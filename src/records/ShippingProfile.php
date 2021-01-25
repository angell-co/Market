<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\records;

use angellco\market\db\Table;
use craft\commerce\records\Country;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

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
 * @property int $id
 * @property int $vendorId
 * @property int $originCountryId
 * @property string $name
 * @property string $processingTime
 * @property Vendor $vendor
 * @property Country $originCountry
 * @property ShippingDestination[] $destinations
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingProfile extends ActiveRecord
{
    public const PROCESSING_TIMES = [
        '1_day',
        '1_2_days',
        '1_3_days',
        '3_5_days',
        '1_2_weeks',
        '2_3_weeks',
        '3_4_weeks',
        '4_6_weeks',
        '6_8_weeks',
        'unknown',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::SHIPPINGPROFILES;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['vendorId', 'originCountryId', 'name'], 'required'],
            ['name', 'unique', 'targetAttribute' => ['name', 'vendorId']],
        ];
    }

    /**
     * Returns the vendor this profile belongs to.
     *
     * @return ActiveQueryInterface
     */
    public function getVendor(): ActiveQueryInterface
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendorId']);
    }

    /**
     * Returns the origin country this profile belongs to.
     *
     * @return ActiveQueryInterface
     */
    public function getOriginCountry(): ActiveQueryInterface
    {
        return $this->hasOne(Country::class, ['id' => 'originCountryId']);
    }

    /**
     * Returns the shipping destinations this profile relates to.
     *
     * @return ActiveQueryInterface
     */
    public function getDestinations(): ActiveQueryInterface
    {
        return $this->hasMany(ShippingDestination::class, ['shippingProfileId' => 'id']);
    }
}
