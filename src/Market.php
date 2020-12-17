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
use angellco\market\elements\Vendor;
use angellco\market\fields\Vendors;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 *
 * @property-read array $cpNavItem
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

        // Register the element type
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Vendor::class;
            }
        );

        // Register the field types
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Vendors::class;
            }
        );

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

}
