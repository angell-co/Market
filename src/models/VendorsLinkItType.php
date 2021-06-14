<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\models;

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use fruitstudios\linkit\base\ElementLink;

/**
 * LinkIt link type for Vendors
 *
 * @property-read mixed $vendor
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorsLinkItType extends ElementLink
{
    // Private
    // =========================================================================

    private $_vendor;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return Vendor::class;
    }

    public static function defaultLabel(): string
    {
        return Craft::t('market', 'Vendor');
    }

    // Public Methods
    // =========================================================================

    public function getVendor()
    {
        if(is_null($this->_vendor))
        {
            $this->_vendor = Market::$plugin->getVendors()->getVendorById((int) $this->value, $this->ownerElement->siteId ?? null);
        }
        return $this->_vendor;
    }
}
