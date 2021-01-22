<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\services;

use craft\base\Component;

/**
 * Shipping destinations service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingDestinations extends Component
{
//    /**
//     * Saves a shipping destination.
//     *
//     * @param Marketplace_ShippingDestinationModel $shippingDestination
//     *
//     * @return bool
//     * @throws Exception
//     * @throws \Exception
//     */
//    public function saveShippingDestination(Marketplace_ShippingDestinationModel $shippingDestination)
//    {
//        if ($shippingDestination->id)
//        {
//            $shippingDestinationRecord = Marketplace_ShippingDestinationRecord::model()->findById($shippingDestination->id);
//
//            if (!$shippingDestinationRecord)
//            {
//                throw new Exception(Craft::t('No shipping destination exists with the ID “{id}”.', array('id' => $shippingDestination->id)));
//            }
//        }
//        else
//        {
//            $shippingDestinationRecord = new Marketplace_ShippingDestinationRecord();
//        }
//
//        $shippingDestinationRecord->shippingProfileId = $shippingDestination->shippingProfileId;
//        $shippingDestinationRecord->shippingZoneId = $shippingDestination->shippingZoneId;
//        $shippingDestinationRecord->primaryRate = $shippingDestination->primaryRate;
//        $shippingDestinationRecord->secondaryRate = ($shippingDestination->secondaryRate === '' ? 0 : $shippingDestination->secondaryRate);
//        $shippingDestinationRecord->deliveryTime = $shippingDestination->deliveryTime;
//
//        $shippingDestinationRecord->validate();
//        $shippingDestination->addErrors($shippingDestinationRecord->getErrors());
//
//        // Validate the model too so we catch any requirements from that
//        $shippingDestination->validate();
//
//        if (!$shippingDestination->hasErrors())
//        {
//            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
//            try
//            {
//                // Save it!
//                $shippingDestinationRecord->save(false);
//
//                // Now that we have an ID, save it on the model
//                if (!$shippingDestination->id)
//                {
//                    $shippingDestination->id = $shippingDestinationRecord->id;
//                }
//
//                // Might as well update our cache of the shipping destination while we have it.
//                $this->_shippingDestinationsById[$shippingDestination->id] = $shippingDestination;
//
//                if ($transaction !== null)
//                {
//                    $transaction->commit();
//                }
//            }
//            catch (\Exception $e)
//            {
//                if ($transaction !== null)
//                {
//                    $transaction->rollback();
//                }
//
//                throw $e;
//            }
//
//            return true;
//        }
//
//        return false;
//    }
//
//    /**
//     * Deletes a shipping destination.
//     *
//     * @param Marketplace_ShippingDestinationModel $shippingDestination
//     *
//     * @return bool
//     */
//    public function deleteShippingDestination(Marketplace_ShippingDestinationModel $shippingDestination)
//    {
//
//        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
//        try {
//
//            Marketplace_ShippingDestinationRecord::model()->deleteByPk($shippingDestination->id);
//
//            if ($transaction !== null)
//            {
//                $transaction->commit();
//            }
//
//            return true;
//
//        } catch (\Exception $e) {
//            if ($transaction !== null)
//            {
//                $transaction->rollback();
//            }
//
//            return false;
//        }
//
//    }
}
