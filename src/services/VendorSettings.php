<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\services;

use angellco\market\db\Table;
use angellco\market\elements\Vendor;
use angellco\market\events\VendorSettingsEvent;
use angellco\market\models\VendorSettings as VendorSettingsModel;
use angellco\market\records\VendorSettings as VendorSettingsRecord;
use Craft;
use craft\base\Component;
use craft\commerce\Plugin as Commerce;
use craft\errors\SiteNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 * Vendor settings service
 *
 * TODO: multi-site
 *
 * @property-read VendorSettingsModel[] $allSettings
 * @property-read VendorSettingsModel $settings
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorSettings extends Component
{

    public const CONFIG_VENDOR_SETTINGS_KEY = 'market.settings.vendors';

    /**
     * @event VendorSettingsEvent The event that is triggered after the settings are saved.
     */
    public const EVENT_AFTER_SAVE_SETTINGS = 'afterSaveSettings';

    /**
     * @event VendorSettingsEvent The event that is triggered after the settings are deleted.
     */
    public const EVENT_AFTER_DELETE_SETTINGS = 'afterDeleteSettings';

    /**
     * Returns the settings for this Site.
     *
     * TODO: memoize this when multi-site is in like category group site settings
     *
     * @param int|null $siteId
     * @return VendorSettingsModel|null
     * @throws SiteNotFoundException
     */
    public function getSettings(int $siteId = null): ?VendorSettingsModel
    {
        // Default to the primary site
        if (!$siteId) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // Get the last added settings record
        $record = VendorSettingsRecord::find()
            ->where(['siteId' => $siteId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        // If there was one, populate a model and return
        if ($record)
        {
            return new VendorSettingsModel($record->toArray([
                'id',
                'siteId',
                'volumeId',
                'fieldLayoutId',
                'shippingOriginId',
                'template',
                'urlFormat',
                'uid',
            ]));
        }

        return null;
    }

    /**
     * Returns all the vendor settings for all active sites.
     *
     * @return VendorSettingsModel[]
     * @throws SiteNotFoundException
     */
    public function getAllSettings(): array
    {
        $models = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $models[$site->id] = $this->getSettings($site->id);
        }

        return $models;
    }

    /**
     * @param int $settingsId
     * @return VendorSettingsModel|null
     */
    public function getSettingsById(int $settingsId): ?VendorSettingsModel
    {
        $record = VendorSettingsRecord::find()
            ->where(['id' => $settingsId])
            ->one();

        if ($record)
        {
            return new VendorSettingsModel($record->toArray([
                'id',
                'siteId',
                'volumeId',
                'fieldLayoutId',
                'shippingOriginId',
                'template',
                'urlFormat',
                'uid',
            ]));
        }

        return null;
    }

    /**
     * @param VendorSettingsModel $settings
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function saveSettings(VendorSettingsModel $settings): bool
    {
        $isNew = !$settings->id;

        // Ensure the settings model has a UID
        if ($isNew) {
            $settings->uid = StringHelper::UUID();
        } else if (!$settings->uid) {
            $settings->uid = Db::uidById(Table::VENDORSETTINGS, $settings->id);
        }

        // Make sure it validates
        if (!$settings->validate()) {
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        // Save it to the project config
        $path = self::CONFIG_VENDOR_SETTINGS_KEY.".".$settings->uid;
        $projectConfig->set($path, $settings->getConfig(), "Save vendor settings “{$settings->getSite()->name}”");

        // Now set the ID on the settings in case the caller needs to know it
        if ($isNew) {
            $settings->id = Db::idByUid(Table::VENDORSETTINGS, $settings->uid);
        }

        return true;
    }

    /**
     * @param ConfigEvent $event
     * @throws SiteNotFoundException
     * @throws \Throwable
     */
    public function handleChangedSettings(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $shippingOrigin = Commerce::getInstance()->getCountries()->getCountryByIso($data['shippingOrigin']);
        $site = Craft::$app->getSites()->getSiteByUid($data['site']);
        $volume = Craft::$app->getVolumes()->getVolumeByUid($data['volume']);
        $settingsRecord = $this->_getSettingsRecord($uid);
        $isNewSettings = $settingsRecord->getIsNewRecord();

        if (!$shippingOrigin || !$site || !$volume || !$settingsRecord) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $settingsRecord->shippingOriginId = $shippingOrigin->id;
            $settingsRecord->siteId = $site->id;
            $settingsRecord->template = $data['template'];
            $settingsRecord->urlFormat = $data['urlFormat'];
            $settingsRecord->volumeId = $volume->id;
            $settingsRecord->uid = $uid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));

                $layout->id = $settingsRecord->fieldLayoutId;
                $layout->type = Vendor::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $settingsRecord->fieldLayoutId = $layout->id;
            } else if ($settingsRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($settingsRecord->fieldLayoutId);
                $settingsRecord->fieldLayoutId = null;
            }

            // Save
            $settingsRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SETTINGS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SETTINGS, new VendorSettingsEvent([
                'vendorSettings' => $this->getSettingsById($settingsRecord->id),
                'isNew' => $isNewSettings
            ]));
        }

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Vendor::class);
    }

    /**
     * @param ConfigEvent $event
     * @throws \yii\db\Exception
     */
    public function handleDeletedSettings(ConfigEvent $event): void
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];

        // Get the record
        $record = $this->_getSettingsRecord($uid);

        // If that came back empty, we're done!
        if (!$record) {
            return;
        }

        $vendorSettings = $this->getSettingsById($record->id);

        // Delete its row
        Craft::$app->db->createCommand()
            ->delete(Table::VENDORSETTINGS, ['id' => $record->id])
            ->execute();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SETTINGS)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SETTINGS, new VendorSettingsEvent([
                'vendorSettings' => $vendorSettings,
            ]));
        }

    }

    /**
     * Gets a settings record by uid.
     *
     * @param string $uid
     * @return VendorSettingsRecord
     */
    private function _getSettingsRecord(string $uid): VendorSettingsRecord
    {
        $query = VendorSettingsRecord::find()->where(['uid' => $uid]);
        return $query->one() ?? new VendorSettingsRecord();
    }
}
