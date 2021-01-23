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

use angellco\market\db\Table;
use angellco\market\Market;
use angellco\market\models\ShippingDestination;
use angellco\market\models\ShippingProfile;
use angellco\market\records\ShippingDestination as ShippingDestinationRecord;
use angellco\market\records\ShippingProfile as ShippingProfileRecord;
use Craft;
use craft\base\Component;
use craft\db\Query;
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

    // TODO: class caching?

    /**
     * Saves a shipping profile and its destinations.
     *
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

//            // Might as well update our cache of the shipping profile while we have it.
//            $this->_shippingProfilesById[$shippingProfile->id] = $shippingProfile;
//
            // Get existing destination IDs direct from the db
            $query = (new Query());
            $query->select([
                'id',
                'shippingProfileId'
            ]);
            $query->from(Table::SHIPPINGDESTINATIONS);
            $query->where(['shippingProfileId' => $model->id]);
            $existingIds = $query->column();

            // Now go over each of the destination models
            foreach ($model->getShippingDestinations() as $destination) {

                // If it saves then remove it from the stack of existing IDs
                if (Market::$plugin->getShippingDestinations()->saveShippingDestination($destination) && ($key = array_search($destination->id, $existingIds, false)) !== false) {
                    unset($existingIds[$key]);
                }
            }

            // If there are any existing IDs left then they need deleting
            if (!empty($existingIds)) {
                foreach ($existingIds as $existingId) {
                    Market::$plugin->getShippingDestinations()->deleteShippingDestinationById($existingId);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

}
