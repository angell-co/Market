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

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\errors\TransactionException;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\web\Controller;
use League\Csv\Writer;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;

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
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function actionSetStatus(): bool
    {
        $this->requirePostRequest();
        $elementsService = Craft::$app->getElements();

        $statusId = $this->request->getRequiredParam('statusId');
        $orderIds = $this->request->getRequiredParam('orderIds');
        $orderIds = explode(',', $orderIds);

        $query = Order::find()
            ->anyStatus()
            ->limit(null)
            ->id($orderIds);


        foreach ($query->all() as $order) {
            $order->orderStatusId = $statusId;
            $elementsService->saveElement($order, false);
        }

        return true;
    }

    /**
     * Creates a CSV of the given order IDs and downloads them.
     *
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionExport()
    {
        $filename = 'cheerfully-given-orders';

        $orderIds = $this->request->getRequiredParam('orderIds');
        $orderIds = explode(',', $orderIds);

        $format = $this->request->getRequiredParam('format');

        /** @var Writer $csv */
        $csv = Market::$plugin->reports->createOrdersCsv($orderIds, $format);

        if ($format === 'expanded') {
            $filename .= '_expanded';
        }

        $csv->output($filename.'.csv');

        return $this->response->sendAndClose();
    }

    /**
     * Refunds an order in full - always returns true.
     *
     * Ideal for Sprig.
     *
     * @return bool
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws SiteNotFoundException
     * @throws TransactionException
     * @throws \Throwable
     */
    public function actionRefund(): bool
    {
        $this->requirePostRequest();
        $chargeId = $this->request->getRequiredParam('chargeId');
        $orderId = $this->request->getRequiredParam('orderId');
        $orderStatusId = $this->request->getRequiredParam('orderStatusId');

        // Get the order
        $query = Order::find()
            ->anyStatus()
            ->id($orderId);

        $order = $query->one();

        // Get the vendor
        /** @var Vendor $vendor */
        $vendor = Market::$plugin->getVendors()->getCurrentVendor();

        $settings = Market::$plugin->getStripeSettings()->getSettings();
        $stripe = new StripeClient($settings->secretKey);

        try {
            // Process the refund
            $refund = $stripe->refunds->create([
                'charge' => $chargeId,
                'refund_application_fee' => true,
            ], [
                'stripe_account' => $vendor->stripeUserId
            ]);

            $this->_createRefundOnOrder($order, $orderStatusId, $refund);

        } catch (ApiErrorException $e) {
            if ($e->getStripeCode() === 'charge_already_refunded') {
                $this->_createRefundOnOrder($order, $orderStatusId);
            }
        }

        return true;
    }

    /**
     * Adds the transaction and updates the order status on a refunded order.
     *
     * @param $order
     * @param $orderStatusId
     * @param null $refund
     * @return |null
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws TransactionException
     * @throws \Throwable
     */
    private function _createRefundOnOrder($order, $orderStatusId, $refund = null): void
    {
        // Create the transaction
        $parentTransaction = $order->getLastTransaction();
        if (!$parentTransaction) {
            return;
        }

        $transaction = Commerce::getInstance()->getTransactions()->createTransaction($order, $parentTransaction, TransactionRecord::TYPE_REFUND);
        $transaction->status = TransactionRecord::STATUS_SUCCESS;

        // Only refund in full for now
        $transaction->paymentAmount = $parentTransaction->getRefundableAmount();

        // Calculate amount in the primary currency
        $transaction->amount = $transaction->paymentAmount / $parentTransaction->paymentRate;

        // Add the refund data if we have it
        if ($refund) {
            $transaction->response = $refund;
            $transaction->reference = $refund->id;
        }

        Commerce::getInstance()->getTransactions()->saveTransaction($transaction);

        // Change the status of the order
        $order->orderStatusId = $orderStatusId;
        Craft::$app->getElements()->saveElement($order);
    }

}
