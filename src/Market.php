<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market;

use angellco\market\base\PluginTrait;
use angellco\market\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;

/**
 * @property-read mixed $settingsResponse
 * @property-read array $cpNavItem
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Market extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Market::$plugin
     *
     * @var Market
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '2.0.0';

    /**
     * @var bool Whether the plugin has its own section in the control panel
     */
    public $hasCpSection = true;

    /**
     * @var bool Whether the plugin has a settings page in the control panel
     */
    public $hasCpSettings = true;

    use PluginTrait;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Market::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_installGlobalEventListeners();

        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            //
        } else if ($request->getIsCpRequest()) {
            $this->_registerCpRoutes();

        } else {
            $this->_registerSiteRoutes();
        }

    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): array
    {
        $navItem = parent::getCpNavItem();

        $navItem['label'] = Craft::t('market', 'Market');

        // TODO: Permissions
        // if (Craft::$app->getUser()->checkPermission('market-manageVendors')) {}

        $navItem['subnav']['vendors'] = [
            'label' => Craft::t('market', 'Vendors'),
            'url' => 'market/vendors'
        ];

        $navItem['subnav']['settings'] = [
            'label' => Craft::t('app', 'Settings'),
            'url' => 'market/settings'
        ];

        return $navItem;
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    public function getSettingsResponse()
    {
        $url = UrlHelper::cpUrl('market/settings/general');
        return Craft::$app->controller->redirect($url);
    }

}
