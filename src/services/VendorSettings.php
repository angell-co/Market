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

use Craft;
use craft\base\Component;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorSettings extends Component
{

    public function getSettings()
    {
        Craft::dd('Oh HAI');

        // Get the last added settings record
        $record = Marketplace_VendorSettingsRecord::model()->find(array(
            'order' => 'id desc'
        ));

        // If there was one, populate a model
        if ($record)
        {
            $settings = Marketplace_VendorSettingsModel::populateModel($record);
        }
        else
        {
            $settings = new Marketplace_VendorSettingsModel();
        }

        return $settings;
    }

}
