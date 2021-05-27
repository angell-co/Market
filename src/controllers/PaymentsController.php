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
use Craft;
use craft\commerce\elements\Order;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;
use Stripe\Exception\ApiErrorException;
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

        $settings = Market::$plugin->getStripeSettings()->getSettings();
        \Stripe\Stripe::setApiKey($settings->secretKey);

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
                $stripeCustomer = \Stripe\Customer::retrieve($stripeCustomerId);

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
                $stripeCustomer = \Stripe\Customer::create([
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

        $settings = Market::$plugin->getStripeSettings()->getSettings();
        \Stripe\Stripe::setApiKey($settings->secretKey);

        // Get all the carts
        $carts = Market::$plugin->getCarts()->getCarts();
        /** @var Order $firstCart */
        $firstCart = ArrayHelper::firstValue($carts);

        // First up, save the payment method onto the stripe customer
        $stripeCustomerId = $this->request->getRequiredParam('customerId');
        $paymentMethodId = $this->request->getRequiredParam('paymentMethodId');

        try {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomerId]);
        } catch (ApiErrorException $e) {
            return $this->asErrorJson($e->getMessage());
        }

        // If that worked we can start processing payments and completing carts
        foreach ($carts as $cart) {

        }

        return $this->asJson([
            'aha' => 'oho!'
        ]);
    }

    // This is essentially a cut-down version of the core commerce payments/pay action
    private function _payCart($order)
    {
        if (!$plugin->getSettings()->allowEmptyCartOnCheckout && $order->getIsEmpty()) {
            $error = Craft::t('commerce', 'Order can not be empty.');

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => $error,
                    $this->_cartVariableName => $this->cartArray($order)
                ]);
            }

            $this->setFailFlash($error);

            return null;
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

        // Set guest email address onto guest customer and order.
        if ($paymentCurrency = $this->request->getParam('paymentCurrency')) {
            try {
                $order->setPaymentCurrency($paymentCurrency);
            } catch (CurrencyException $exception) {
                Craft::$app->getErrorHandler()->logException($exception);

                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'error' => $error,
                        $this->_cartVariableName => $this->cartArray($order)
                    ]);
                }

                $order->addError('paymentCurrency', $exception->getMessage());
                $this->setFailFlash($exception->getMessage());

                return null;
            }
        }

        // Set Payment Gateway on cart
        // Same as CartController::updateCart()
        if ($gatewayId = $this->request->getParam('gatewayId')) {
            if ($gateway = $plugin->getGateways()->getGatewayById($gatewayId)) {
                $order->setGatewayId($gatewayId);
            }
        }

        // Submit payment source on cart
        // See CartController::updateCart()
        if ($paymentSourceId = $this->request->getParam('paymentSourceId')) {
            if ($paymentSource = $plugin->getPaymentSources()->getPaymentSourceById($paymentSourceId)) {
                // The payment source can only be used by the same user as the cart's user.
                $cartUserId = $order->getUser() ? $order->getUser()->id : null;
                $paymentSourceUserId = $paymentSource->getUser() ? $paymentSource->getUser()->id : null;
                $allowedToUsePaymentSource = ($cartUserId && $paymentSourceUserId && $currentUser && $isSiteRequest && ($paymentSourceUserId == $cartUserId));
                if ($allowedToUsePaymentSource) {
                    $order->setPaymentSource($paymentSource);
                }
            }
        }

        // This will return the gateway to be used. The orders gateway ID could be null, but it will know the gateway from the paymentSource ID
        $gateway = $order->getGateway();

        if (!$gateway || !$gateway->availableForUseWithOrder($order)) {
            $error = Craft::t('commerce', 'There is no gateway or payment source available for use with this order.');

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => $error,
                    $this->_cartVariableName => $this->cartArray($order)
                ]);
            }

            if ($order->gatewayId) {
                $order->addError('gatewayId', $error);
            }

            if ($order->paymentSourceId) {
                $order->addError('paymentSourceId', $error);
            }

            $this->setFailFlash($error);

            return null;
        }

        // We need the payment form whether we are populating it from the request or from the payment source.
        $paymentForm = $gateway->getPaymentFormModel();

        /**
         *
         * Are we paying with:
         *
         * 1) The current order paymentSourceId
         * OR
         * 2) The current order gatewayId and a payment form populated from the request
         *
         */

        // 1) Paying with the current order paymentSourceId
        if ($order->paymentSourceId) {
            /** @var PaymentSource $paymentSource */
            $paymentSource = $order->getPaymentSource();
            if ($gateway->supportsPaymentSources()) {
                $paymentForm->populateFromPaymentSource($paymentSource);
            }
        }

        // 2) Paying with the current order gatewayId and a payment form populated from the request
        if ($order->gatewayId && !$order->paymentSourceId) {

            // Populate the payment form from the params
            $paymentForm->setAttributes($this->request->getBodyParams(), false);

            // Does the user want to save this card as a payment source?
            if ($currentUser && $this->request->getBodyParam('savePaymentSource') && $gateway->supportsPaymentSources()) {

                try {
                    $paymentSource = $plugin->getPaymentSources()->createPaymentSource($currentUser->id, $gateway, $paymentForm);
                } catch (PaymentSourceException $exception) {
                    Craft::$app->getErrorHandler()->logException($exception);

                    if ($this->request->getAcceptsJson()) {
                        return $this->asJson([
                            'error' => $exception->getMessage(),
                            'paymentFormErrors' => $paymentForm->getErrors(),
                            $this->_cartVariableName => $this->cartArray($order)
                        ]);
                    }

                    $this->setFailFlash($error);
                    Craft::$app->getUrlManager()->setRouteParams(['paymentForm' => $paymentForm, $this->_cartVariableName => $order]);

                    return null;
                }

                $order->setPaymentSource($paymentSource);
                $paymentForm->populateFromPaymentSource($paymentSource);
            }
        }

        // Allowed to update order's custom fields?
        if ($order->getIsActiveCart() || $userSession->checkPermission('commerce-manageOrders')) {
            $order->setFieldValuesFromRequest('fields');
        }

        // Check email address exists on order.
        if (!$order->email) {
            $error = Craft::t('commerce', 'No customer email address exists on this cart.');

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => $error,
                    'paymentFormErrors' => $paymentForm->getErrors(),
                    $this->_cartVariableName => $this->cartArray($order)
                ]);
            }

            $this->setFailFlash($error);
            Craft::$app->getUrlManager()->setRouteParams(['paymentForm' => $paymentForm, $this->_cartVariableName => $order]);

            return null;
        }

        // Does the order require shipping
        if ($plugin->getSettings()->requireShippingMethodSelectionAtCheckout && !$order->getShippingMethod()) {
            $error = Craft::t('commerce', 'There is no shipping method selected for this order.');

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => $error,
                    $this->_cartVariableName => $this->cartArray($order)
                ]);
            }

            $this->setFailFlash($error);
            Craft::$app->getUrlManager()->setRouteParams(compact('paymentForm'));

            return null;
        }

        // Save the return and cancel URLs to the order
        $returnUrl = $this->request->getValidatedBodyParam('redirect');
        $cancelUrl = $this->request->getValidatedBodyParam('cancelUrl');

        if ($returnUrl !== null && $cancelUrl !== null) {
            $view = $this->getView();
            $order->returnUrl = $view->renderObjectTemplate($returnUrl, $order);
            $order->cancelUrl = $view->renderObjectTemplate($cancelUrl, $order);
        }

        // Do one final save to confirm the price does not change out from under the customer. Also removes any out of stock items etc.
        // This also confirms the products are available and discounts are current.
        $order->recalculate();
        // Save the orders new values.

        $totalPriceChanged = $originalTotalPrice != $order->getOutstandingBalance();
        $totalQtyChanged = $originalTotalQty != $order->getTotalQty();
        $totalAdjustmentsChanged = $originalTotalAdjustments != count($order->getAdjustments());

        $updateCartSearchIndexes = Plugin::getInstance()->getSettings()->updateCartSearchIndexes;
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

                if ($this->request->getAcceptsJson()) {

                    return $this->asJson([
                        'error' => $error,
                        'paymentFormErrors' => $paymentForm->getErrors(),
                        $this->_cartVariableName => $this->cartArray($order)
                    ]);
                }

                $this->setFailFlash($error);
                Craft::$app->getUrlManager()->setRouteParams(['paymentForm' => $paymentForm, $this->_cartVariableName => $order]);

                return null;
            }
        }

        $redirect = '';
        $transaction = null;
        $paymentForm->validate();

        // Make sure during this payment request the order does not recalculate.
        // We don't want to save the order in this mode in case the payment fails. The customer should still be able to edit and recalculate the cart.
        // When the order is marked as complete from a payment later, the order will be set to 'recalculate none' mode permanently.
        $order->setRecalculationMode(Order::RECALCULATION_MODE_NONE);

        // set a partial payment amount on the order in the orders currency (not payment currency)
        $partialAllowed = (($this->request->isSiteRequest && Plugin::getInstance()->getSettings()->allowPartialPaymentOnCheckout) || $this->request->isCpRequest);

        if ($partialAllowed) {
            if ($isCpAndAllowed) {
                $paymentAmount = $this->request->getBodyParam('paymentAmount');
            } else {
                $paymentAmount = $this->request->getValidatedBodyParam('paymentAmount');
            }

            $order->setPaymentAmount($paymentAmount);
        }

        if (!$partialAllowed && $order->getPaymentAmount() < $order->getOutstandingBalance()) {
            $error = Craft::t('commerce', 'Partial payment not allowed.');
            $this->setFailFlash($error);
            Craft::$app->getUrlManager()->setRouteParams(['paymentForm' => $paymentForm, $this->_cartVariableName => $order]);

            return null;
        }

        if (!$paymentForm->hasErrors() && !$order->hasErrors()) {
            try {
                $plugin->getPayments()->processPayment($order, $paymentForm, $redirect, $transaction);
                $success = true;
            } catch (PaymentException $exception) {
                $error = $exception->getMessage();
                $success = false;
            }
        } else {
            $error = Craft::t('commerce', 'Invalid payment or order. Please review.');
            $success = false;
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
