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
use craft\web\Controller;
use League\Csv\Writer;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
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

        $statusId = $this->request->getRequiredParam('statusId');
        $orderIds = $this->request->getRequiredParam('orderIds');
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

    public function actionRefund()
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

            // Create the transaction
            $parentTransaction = $order->getLastTransaction();
            $transaction = Commerce::getInstance()->getTransactions()->createTransaction($order, $parentTransaction, TransactionRecord::TYPE_REFUND);
            $transaction->response = $refund;
            $transaction->reference = $refund->id;
            Commerce::getInstance()->getTransactions()->saveTransaction($transaction);

            // Change the status of the order
            $order->orderStatusId = $orderStatusId;
            Craft::$app->getElements()->saveElement($order);

        } catch (ApiErrorException $e) {
            // TODO
        } catch (TransactionException $e) {
        } catch (ElementNotFoundException $e) {
        } catch (Exception $e) {
        } catch (\Throwable $e) {
        }

        return true;
    }

}
