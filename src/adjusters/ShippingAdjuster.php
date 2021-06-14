<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\adjusters;

use angellco\market\helpers\ShippingProfileHelper;
use angellco\market\Market;
use Craft;
use craft\base\Component;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\models\OrderAdjustment;
use craft\errors\InvalidFieldException;
use yii\base\InvalidConfigException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingAdjuster extends Component implements AdjusterInterface
{
    public const ADJUSTMENT_TYPE = 'shipping';

    /**
     * This adjuster applies the shipping profiles to an order as follows:
     *
     * 1. Selects the most expensive profile and applies that first using the primary rate once and the secondary rate on any additional matching items
     * 2. Then, if multiple profiles are in play it applies only the secondary rate to all remaining items
     *
     * TODO: add concept of shipping upgrades, see Etsy
     *
     * @param Order $order
     * @return OrderAdjustment[]
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     */
    public function adjust(Order $order): array
    {
        $lineItems = $order->getLineItems();

        // Figure out non shippable items
        $nonShippableItems = [];

        foreach ($lineItems as $item) {
            $purchasable = $item->getPurchasable();
            if ($purchasable && !$purchasable->getIsShippable()) {
                $nonShippableItems[$item->id] = $item->id;
            }
        }

        // Are all line items non shippable items? No shipping cost.
        if (count($lineItems) == count($nonShippableItems)) {
            return [];
        }


        // Check we have a shipping address
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress || !$shippingAddress->countryId) {
            return [];
        }


        // Loop all the line items and work out which shipping profiles are in play
        $shippingProfileIds = [];
        foreach ($lineItems as $item) {
            $purchasable = $item->getPurchasable();

            // Currently only supporting core products and variants
            if (!is_a($purchasable, Variant::class)) {
                return [];
            }

            /** @var Variant $purchasable */

            // Get the shipping profile ID of the product
            $shippingProfileIds[] = $purchasable->getProduct()->getFieldValue('shippingProfile')->value;
        }

        $shippingProfileIds = array_unique($shippingProfileIds);


        // Go over each profile and process it
        $shippingProfilesService = Market::$plugin->getShippingProfiles();
        $shippingProfiles = [];
        $masterProfile = null;
        $masterDestination = null;
        foreach ($shippingProfileIds as $shippingProfileId) {

            // Get the actual profile
            $shippingProfile = $shippingProfilesService->getShippingProfileById($shippingProfileId);

            // Might be deleted?
            if (!$shippingProfile) {
                continue;
            }

            // Set the destinations by country
            $shippingProfile->setShippingDestinationsForCountry($shippingAddress->countryId);

            // Get them again
            $shippingDestinations = $shippingProfile->getShippingDestinations();

            // Check we got some back
            if (!count($shippingDestinations)) {
                continue;
            }

            // This is a bit of a hack but just get the first one that came back
            $shippingDestination = $shippingDestinations[0];

            // If this is the first profile, then just set the master
            if (is_null($masterProfile)) {
                $masterProfile = $shippingProfile;
                $masterDestination = $shippingDestination;

            // If not, override if its more expensive
            } else if ($shippingDestination->primaryRate > $masterDestination->primaryRate) {
                $masterProfile = $shippingProfile;
                $masterDestination = $shippingDestination;
            }

            // Add it to our cache of profiles
            $shippingProfiles[$shippingProfile->id] = $shippingProfile;

        }


        // Bail if we couldn’t work out the master profile and destination
        if (is_null($masterProfile) || is_null($masterDestination)) {
            return [];
        }


        // Now we build up the costs for each line item
        $affectedLineIds = [];
        $amount = 0;
        $masterProfilesApplied = 0;
        foreach ($lineItems as $item) {
            $purchasable = $item->getPurchasable();

            // Get the profile for this line item
            $lineItemProfileId = $purchasable->getProduct()->getFieldValue('shippingProfile')->value;
            if (!isset($shippingProfiles[$lineItemProfileId])) {
                continue;
            }
            $lineItemProfile = $shippingProfiles[$lineItemProfileId];

            // Get the destination
            $destinations = $lineItemProfile->getShippingDestinations();
            if (!count($destinations)) {
                continue;
            }
            $destination = $destinations[0];

            // Check if its the master, if it is then we want to use the primary rate for
            // first item of that kind, then use secondary rate for all the rest
            if ($lineItemProfile->id === $masterProfile->id) {

                if ($masterProfilesApplied > 0) {
                    if ($destination->secondaryRate) {
                        $itemAmount = $destination->secondaryRate * $item->qty;
                        $amount += $itemAmount;

                        // TODO: make a line item level adjustment
//                        $item->shippingCost = $itemAmount;
                        $affectedLineIds[] = $item->id;
                    }
                } else {
                    $itemAmount = $destination->primaryRate;
                    if ($destination->secondaryRate && $item->qty > 1) {
                        $itemAmount += $destination->secondaryRate * ($item->qty - 1);
                    }
                    $amount += $itemAmount;

                    // TODO: make a line item level adjustment
//                    $item->shippingCost = $itemAmount;
                    $affectedLineIds[] = $item->id;
                }

                $masterProfilesApplied++;

            // Not master, so just apply the secondary rate
            } else if ($destination->secondaryRate) {
                $itemAmount = $destination->secondaryRate * $item->qty;
                $amount += $itemAmount;

                // TODO: make a line item level adjustment
//                $item->shippingCost = $itemAmount;
                $affectedLineIds[] = $item->id;
            }
        }


        // Build the actual adjustment
        $processingTimeOptions = ShippingProfileHelper::processingTimeOptions();
        $dispatchTime = $processingTimeOptions[$masterProfile->processingTime]['label'];

        $adjustment = new OrderAdjustment();
        $adjustment->type = self::ADJUSTMENT_TYPE;
        $adjustment->setOrder($order);//X!
        $adjustment->name = $masterProfile->name;
        $adjustment->amount = $amount;
        $adjustment->description = Craft::t('market', "Usually dispatched in {dispatchTime}, estimated delivery to {country} in {deliveryTime}.", [
            'dispatchTime' => strtolower($dispatchTime),
            'country' => $shippingAddress->getCountry()->name,
            'deliveryTime' =>$masterDestination->deliveryTime
        ]);
        $adjustment->isEstimated = false;
        $adjustment->sourceSnapshot = [
            'lineItemsAffected' => $affectedLineIds,
            'profilesUsed' => array_map(static function($profile) {
                return $profile->toArray();
            }, $shippingProfiles)
        ];

        return [$adjustment];

    }
}
