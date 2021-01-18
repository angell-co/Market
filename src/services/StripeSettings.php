<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\services;

use angellco\market\db\Table;
use angellco\market\records\StripeSettings as StripeSettingsRecord;
use Craft;
use craft\base\Component;
use angellco\market\models\StripeSettings as StripeSettingsModel;
use craft\errors\SiteNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\Exception;
use yii\web\ServerErrorHttpException;

/**
 * Stripe settings service
 *
 * TODO: multi-site
 *
 * @property-read StripeSettingsModel $settings
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class StripeSettings extends Component
{

    public const CONFIG_STRIPE_SETTINGS_KEY = 'market.settings.stripe';

    /**
     * Returns the settings for this Site.
     *
     * @return StripeSettingsModel
     * @throws SiteNotFoundException
     */
    public function getSettings(): StripeSettingsModel
    {
        $siteId = Craft::$app->getSites()->getPrimarySite()->id;

        // Get the last added settings record
        $record = StripeSettingsRecord::find()
            ->where(['siteId' => $siteId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        // If there was one, populate a model and return
        if ($record)
        {
            return new StripeSettingsModel($record->toArray([
                'id',
                'siteId',
                'clientId',
                'secretKey',
                'publishableKey',
                'redirectSuccess',
                'redirectError',
                'uid',
            ]));
        }

        // If not, return a fresh model
        return new StripeSettingsModel();
    }

    /**
     * @param int $settingsId
     * @return StripeSettingsModel|null
     */
    public function getSettingsById(int $settingsId): ?StripeSettingsModel
    {
        $record = StripeSettingsRecord::find()
            ->where(['id' => $settingsId])
            ->one();

        if ($record)
        {
            return new StripeSettingsModel($record->toArray([
                'id',
                'siteId',
                'clientId',
                'secretKey',
                'publishableKey',
                'redirectSuccess',
                'redirectError',
                'uid',
            ]));
        }

        return null;
    }

    /**
     * @param StripeSettingsModel $settings
     * @return bool
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     * @throws \yii\base\Exception
     */
    public function saveSettings(StripeSettingsModel $settings): bool
    {
        $isNew = !$settings->id;

        // Ensure the settings model has a UID
        if ($isNew) {
            $settings->uid = StringHelper::UUID();
        } else if (!$settings->uid) {
            $settings->uid = Db::uidById(Table::STRIPESETTINGS, $settings->id);
        }

        // Make sure it validates
        if (!$settings->validate()) {
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        // Save it to the project config
        $path = self::CONFIG_STRIPE_SETTINGS_KEY.".".$settings->uid;
        $projectConfig->set($path, $settings->getConfig(), "Save stripe settings “{$settings->getSite()->name}”");

        // Now set the ID on the settings in case the caller needs to know it
        if ($isNew) {
            $settings->id = Db::idByUid(Table::STRIPESETTINGS, $settings->uid);
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

        $site = Craft::$app->getSites()->getSiteByUid($data['site']);
        $settingsRecord = $this->_getSettingsRecord($uid);

        if (!$site || !$settingsRecord) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $settingsRecord->siteId = $site->id;
            $settingsRecord->clientId = $data['clientId'];
            $settingsRecord->secretKey = $data['secretKey'];
            $settingsRecord->publishableKey = $data['publishableKey'];
            $settingsRecord->redirectSuccess = $data['redirectSuccess'];
            $settingsRecord->redirectError = $data['redirectError'];
            $settingsRecord->uid = $uid;

            // Save
            $settingsRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param ConfigEvent $event
     * @throws Exception
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

        // Delete its row
        Craft::$app->db->createCommand()
            ->delete(Table::STRIPESETTINGS, ['id' => $record->id])
            ->execute();
    }

    /**
     * Gets a settings record by uid.
     *
     * @param string $uid
     * @return StripeSettingsRecord
     */
    private function _getSettingsRecord(string $uid): StripeSettingsRecord
    {
        $query = StripeSettingsRecord::find()->where(['uid' => $uid]);
        return $query->one() ?? new StripeSettingsRecord();
    }
}
