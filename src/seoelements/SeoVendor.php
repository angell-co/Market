<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\seoelements;

use angellco\market\elements\Vendor;
use angellco\market\errors\VendorSettingsNotFoundException;
use angellco\market\events\VendorSettingsEvent;
use angellco\market\Market;
use angellco\market\models\VendorSettings as VendorSettingsModel;
use angellco\market\services\VendorSettings;
use craft\errors\SiteNotFoundException;
use nystudio107\seomatic\Seomatic;
use nystudio107\seomatic\base\SeoElementInterface;
use nystudio107\seomatic\helpers\ArrayHelper;
use nystudio107\seomatic\helpers\Config as ConfigHelper;
use nystudio107\seomatic\models\MetaBundle;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\db\ElementQueryInterface;

use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class SeoVendor implements SeoElementInterface
{

    // Constants
    // =========================================================================

    const META_BUNDLE_TYPE = 'vendor';
    const ELEMENT_CLASSES = [
        Vendor::class,
    ];
    const REQUIRED_PLUGIN_HANDLE = 'market';
    const CONFIG_FILE_PATH = 'vendormeta/Bundle';

    // Public Static Methods
    // =========================================================================

    /**
     * Return the sourceBundleType for that this SeoElement handles
     *
     * @return string
     */
    public static function getMetaBundleType(): string
    {
        return self::META_BUNDLE_TYPE;
    }

    /**
     * Returns an array of the element classes that are handled by this SeoElement
     *
     * @return array
     */
    public static function getElementClasses(): array
    {
        return self::ELEMENT_CLASSES;
    }

    /**
     * Return the refHandle (e.g.: `entry` or `category`) for the SeoElement
     *
     * @return string
     */
    public static function getElementRefHandle(): string
    {
        return Vendor::refHandle();
    }

    /**
     * Return the handle to a required plugin for this SeoElement type
     *
     * @return null|string
     */
    public static function getRequiredPluginHandle()
    {
        return self::REQUIRED_PLUGIN_HANDLE;
    }

    /**
     * Install any event handlers for this SeoElement type
     */
    public static function installEventHandlers()
    {
        $request = Craft::$app->getRequest();

        // Install for all non-console requests
        if (!$request->getIsConsoleRequest()) {
            // Handler: VendorSettings::EVENT_AFTER_SAVE_SETTINGS
            Event::on(
                VendorSettings::class,
                VendorSettings::EVENT_AFTER_SAVE_SETTINGS,
                function (VendorSettingsEvent $event) {
                    Craft::debug(
                        'VendorSettings::EVENT_AFTER_SAVE_SETTINGS',
                        __METHOD__
                    );
                    if ($event->vendorSettings !== null && $event->vendorSettings->id !== null) {
                        Seomatic::$plugin->metaBundles->invalidateMetaBundleById(
                            SeoVendor::getMetaBundleType(),
                            $event->vendorSettings->id,
                            $event->isNew
                        );
                        // Create the meta bundles for this vendor if it's new
                        if ($event->isNew) {
                            SeoVendor::createContentMetaBundle($event->vendorSettings);
                            Seomatic::$plugin->sitemaps->submitSitemapIndex();
                        }
                    }
                }
            );
            // Handler: VendorSettings::EVENT_AFTER_DELETE_SETTINGS
            Event::on(
                VendorSettings::class,
                VendorSettings::EVENT_AFTER_DELETE_SETTINGS,
                function (VendorSettingsEvent $event) {
                    Craft::debug(
                        'VendorSettings::EVENT_AFTER_DELETE_SETTINGS',
                        __METHOD__
                    );
                    if ($event->vendorSettings !== null && $event->vendorSettings->id !== null) {
                        Seomatic::$plugin->metaBundles->invalidateMetaBundleById(
                            SeoVendor::getMetaBundleType(),
                            $event->vendorSettings->id,
                            false
                        );
                        // Delete the meta bundles for this vendor
                        Seomatic::$plugin->metaBundles->deleteMetaBundleBySourceId(
                            SeoVendor::getMetaBundleType(),
                            $event->vendorSettings->id
                        );
                    }
                }
            );
        }

        // Install only for non-console site requests
        if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
        }

        // Install only for non-console Control Panel requests
        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
//            // Category Groups sidebar
//            Seomatic::$view->hook('cp.categories.edit.details', function (&$context) {
//                $html = '';
//                Seomatic::$view->registerAssetBundle(SeomaticAsset::class);
//                /** @var  $category Category */
//                $category = $context['category'];
//                if ($category !== null && $category->uri !== null) {
//                    Seomatic::$plugin->metaContainers->previewMetaContainers($category->uri, $category->siteId, true);
//                    // Render our preview sidebar template
//                    if (Seomatic::$settings->displayPreviewSidebar) {
//                        $html .= PluginTemplate::renderPluginTemplate('_sidebars/category-preview.twig');
//                    }
//                    // Render our analysis sidebar template
//// @TODO: This will be added an upcoming 'pro' edition
////                if (Seomatic::$settings->displayAnalysisSidebar) {
////                    $html .= PluginTemplate::renderPluginTemplate('_sidebars/category-analysis.twig');
////                }
//                }
//
//                return $html;
//            });
        }
    }

    /**
     * Return an ElementQuery for the sitemap elements for the given MetaBundle
     *
     * @param MetaBundle $metaBundle
     *
     * @return ElementQueryInterface
     */
    public static function sitemapElementsQuery(MetaBundle $metaBundle): ElementQueryInterface
    {
        return Vendor::find()
            ->siteId($metaBundle->sourceSiteId)
            ->limit($metaBundle->metaSitemapVars->sitemapLimit);
    }

    /**
     * Return an ElementInterface for the sitemap alt element for the given MetaBundle
     * and Element ID
     *
     * @param MetaBundle $metaBundle
     * @param int        $elementId
     * @param int        $siteId
     *
     * @return null|ElementInterface
     */
    public static function sitemapAltElement(
        MetaBundle $metaBundle,
        int $elementId,
        int $siteId
    ) {
        return Vendor::find()
            ->id($elementId)
            ->siteId($siteId)
            ->limit(1)
            ->one()
            ;
    }

    /**
     * Return a preview URI for a given $sourceHandle and $siteId
     * This just returns the first element
     *
     * @param string    $sourceHandle
     * @param int|null  $siteId
     *
     * @return string|null
     */
    public static function previewUri(string $sourceHandle, $siteId)
    {
        $uri = null;
        $element = Vendor::find()
            ->siteId($siteId)
            ->one()
        ;
        if ($element) {
            $uri = $element->uri;
        }

        return $uri;
    }

    /**
     * Return an array of FieldLayouts from the $sourceHandle
     *
     * @param string $sourceHandle
     *
     * @return array
     */
    public static function fieldLayouts(string $sourceHandle): array
    {
        $parts = explode('_', $sourceHandle);
        $siteHandle = $parts[1] ?? $sourceHandle;
        $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        $layouts = [];
        $layoutId = null;
        try {
            $vendorSettings = Market::$plugin->getVendorSettings()->getSettings($site->id);
            if ($vendorSettings) {
                $layoutId = $vendorSettings->getFieldLayoutId();
            }
        } catch (\Exception $e) {
            $layoutId = null;
        }
        if ($layoutId) {
            $layouts[] = Craft::$app->getFields()->getLayoutById($layoutId);
        }

        return $layouts;
    }

    /**
     * Return the (entry) type menu as a $id => $name associative array
     *
     * @param string $sourceHandle
     *
     * @return array
     */
    public static function typeMenuFromHandle(string $sourceHandle): array
    {
        return [];
    }

    /**
     * Return the source model of the given $sourceId
     *
     * @param int $sourceId
     *
     * @return VendorSettingsModel|null
     */
    public static function sourceModelFromId(int $sourceId)
    {
        return Market::$plugin->getVendorSettings()->getSettingsById($sourceId);
    }

    /**
     * Return the source model of the given $sourceId
     *
     * @param string|null $sourceHandle
     *
     * @return VendorSettingsModel|null
     * @throws SiteNotFoundException
     */
    public static function sourceModelFromHandle($sourceHandle)
    {
        if (!$sourceHandle) {
            $site = Craft::$app->getSites()->getCurrentSite();
        } else {
            $parts = explode('_', $sourceHandle);
            $siteHandle = $parts[1] ?? $sourceHandle;
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        }
        return Market::$plugin->getVendorSettings()->getSettings($site->id);
    }

    /**
     * Return the most recently updated Element from a given source model
     *
     * @param Model $sourceModel
     * @param int   $sourceSiteId
     *
     * @return null|ElementInterface
     */
    public static function mostRecentElement(Model $sourceModel, int $sourceSiteId)
    {
        return Vendor::find()
            ->siteId($sourceSiteId)
            ->limit(1)
            ->orderBy(['elements.dateUpdated' => SORT_DESC])
            ->one()
            ;
    }

    /**
     * Return the path to the config file directory
     *
     * @return string
     */
    public static function configFilePath(): string
    {
        return self::CONFIG_FILE_PATH;
    }

    /**
     * Return a meta bundle config array for the given $sourceModel
     *
     * @param Model $sourceModel
     *
     * @return array
     * @throws InvalidConfigException
     */
    public static function metaBundleConfig(Model $sourceModel): array
    {
        /** @var VendorSettingsModel $sourceModel */
        $site = $sourceModel->getSite();

        return ArrayHelper::merge(
            ConfigHelper::getConfigFromFile(self::configFilePath(), '@angellco/market'),
            [
                'sourceId' => $sourceModel->id,
                'sourceSiteId' => $sourceModel->siteId,
                'sourceHandle' => 'vendorsettings_' . $site->handle,
                'sourceName' => $sourceModel->getName()
            ]
        );
    }

    /**
     * Return the source id from the $element
     *
     * @param ElementInterface $element
     * @return int|null
     *
     * @throws SiteNotFoundException
     * @throws VendorSettingsNotFoundException
     */
    public static function sourceIdFromElement(ElementInterface $element)
    {
        /** @var Vendor $element */
        return $element->getSettings()->id;
    }

    /**
     * Return the (entry) type id from the $element
     *
     * @param ElementInterface $element
     *
     * @return int|null
     */
    public static function typeIdFromElement(ElementInterface $element)
    {
        /** @var Vendor $element */
        return null;
    }

    /**
     * Return the source handle from the $element
     *
     * @param ElementInterface $element
     *
     * @return string|null
     * @throws InvalidConfigException
     */
    public static function sourceHandleFromElement(ElementInterface $element)
    {
        /** @var Vendor $element */
        return 'vendorsettings_' . $element->getSite()->handle;
    }

    /**
     * Create a MetaBundle in the db for each site, from the passed in $sourceModel
     *
     * @param Model $sourceModel
     */
    public static function createContentMetaBundle(Model $sourceModel)
    {
        /** @var VendorSettingsModel $sourceModel */
        $sites = Craft::$app->getSites()->getAllSites();
        foreach ($sites as $site) {
            $seoElement = self::class;
            /** @var SeoElementInterface $seoElement */
            Seomatic::$plugin->metaBundles->createMetaBundleFromSeoElement($seoElement, $sourceModel, $site->id);
        }
    }

    /**
     * Create all the MetaBundles in the db for this Seo Element
     */
    public static function createAllContentMetaBundles()
    {
        // Get all of the vendor settings
        $vendorSettings = Market::$plugin->getVendorSettings()->getAllSettings();
        foreach ($vendorSettings as $vendorSettingsModel) {
            self::createContentMetaBundle($vendorSettingsModel);
        }
    }
}
