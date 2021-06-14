<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\validators;

use Craft;
use craft\commerce\Plugin as Commerce;
use yii\validators\Validator;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingDestinationValidator  extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $destinationModels = $model->$attribute;

        $usedZoneIds = [];
        foreach ($destinationModels as $destinationModel) {
            if (in_array($destinationModel->shippingZoneId, $usedZoneIds, true)) {
                $zone = Commerce::getInstance()->getShippingZones()->getShippingZoneById($destinationModel->shippingZoneId);
                $this->addError($model, $attribute, Craft::t('market', 'Zone “{name}” has already been used on this profile.', [
                    'name' => $zone->name
                ]));
            }
            $usedZoneIds[] = $destinationModel->shippingZoneId;
        }
    }
}
