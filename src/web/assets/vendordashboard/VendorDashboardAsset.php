<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\web\assets\vendordashboard;

use craft\web\AssetBundle;

/**
 * Vendor dashboard asset bundle.
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorDashboardAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->css[] = 'css/main.css';

        $this->js[] = 'js/tabs.js';
        $this->js[] = 'js/titleField.js';
        $this->js[] = 'js/slugField.js';
        $this->js[] = 'js/textField.js';
        $this->js[] = 'js/skuField.js';
        $this->js[] = 'js/stockField.js';
        $this->js[] = 'js/assetField.js';
        $this->js[] = 'js/categoryField.js';
        $this->js[] = 'js/variantBlock.js';
        // TODO: append Field to these
        $this->js[] = 'js/lightswitch.js';
        $this->js[] = 'js/timepicker.js';
        $this->js[] = 'js/datepicker.js';

        parent::init();
    }
}
