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
use angellco\market\elements\Vendor;
use angellco\market\helpers\ShippingProfileHelper;
use angellco\market\Market;
use Craft;
use craft\base\Model;
use craft\commerce\models\Country;
use craft\commerce\Plugin as Commerce;
use craft\helpers\UrlHelper;

/**
 * Shipping profile model
 *
 * @property string $cpEditUrl
 * @property Vendor|null $vendor
 * @property null|Country $originCountry
 * @property ShippingDestination[]|array $shippingDestinations
 * @property string $processingTimeLabel
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingProfile extends Model
{

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int Vendor ID
     */
    public $vendorId;

    /**
     * @var int Origin country ID
     */
    public $originCountryId;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Processing time
     */
    public $processingTime = '1_3_days';

    /**
     * @var ShippingDestination[] Shipping destinations
     */
    private $_shippingDestinations;

    /**
     * @var Vendor|null The vendor element
     */
    private $_vendor;

    /**
     * @var Country|null The origin country
     */
    private $_originCountry;

    /**
     * Use the translated shipping profile's name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name) ?: static::class;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Craft::t('app', 'Name'),
            'vendor' => Craft::t('market', 'Vendor'),
            'processingTime' => Craft::t('market', 'Processing Time'),
            'originCountry' => Craft::t('market', 'Origin Country'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['vendorId', 'originCountryId', 'name', 'processingTime', 'shippingDestinations'], 'required'];

        // TODO: shippingDestinations needs has its own validator. Or, use the same method as siteSettings on CategoryGroup

        return $rules;
    }

    /**
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl("market/shipping-profiles/{$this->id}");
    }

    /**
     * Returns the vendor for this profile.
     *
     * @return Vendor|null
     */
    public function getVendor(): ?Vendor
    {
        if (!$this->_vendor) {
            $this->_vendor = Market::$plugin->getVendors()->getVendorById($this->vendorId);
        }

        return $this->_vendor;
    }

    /**
     * Returns the origin country for this profile.
     */
    public function getOriginCountry(): ?Country
    {
        if (!$this->_originCountry) {
            $this->_originCountry = Commerce::getInstance()->getCountries()->getCountryById($this->originCountryId);
        }

        return $this->_originCountry;
    }

    /**
     * Returns the human readable label for the processing time
     *
     * @return string
     */
    public function getProcessingTimeLabel(): string
    {
        return ShippingProfileHelper::processingTimeOptions()[$this->processingTime]['label'];
    }

    /**
     * Returns the profile's destination models.
     *
     * @return ShippingDestination[]|array
     */
    public function getShippingDestinations(): array
    {
        if ($this->_shippingDestinations !== null) {
            return $this->_shippingDestinations;
        }

        if (!$this->id) {
            return [];
        }

        $this->setShippingDestinations(Market::$plugin->getShippingProfiles()->getShippingProfileDestinations($this->id));

        return $this->_shippingDestinations;
    }

    /**
     * Sets the profile's shipping destinations.
     *
     * @param ShippingDestination[] $shippingDestinations
     */
    public function setShippingDestinations(array $shippingDestinations): void
    {
        $this->_shippingDestinations = $shippingDestinations;
    }


//    TODO: adjuster stuff
//    /**
//     * Sets the destinations to those that match the given country ID.
//     *
//     * This is for the adjuster
//     *
//     * @param $countryId
//     */
//    public function setDestinationsForCountry($countryId)
//    {
//        $destinations = [];
//
//        foreach ($this->getDestinations() as $destination) {
//            $shippingZone = craft()->commerce_shippingZones->getShippingZoneById($destination->shippingZoneId);
//            if (!$shippingZone)
//            {
//                continue;
//            }
//
//            // Only support country based
//            if (!$shippingZone->countryBased)
//            {
//                continue;
//            }
//
//            // Get all the country ids for this zone
//            $countryIds = $shippingZone->getCountryIds();
//
//            // Bail if the given country isn’t in there
//            if (!in_array($countryId, $countryIds))
//            {
//                continue;
//            }
//
//            // If we got this far then add the destination to the output
//            $destinations[] = $destination;
//        }
//
//        // Now override the original destinations with our filtered ones
//        $this->setDestinations($destinations);
//    }


}
