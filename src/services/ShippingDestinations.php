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
use angellco\market\records\ShippingDestination as ShippingDestinationRecord;
use Craft;
use craft\base\Component;
use yii\web\NotFoundHttpException;

/**
 * Shipping destinations service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingDestinations extends Component
{

    // TODO: class caching?

    /**
     * Saves a shipping destination.
     *
     * @param ShippingDestination $model
     * @param bool $runValidation
     * @return bool
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function saveShippingDestination(ShippingDestination $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = ShippingDestinationRecord::findOne($model->id);

            if (!$record) {
                throw new NotFoundHttpException(Craft::t('market', 'No shipping destination exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new ShippingDestinationRecord();
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Shipping destination not saved due to validation error.', __METHOD__);

            return false;
        }

        $record->shippingProfileId = $model->shippingProfileId;
        $record->shippingZoneId = $model->shippingZoneId;
        $record->primaryRate = $model->primaryRate;
        $record->secondaryRate = $model->secondaryRate;
        $record->deliveryTime = $model->deliveryTime;

        $record->validate();
        $model->addErrors($record->getErrors());

        // TODO validate model?

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $model->id = $record->id;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

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
