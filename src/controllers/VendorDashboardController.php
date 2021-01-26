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

    /**
     * Orders index
     *
     * @return Response
     */
    public function actionOrdersIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/orders/_index.html', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * Products index
     *
     * @return Response
     */
    public function actionProductsIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/products/_index.html', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * Files index
     *
     * @return Response
     */
    public function actionFilesIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/files/_index.html', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * Shipping index
     *
     * @return Response
     */
    public function actionShippingIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/shipping/_index.html', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * Reports index
     *
     * @return Response
     */
    public function actionReportsIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/reports/_index.html', [], View::TEMPLATE_MODE_CP);
    }
}
