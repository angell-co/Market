<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\records;

use craft\db\ActiveRecord;

/**
 * Shipping profile record
 *
 * This is like a shipping method but we’re using a different name to separate
 * our stuff from Commerce. Also, as we have essentially cloned the Etsy model
 * it seemed sensible to use the names they do, lots of sellers will already be
 * familiar with it anyway.
 *
 * It has a vendor, name, shipping origin, processing/turn-around time and
 * that’s it.
 *
 * Related to this record will be a number of shipping destinations, each with
 * their own costs.
 *
 * Products that use the shipping profiles field type can then select a profile.
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingProfile extends ActiveRecord
{}
