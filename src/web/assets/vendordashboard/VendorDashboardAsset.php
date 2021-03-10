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

        $this->js[] = 'js/datepicker.js';

        parent::init();
    }
}
