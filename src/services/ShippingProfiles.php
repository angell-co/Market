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

use angellco\market\models\ShippingDestination;
use angellco\market\models\ShippingProfile;
use angellco\market\records\ShippingDestination as ShippingDestinationRecord;
use angellco\market\records\ShippingProfile as ShippingProfileRecord;
use Craft;
use craft\base\Component;
use yii\web\NotFoundHttpException;

/**
 * Shipping profiles service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingProfiles extends Component
{

    /**
     * @param int $id
     * @return ShippingProfile|null
     */
    public function getShippingProfileById(int $id): ?ShippingProfile
    {
        $record = ShippingProfileRecord::find()
            ->where(['id' => $id])
            ->one();

        if ($record)
        {
            return new ShippingProfile($record->toArray([
                'id',
                'vendorId',
                'originCountryId',
                'name',
                'processingTime',
            ]));
        }

        return null;
    }

    /**
     * Returns a profiles's destinations.
     *
     * @param int $profileId
     * @return ShippingDestination[]
     */
    public function getShippingProfileDestinations(int $profileId): array
    {
        $results = ShippingDestinationRecord::find()
            ->where(['shippingProfileId' => $profileId])
            ->all();
        $destinations = [];

        foreach ($results as $result) {
            $destinations[] = new ShippingDestination($result->toArray([
                'id',
                'shippingProfileId',
                'shippingZoneId',
                'primaryRate',
                'secondaryRate',
                'deliveryTime',
            ]));
        }

        return $destinations;
    }

    /**
     * @param ShippingProfile $model
     * @param bool $runValidation
     * @return bool
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function saveShippingProfile(ShippingProfile $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = ShippingProfileRecord::findOne($model->id);

            if (!$record) {
                throw new NotFoundHttpException(Craft::t('market', 'No shipping profile exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new ShippingProfileRecord();
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Shipping profile not saved due to validation error.', __METHOD__);

            return false;
        }

        $record->name = $model->name;
        $record->vendorId = $model->vendorId;
        $record->originCountryId = $model->originCountryId;
        $record->processingTime = $model->processingTime;

        $record->validate();
        $model->addErrors($record->getErrors());
//
//        // Validate the model too so we catch any requirements from that
//        $model->validate();

//        if ($model->hasErrors()) {
//            return false;
//        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $model->id = $record->id;

            // If it saved OK we can save the shipping destinations
            // TODO: here I am

//            // Might as well update our cache of the shipping profile while we have it.
//            $this->_shippingProfilesById[$shippingProfile->id] = $shippingProfile;
//
//            // Get existing destinations
//            $existingDestinationsById = $shippingProfile->getDestinations('id');
//
            // Now go over each of the destination models
//            foreach ($shippingProfile->destinations as $rowId => $row) {
//
//             TODO: This bit is probably needed in the controller
//
//                // Check if this is a new one or not
//                if (strncmp($rowId, 'new', 3) === 0 || !isset($existingDestinationsById[$rowId])) {
//                    $shippingDestination = new Marketplace_ShippingDestinationModel();
//                    $shippingDestination->shippingProfileId = $shippingProfile->id;
//                } else {
//                    $shippingDestination = $existingDestinationsById[$rowId];
//
//                    // Remove from the stack of existing ones so we know what to remove
//                    unset($existingDestinationsById[$rowId]);
//                }
//

//
//                // Save it
//                craft()->marketplace_shippingDestinations->saveShippingDestination($shippingDestination);
//            }
//
//            // Remove existing destinations not included in this save
//            if (count($existingDestinationsById)) {
//                foreach ($existingDestinationsById as $existingDestination) {
//                    craft()->marketplace_shippingDestinations->deleteShippingDestination($existingDestination);
//                }
//            }


            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }



//        $this->_allShippingMethods = null; //clear the cache

        return true;
    }

}
