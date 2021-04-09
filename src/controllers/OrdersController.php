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

use Craft;
use craft\commerce\elements\Order;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\BadRequestHttpException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class OrdersController extends Controller
{

    /**
     * Sets the status on orders - always returns true.
     *
     * Ideal for Sprig.
     *
     * @return bool
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function actionSetStatus(): bool
    {
        $this->requirePostRequest();
        $elementsService = Craft::$app->getElements();

        $statusId = Craft::$app->getRequest()->getRequiredParam('statusId');
        $orderIds = Craft::$app->getRequest()->getRequiredParam('orderIds');
        $orderIds = explode(',', $orderIds);

        $query = Order::find()
            ->anyStatus()
            ->limit(null)
            ->id($orderIds);

        foreach ($query->all() as $order) {
            $order->orderStatusId = $statusId;
            $elementsService->saveElement($order);
        }

        return true;
    }

}
