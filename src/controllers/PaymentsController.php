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
use angellco\market\models\StripeSettings;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\errors\CurrencyException;
use craft\commerce\errors\PaymentException;
use craft\commerce\errors\TransactionException;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

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
        $stripe = new StripeClient($this->_settings->secretKey);

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
                $stripeCustomer = $stripe->customers->retrieve($stripeCustomerId);

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
                $stripeCustomer = $stripe->customers->create([
                    'email' => $firstCart->email,
                    'name' => $billingAddress->firstName . ' ' . $billingAddress->lastName,
                    'address' => [
                        'city' => $billingAddress->city,
                        'country' => $billingAddress->countryIso,
                        'line1' => $billingAddress->address1,
                        'postal_code' => $billingAddress->zipCode,
                        'state' => $billingAddress->stateText
                    ],
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

        // Finally create the SetupIntent - this will automatically attach the card
        // as a payment method to the platform customer
        try {
            $setupIntent = $stripe->setupIntents->create([
                'customer' => $stripeCustomer->id,
                'usage' => 'on_session'
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

        $paymentIntentId = $this->request->getParam('paymentIntentId');
        $stripeCustomerId = $this->request->getRequiredParam('customerId');
        $paymentMethodId = $this->request->getRequiredParam('paymentMethodId');

        // If that worked we can start processing payments and completing carts
        foreach (Market::$plugin->getCarts()->getCarts() as $cart) {
            try {
                $result = $this->_payCart($cart, $stripeCustomerId, $paymentMethodId, $paymentIntentId);

                // We can’t re-use the $paymentIntentId, so after the first attempt to pay
                // a cart we trash it
                $paymentIntentId = null;

                // Return to the client if we have an error
                if ($result['status'] === 'error') {
                    return $this->asErrorJson($result['message']);
                }

                // If the response requires action, we have to tell the client and come back to this order later
                if ($result['status'] === 'requires_action') {
                    return $this->asJson([
                        'status' => 'requires_action',
                        'payload' => $result['payload']
                    ]);
                }
            } catch (Exception $e) {
                return $this->asErrorJson($e->getMessage());
            } catch (\Throwable $e) {
                return $this->asErrorJson($e->getMessage());
            }
        }

        // We got this far, so all the carts have been paid and completed

        // Now we should clear the payment method on the platform customer
        $this->_settings = Market::$plugin->getStripeSettings()->getSettings();
        $stripe = new StripeClient($this->_settings->secretKey);
        try {
            $platformPaymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);
            $platformPaymentMethod->detach();
        } catch (ApiErrorException $e) {
            // Shhh ...
        }

        return $this->asJson([
            'status' => 'success',
        ]);
    }

    /**
     * This is essentially a cut-down version of the core commerce payments/pay action with no gateway and instead
     * our custom stripe bits inserted.
     *
     * It returns an array, which will have a status key with either error, requires_action or success as the value.
     *
     * @param Order $order
     * @param int $stripeCustomerId
     * @param int $paymentMethodId
     * @param null|int $paymentIntentId
     * @return array
     * @throws CurrencyException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws PaymentException
     * @throws SiteNotFoundException
     * @throws TransactionException
     * @throws \Throwable
     * @noinspection NullPointerExceptionInspection
     */
    private function _payCart(Order $order, $stripeCustomerId, $paymentMethodId, $paymentIntentId = null): array
    {
        $commerce = Commerce::getInstance();

        if (!$commerce->getSettings()->allowEmptyCartOnCheckout && $order->getIsEmpty()) {
            return [
                'status' => 'error',
                'message' => Craft::t('commerce', 'Order can not be empty.')
            ];
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
                return [
                    'status' => 'error',
                    'message' => $exception->getMessage()
                ];
            }
        }

        // NOTE: Here we could submit payment source on the cart if we need to

        // Check email address exists on order.
        if (!$order->email) {
            return [
                'status' => 'error',
                'message' => Craft::t('commerce', 'No customer email address exists on this cart.')
            ];
        }

        // Does the order require shipping
        if ($commerce->getSettings()->requireShippingMethodSelectionAtCheckout && !$order->getShippingMethod()) {
            return [
                'status' => 'error',
                'message' => Craft::t('commerce', 'There is no shipping method selected for this order.')
            ];
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

                return [
                    'status' => 'error',
                    'message' => Craft::t('commerce', 'Something changed with the order before payment, please review your order and submit payment again.')
                ];
            }
        }

        // Make sure during this payment request the order does not recalculate.
        // We don't want to save the order in this mode in case the payment fails. The customer should still be able to edit and recalculate the cart.
        // When the order is marked as complete from a payment later, the order will be set to 'recalculate none' mode permanently.
        $order->setRecalculationMode(Order::RECALCULATION_MODE_NONE);

        // Get the vendor
        /** @noinspection PhpUndefinedMethodInspection */
        /** @var Vendor $vendor */
        $vendor = $order->getAttachedVendor();
        if (!$vendor) {
            return [
                'status' => 'error',
                'message' => Craft::t('market', 'There is no Vendor attached to this order so it cannot be processed.')
            ];
        }

        // Check we have stripe details
        if (!$vendor->stripeUserId) {
            // TODO: MarketplacePlugin::log(Craft::t('Vendor {code} with id {id} isn’t connected to Stripe.', ['title'=>$vendor->code,'id'=>$vendor->id]), LogLevel::Info);
            return [
                'status' => 'error',
                'message' => Craft::t('market', 'Sorry this Vendor is unable to accept payments right now.')
            ];
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

            // We have to set a gateway otherwise the transactions will fail, so use the dummy one if needed
            $gateways = $commerce->getGateways()->getAllGateways();
            if (!$gateways) {
                throw new InvalidConfigException('No gateway set, at least one is required.');
            }

            $gateway = ArrayHelper::firstValue($gateways);
            $order->setGatewayId($gateway->id);

            // Make a transaction
            $transaction = $commerce->getTransactions()->createTransaction($order, null, TransactionRecord::TYPE_PURCHASE);

            // If we have a payment intent, confirm that
            if ($paymentIntentId) {
                try {
                    // Set Stripe to use the connected account
                    $stripe = new StripeClient($vendor->stripeAccessToken);

                    // Get the payment intent from connected account and confirm it
                    $intent = $stripe->paymentIntents->retrieve($paymentIntentId);
                    $intent->confirm();
                } catch (ApiErrorException $e) {
                    $transaction->status = TransactionRecord::STATUS_FAILED;
                    $transaction->message = $e->getMessage();

                    // If this transaction is already saved, don't even try.
                    if (!$transaction->id) {
                        $this->_saveTransaction($transaction);
                    }

                    return [
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }

            // Otherwise initially try and process the payment method with
            // automatic confirmation
            } else {
                try {
                    $this->_settings = Market::$plugin->getStripeSettings()->getSettings();
                    $stripe = new StripeClient($this->_settings->secretKey);

                    // Share the payment method with the connected account
                    $connectPaymentMethod = $stripe->paymentMethods->create([
                        'customer' => $stripeCustomerId,
                        'payment_method' => $paymentMethodId,
                    ], [
                        'stripe_account' => $vendor->stripeUserId
                    ]);

                    // Now we can finally create the PaymentIntent on the connected account
                    $intent = $stripe->paymentIntents->create([
                        'amount' => $totalPricePence,
                        'currency' => strtolower($currencyIso),
                        'application_fee_amount' => $fee,
                        'confirm' => true,
                        'confirmation_method' => 'manual',
                        'statement_descriptor_suffix' => 'CG',
                        // Here we use the payment method specific to the connected account
                        'payment_method' => $connectPaymentMethod->id
                    ], [
                        'stripe_account' => $vendor->stripeUserId
                    ]);
                } catch (ApiErrorException $e) {
                    $transaction->status = TransactionRecord::STATUS_FAILED;
                    $transaction->message = $e->getMessage();

                    // If this transaction is already saved, don't even try.
                    if (!$transaction->id) {
                        $this->_saveTransaction($transaction);
                    }

                    return [
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }

            // Handle the payment response
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            if ($intent->status === 'requires_action' && $intent->next_action->type === 'use_stripe_sdk') {
                $this->_updateTransaction($transaction, $intent);

                // Tell the client to handle the action
                return [
                    'status' => 'requires_action',
                    'payload' => [
                        'clientSecret' => $intent->client_secret,
                        'connectedAccountId' => $vendor->stripeUserId
                    ]
                ];
            }

            // The payment didn’t need any additional actions and completed!
            if ($intent->status === 'succeeded') {
                $this->_updateTransaction($transaction, $intent);

                if ($transaction->status !== TransactionRecord::STATUS_SUCCESS) {
                    throw new PaymentException($transaction->message);
                }

                // Success!
                $order->updateOrderPaidInformation();

                return [
                    'status' => 'success'
                ];
            }

            return [
                'status' => 'error',
                'message' => Craft::t('market', 'Invalid PaymentIntent status')
            ];
        }

        return [
            'status' => 'error',
            'message' => Craft::t('commerce', 'Invalid payment or order. Please review.')
        ];
    }


    /**
     * Save a transaction.
     *
     * @param Transaction $transaction
     * @throws TransactionException
     */
    private function _saveTransaction($transaction): void
    {
        if (!Commerce::getInstance()->getTransactions()->saveTransaction($transaction)) {
            throw new TransactionException('Error saving transaction: ' . implode(', ', $transaction->errors));
        }
    }

    /**
     * Updates a transaction.
     *
     * @param Transaction $transaction
     * @param PaymentIntent $intent
     * @throws TransactionException
     * @noinspection NullPointerExceptionInspection
     */
    private function _updateTransaction(Transaction $transaction, PaymentIntent $intent): void
    {
        if ($intent->status === 'succeeded') {
            $transaction->status = TransactionRecord::STATUS_SUCCESS;
        } elseif ($intent->status === 'processing') {
            $transaction->status = TransactionRecord::STATUS_PROCESSING;
        } elseif ($intent->status === 'requires_action') {
            $transaction->status = TransactionRecord::STATUS_REDIRECT;
        } else {
            $transaction->status = TransactionRecord::STATUS_FAILED;
        }

        // Get the response and remove the client secret
        $response = $intent->getLastResponse();
        $response->body = null;
        if ($response->json['client_secret']) {
            unset($response->json['client_secret']);
        }

        $transaction->response = $response;
        $transaction->code = $intent->getLastResponse()->code;
        $transaction->reference = $intent->id;
        $transaction->message = $intent->last_payment_error->message ?? '';

        $this->_saveTransaction($transaction);
    }

}
