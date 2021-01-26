<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\controllers;

use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorDashboardController extends Controller
{
    /**
     * Shipping index
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/_index.html', [], View::TEMPLATE_MODE_CP);
    }
}
