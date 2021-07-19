<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */
namespace angellco\market\events;

use angellco\market\models\VendorSettings;
use yii\base\Event;

/**
 * Vendor settings event class.
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorSettingsEvent extends Event
{
    /**
     * @var VendorSettings|null The vendor settings model associated with the event.
     */
    public $vendorSettings;

    /**
     * @var bool Whether the settings are brand new
     */
    public $isNew = false;
}
