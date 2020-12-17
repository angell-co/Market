<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\db;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
abstract class Table
{
    public const ORDERGROUPS = '{{%market_ordergroups}}';
    public const ORDERGROUPS_COMMERCEORDERS = '{{%market_ordergroups_commerce_orders}}';
    public const SHIPPINGDESTINATIONS = '{{%market_shippingdestinations}}';
    public const SHIPPINGPROFILES = '{{%market_shippingprofiles}}';
    public const STRIPESETTINGS = '{{%market_stripesettings}}';
    public const VENDORS = '{{%market_vendors}}';
    public const VENDORSETTINGS = '{{%market_vendorsettings}}';
}
