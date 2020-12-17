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

use angellco\market\models\VendorSettings as VendorSettingsModel;
use angellco\market\records\VendorSettings as VendorSettingsRecord;
use craft\base\Component;

/**
 * Vendor settings service
 *
 * @property-read VendorSettingsModel $settings
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorSettings extends Component
{

    public function getSettings(): VendorSettingsModel
    {
        // Get the last added settings record
        $record = VendorSettingsRecord::find()->orderBy(['id' => SORT_DESC])->one();

        // If there was one, populate a model and return
        if ($record)
        {
            return new VendorSettingsModel($record->toArray([
                'id',
                'siteId',
                'volumeId',
                'fieldLayoutId',
                'shippingOriginId',
                'template',
                'urlFormat',
                'uid',
            ]));
        }

        // If not, return a fresh model
        return new VendorSettingsModel();
    }

}
