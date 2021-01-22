<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\models;
use craft\base\Model;
use craft\commerce\models\ShippingAddressZone;
use craft\commerce\Plugin as Commerce;

/**
 * Shipping destination model
 *
 * @property ShippingAddressZone|null $shippingZone
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingDestination extends Model
{

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int Shipping profile ID
     */
    public $shippingProfileId;

    /**
     * @var int Shipping zone ID
     */
    public $shippingZoneId;

    /**
     * @var float Primary rate
     */
    public $primaryRate;

    /**
     * @var float Secondary rate
     */
    public $secondaryRate;

    /**
     * @var string Delivery time
     */
    public $deliveryTime;

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['shippingProfileId', 'shippingZoneId', 'primaryRate', 'deliveryTime'], 'required'];

        return $rules;
    }

//    public function getShippingProfile()
//    {
        // TODO with shippingProfiles service
//    }

    /**
     * Returns the shipping zone set on the destination.
     *
     * @return ShippingAddressZone|null
     */
    public function getShippingZone(): ?ShippingAddressZone
    {
        return Commerce::getInstance()->getShippingZones()->getShippingZoneById($this->shippingZoneId);
    }

}
