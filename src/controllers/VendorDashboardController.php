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

use angellco\market\Market;
use angellco\market\models\ShippingProfile;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\web\Controller;
use craft\web\View;
use yii\web\NotFoundHttpException;
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
     * Edit order
     *
     * @param int|null $orderId
     * @param Order|null $order
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionEditOrder(int $orderId = null, Order $order = null): Response
    {
        // Sort out the main model
        if ($orderId !== null && $order === null) {
            $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);

            if (!$order) {
                throw new NotFoundHttpException('Order not found');
            }
        }

        return $this->renderTemplate('market/vendor-dashboard/orders/_edit.html', compact('order'), View::TEMPLATE_MODE_CP);
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

    /**
     * Settings index
     *
     * @return Response
     */
    public function actionSettingsIndex(): Response
    {
        return $this->renderTemplate('market/vendor-dashboard/settings/_index.html', [], View::TEMPLATE_MODE_CP);
    }
}
