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
use angellco\market\models\StripeSettings;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\errors\CurrencyException;
use craft\commerce\Plugin as Commerce;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use function Arrayy\array_first;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class PaymentsController extends Controller
{

    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * @var StripeSettings|mixed
     */
    private $_settings;

    /**
     * Sets up the stripe customer and SetupIntent
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidFieldException
     * @throws MissingComponentException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws SiteNotFoundException
     * @throws Exception
     */
    public function actionSetup(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $this->_settings = Market::$plugin->getStripeSettings()->getSettings();
        Stripe::setApiKey($this->_settings->secretKey);

        // Get all the carts
        $carts = Market::$plugin->getCarts()->getCarts();
        /** @var Order $firstCart */
        $firstCart = ArrayHelper::firstValue($carts);

        // Get the billing address
        $billingAddress = $firstCart->getBillingAddress();
        if (!$billingAddress) {
            return $this->asErrorJson('Sorry there was a problem with your addresses, please check them and try again.');
        }

        // See if we have a stripe customer ID already
        $stripeCustomerId = null;
        $user = $firstCart->getCustomer()->getUser();
        if ($user) {
            $stripeCustomerId = $user->getFieldValue('stripeCustomerId');
        }

        // If we do, check it
        $stripeCustomer = null;
        if ($stripeCustomerId) {
            try {
                $stripeCustomer = Customer::retrieve($stripeCustomerId);

                // If that customer was deleted, set it to false so we create a new one
                if ($stripeCustomer->isDeleted()) {
                    $stripeCustomer = false;
                }

            } catch (ApiErrorException $e) {
                // If this fails, we just want to carry on
            }
        }

        // If we still don’t have a stripe customer, create it
        if (!$stripeCustomer) {
            try {
                $stripeCustomer = Customer::create([
                    'email' => $firstCart->email,
                    'name' => $billingAddress->firstName . ' ' . $billingAddress->lastName
                ]);
            } catch (ApiErrorException $e) {
                return $this->asErrorJson($e->getMessage());
            }
        }

        // Now we can silently store the customer ID for future use if they are logged in
        if ($user && $stripeCustomerId !== $stripeCustomer->id) {
            $user->setFieldValue('stripeCustomerId', $stripeCustomer->id);
            /** @noinspection PhpUnhandledExceptionInspection */
            Craft::$app->getElements()->saveElement($user);
        }

        // Finally create the SetupIntent - this will allow us to create a saved payment method we can re-use for each order
        try {
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $stripeCustomer->id
            ]);

            return $this->asJson([
                'customerId' => $stripeCustomer->id,
                'clientSecret' => $setupIntent->client_secret
            ]);
        } catch (ApiErrorException $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     * @throws SiteNotFoundException
     */
    public function actionPay(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $this->_settings = Market::$plugin->getStripeSettings()->getSettings();
        Stripe::setApiKey($this->_settings->secretKey);

        // First up, save the payment method onto the stripe customer
        $stripeCustomerId = $this->request->getRequiredParam('customerId');
        $paymentMethodId = $this->request->getRequiredParam('paymentMethodId');

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomerId]);
        } catch (ApiErrorException $e) {
            return $this->asErrorJson($e->getMessage());
        }

        // If that worked we can start processing payments and completing carts
        foreach (Market::$plugin->getCarts()->getCarts() as $cart) {
            // TODO: this should use try catch, if _payCart can’t complete the payment then it should throw something so
            //       we can catch it and return here - or, it could always return an array - either success, needs action or error
            $this->_payCart($cart, $stripeCustomerId, $paymentMethodId);
        }

        return $this->asJson([
            'aha' => 'oho!'
        ]);
    }

    /**
     * This is essentially a cut-down version of the core commerce payments/pay action with no gateway and instead
     * our custom stripe bits inserted
     *
     * @param Order $order
     * @param $stripeCustomerId
     * @param $paymentMethodId
     * @return void|Response|null
     * @noinspection NullPointerExceptionInspection
     * @throws Exception
     * @throws \Throwable
     */
    private function _payCart(Order $order, $stripeCustomerId, $paymentMethodId)
    {
        $commerce = Commerce::getInstance();

        if (!$commerce->getSettings()->allowEmptyCartOnCheckout && $order->getIsEmpty()) {
            $error = Craft::t('commerce', 'Order can not be empty.');
            return $this->asErrorJson($error);
        }

        // Set if the customer should be registered on order completion
        if ($this->request->getBodyParam('registerUserOnOrderComplete')) {
            $order->registerUserOnOrderComplete = true;
        }

        if ($this->request->getBodyParam('registerUserOnOrderComplete') === 'false') {
            $order->registerUserOnOrderComplete = false;
        }

        // These are used to compare if the order changed during its final
        // recalculation before payment.
        $originalTotalPrice = $order->getOutstandingBalance();
        $originalTotalQty = $order->getTotalQty();
        $originalTotalAdjustments = count($order->getAdjustments());

        // Set payment currency.
        if ($paymentCurrency = $this->request->getParam('paymentCurrency')) {
            try {
                $order->setPaymentCurrency($paymentCurrency);
            } catch (CurrencyException $exception) {
                Craft::$app->getErrorHandler()->logException($exception);
                return $this->asErrorJson($exception->getMessage());
            }
        }

        // NOTE: Here we could submit payment source on the cart if we need to

        // Check email address exists on order.
        if (!$order->email) {
            $error = Craft::t('commerce', 'No customer email address exists on this cart.');
            return $this->asErrorJson($error);
        }

        // Does the order require shipping
        if ($commerce->getSettings()->requireShippingMethodSelectionAtCheckout && !$order->getShippingMethod()) {
            $error = Craft::t('commerce', 'There is no shipping method selected for this order.');
            return $this->asErrorJson($error);
        }

        // Do one final save to confirm the price does not change out from under the customer. Also removes any out of stock items etc.
        // This also confirms the products are available and discounts are current.
        $order->recalculate();

        // Save the orders new values.
        $totalPriceChanged = $originalTotalPrice != $order->getOutstandingBalance();
        $totalQtyChanged = $originalTotalQty != $order->getTotalQty();
        $totalAdjustmentsChanged = $originalTotalAdjustments != count($order->getAdjustments());

        $updateCartSearchIndexes = $commerce->getSettings()->updateCartSearchIndexes;
        $updateSearchIndex = ($order->isCompleted || $updateCartSearchIndexes);

        if (Craft::$app->getElements()->saveElement($order, true, false, $updateSearchIndex)) {
            // Has the order changed in a significant way?
            if ($totalPriceChanged || $totalQtyChanged || $totalAdjustmentsChanged) {
                if ($totalPriceChanged) {
                    $order->addError('totalPrice', Craft::t('commerce', 'The total price of the order changed.'));
                }

                if ($totalQtyChanged) {
                    $order->addError('totalQty', Craft::t('commerce', 'The total quantity of items within the order changed.'));
                }

                if ($totalAdjustmentsChanged) {
                    $order->addError('totalAdjustments', Craft::t('commerce', 'The total number of order adjustments changed.'));
                }

                $error = Craft::t('commerce', 'Something changed with the order before payment, please review your order and submit payment again.');
                return $this->asErrorJson($error);
            }
        }

        $transaction = null;

        // Make sure during this payment request the order does not recalculate.
        // We don't want to save the order in this mode in case the payment fails. The customer should still be able to edit and recalculate the cart.
        // When the order is marked as complete from a payment later, the order will be set to 'recalculate none' mode permanently.
        $order->setRecalculationMode(Order::RECALCULATION_MODE_NONE);

        // Get the vendor
        /** @noinspection PhpUndefinedMethodInspection */
        $vendor = $order->getAttachedVendor();
        if (!$vendor) {
            $error = Craft::t('market', 'There is no Vendor attached to this order so it cannot be processed.');
            return $this->asErrorJson($error);
        }

        // Check we have stripe details
        if (!$vendor->stripeUserId) {
            $error = Craft::t('market', 'Sorry this Vendor is unable to accept payments right now.');
            // TODO: MarketplacePlugin::log(Craft::t('Vendor {code} with id {id} isn’t connected to Stripe.', ['title'=>$vendor->code,'id'=>$vendor->id]), LogLevel::Info);
            return $this->asErrorJson($error);
        }

        // Convert amounts to pence
        $currencyIso = $order->getPaymentCurrency();
        $currency = $commerce->getPaymentCurrencies()->getPaymentCurrencyByIso($currencyIso);
        $itemSubtotalPence = $order->itemSubtotal * (10 ** $currency->minorUnit);
        $totalPricePence = $order->totalPrice * (10 ** $currency->minorUnit);

        // Figure out the commission fee of 20% of the item subtotal (excludes adjustments)
        $fee = floor($itemSubtotalPence * 20) / 100;

        // Final check for errors
        if (!$order->hasErrors()) {

            // If we have a payment intent, confirm that
            $paymentIntentId = $this->request->getParam('paymentIntentId');
            if ($paymentIntentId) {
                try {
                    // Set Stripe to use the connected account
                    Stripe::setApiKey($vendor->stripeAccessToken);

                    // Get the payment intent from connected account and confirm it
                    $intent = PaymentIntent::retrieve($paymentIntentId);
                    $intent->confirm();
                } catch (ApiErrorException $e) {
                    return $this->asErrorJson($e->getMessage());
                }

            // Otherwise initially try and process the payment method with
            // automatic confirmation
            } else {
                try {
                    Stripe::setApiKey($this->_settings->secretKey);

                    // Share the payment method with the connected account
                    $connectedAccountPaymentMethod = PaymentMethod::create([
                        'customer' => $stripeCustomerId,
                        'payment_method' => $paymentMethodId,
                    ], [
                        'stripe_account' => $vendor->stripeUserId
                    ]);

                    // Create the PaymentIntent on the connected account
                    $intent = PaymentIntent::create([
                        'amount' => $totalPricePence,
                        'currency' => strtolower($currencyIso),
                        'application_fee_amount' => $fee,
                        'confirm' => true,
                        'confirmation_method' => 'manual',
                        'statement_descriptor_suffix' => 'CG',
                        // Here we use the payment method specific to the connected account
                        'payment_method' => $connectedAccountPaymentMethod->id
                    ], [
                        'stripe_account' => $vendor->stripeUserId
                    ]);
                } catch (ApiErrorException $e) {
                    return $this->asErrorJson($e->getMessage());
                }
            }

            // If for some reason we don’t have any payment intents, throw an error
            if (!isset($intent)) {
                $error = Craft::t('market', 'No Payment Intent exists.');
                return $this->asErrorJson($error);
            }

            // Handle the payment response
            if ($intent->status === 'requires_action' && $intent->next_action->type === 'use_stripe_sdk') {
                // Tell the client to handle the action
                $this->returnJson([
                    'success' => false,
                    'requiresAction' => true,
                    'paymentIntentClientSecret' => $intent->client_secret,
                    'connectedAccountId' => $vendor->stripeUserId
                ]);

            // The payment didn’t need any additional actions and completed!
            } else if ($intent->status === 'succeeded') {

//                // At this point all the payment stuff has gone OK, so clear the
//                // payment method on the Stripe Customer if its the last cart
//                if ($lastCart) {
//                    Stripe::setApiKey($this->_secretKey);
//                    $platformPaymentMethod = PaymentMethod::retrieve($paymentMethodId);
//                    $platformPaymentMethod->detach();
//                }
//
//                // Make a transaction
//                $transaction = craft()->commerce_transactions->createTransaction($order);
//                $transaction->type = Commerce_TransactionRecord::TYPE_PURCHASE;
//
//                // If we set it to success then all sorts of errors pop up in the cp due to
//                // not having a payment method, so we leave it at pending
//                // $transaction->status = Commerce_TransactionRecord::STATUS_SUCCESS;
//                $transaction->response = JsonHelper::encode($intent->charges->data);
//
//                if (!craft()->commerce_transactions->saveTransaction($transaction)) {
//                    throw new Exception('Error saving transaction: ' . implode(', ', $transaction->getAllErrors()));
//                }
//
//                // Pay off and complete the order
//                $order->totalPaid = $order->totalPrice;
//                $order->datePaid = DateTimeHelper::currentTimeForDb();
//                $success = craft()->commerce_orders->completeOrder($order);
//
//                // Successfully completed the order
//                if ($success) {
//                    $this->returnJson([
//                        'success' => true,
//                        'orderNumber' => $order->number
//                    ]);
//                } else {
//                    $this->returnErrorJson(Craft::t('Sorry there was an internal error, please get in touch quoting #{number} to verify your order.', ['number' => $order->shortNumber]));
//                    return;
//                }
            } else {
                $error = Craft::t('market', 'Invalid PaymentIntent status');
                return $this->asErrorJson($error);
            }


        } else {
            $error = Craft::t('commerce', 'Invalid payment or order. Please review.');
            return $this->asErrorJson($error);
        }








//        if (!$success) {
//            if ($this->request->getAcceptsJson()) {
//                return $this->asJson([
//                    'error' => $error,
//                    'paymentFormErrors' => $paymentForm->getErrors(),
//                    $this->_cartVariableName => $this->cartArray($order)
//                ]);
//            }
//
//            $this->setFailFlash($error);
//
//            Craft::$app->getUrlManager()->setRouteParams(['paymentForm' => $paymentForm, $this->_cartVariableName => $order]);
//
//            return null;
//        }
//
//        if ($this->request->getAcceptsJson()) {
//            $response = [
//                'success' => true,
//                $this->_cartVariableName => $this->cartArray($order)
//            ];
//
//            if ($redirect) {
//                $response['redirect'] = $redirect;
//            }
//
//            if ($transaction) {
//                /** @var Transaction $transaction */
//                $response['transactionId'] = $transaction->reference;
//                $response['transactionHash'] = $transaction->hash;
//            }
//
//            return $this->asJson($response);
//        }
    }


}
