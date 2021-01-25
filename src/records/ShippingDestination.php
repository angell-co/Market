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
use craft\commerce\records\ShippingZone;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Shipping destination record
 *
 * This record represents the cost of shipping to a particular destination for a given
 * shipping profile.
 *
 * The shipping profiles themselves are used to determine which products match rather
 * than these, here we just record which country to send to and how much per
 * item plus optional cost per each additional item.
 *
 * @property int $id
 * @property int $shippingProfileId
 * @property int $shippingZoneId
 * @property float $primaryRate
 * @property float $secondaryRate
 * @property string $deliveryTime
 * @property ShippingProfile $shippingProfile
 * @property ShippingZone $shippingZone
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingDestination extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::SHIPPINGDESTINATIONS;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['primaryRate', 'deliveryTime'], 'required'],
            ['shippingZoneId', 'unique', 'targetAttribute' => ['shippingProfileId', 'shippingZoneId']],
        ];
    }

    /**
     * Returns the shipping profile this destination belongs to.
     *
     * @return ActiveQueryInterface
     */
    public function getShippingProfile(): ActiveQueryInterface
    {
        return $this->hasOne(ShippingProfile::class, ['id' => 'shippingProfileId']);
    }

    /**
     * Returns the Commerce shipping zone this destination belongs to.
     *
     * @return ActiveQueryInterface
     */
    public function getShippingZone(): ActiveQueryInterface
    {
        return $this->hasOne(ShippingZone::class, ['id' => 'shippingZoneId']);
    }
}
