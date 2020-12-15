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

use angellco\market\elements\Vendor;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use yii\base\Event;

/**
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

        // Register the element type
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Vendor::class;
            }
        );
    }

}
