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
use craft\commerce\controllers\CartController as CommerceCartController;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class CartController extends CommerceCartController
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * Switch carts then hit the Commerce update cart action
     *
     * @return void|Response|null
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws MissingComponentException
     * @throws Exception
     */
    public function actionUpdateCart(): ?Response
    {
        $this->requirePostRequest();

        $vendorId = $this->request->getRequiredParam('vendorId');

        // Switch the commerce cart around
        Market::$plugin->getCarts()->switchCart($vendorId);
        return parent::actionUpdateCart();
    }

    /**
     * Updates the order level data on all the carts in the session based on
     * the POST data. The following things are covered:
     *
     * - addresses
     * - email
     * - registerUserOnOrderComplete
     * - paymentCurrency
     * - couponCode
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws MissingComponentException
     * @throws \Throwable
     */
    public function actionUpdateCarts(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $updateCartSearchIndexes = Commerce::getInstance()->getSettings()->updateCartSearchIndexes;
        $elements = Craft::$app->getElements();
        $customer = Commerce::getInstance()->getCustomers()->getCustomer();

        // Get all the carts and loop over them
        $carts = Market::$plugin->getCarts()->getCarts();
        foreach ($carts as $cart) {

            $cart->setCustomer($customer);

            $this->_setAddresses($cart);

            // Set guest email address onto guest customers order.
            if (!$cart->getUser() && $email = $this->request->getParam('email')) {
                $cart->setEmail($email);
            }

            // Set if the customer should be registered on order completion
            if ($this->request->getBodyParam('registerUserOnOrderComplete')) {
                $cart->registerUserOnOrderComplete = true;
            }

            if ($this->request->getBodyParam('registerUserOnOrderComplete') === 'false') {
                $cart->registerUserOnOrderComplete = false;
            }

            // Set payment currency on cart
            if ($currency = $this->request->getParam('paymentCurrency')) {
                $cart->paymentCurrency = $currency;
            }

            // Set Coupon on Cart. Allow blank string to remove coupon
            if (($couponCode = $this->request->getParam('couponCode')) !== null) {
                $cart->couponCode = trim($couponCode) ?: null;
            }

            // Save, return if error
            if (!$cart->validate($cart->activeAttributes(), false) || !$elements->saveElement($cart, false, false, $updateCartSearchIndexes)) {
                $error = Craft::t('commerce', 'Unable to update cart.');
                $message = $this->request->getValidatedBodyParam('failMessage') ?? $error;

                return $this->asJson([
                    'error' => $error,
                    'errors' => $cart->getErrors(),
                    'success' => !$cart->hasErrors(),
                    'message' => $message,
                    $this->_cartVariable => $this->cartArray($cart)
                ]);
            }

        }

        // If we got this far then they all saved ok
        $cartUpdatedMessage = Craft::t('commerce', 'Cart updated.');
        $message = $this->request->getValidatedBodyParam('successMessage') ?? $cartUpdatedMessage;
        return $this->asJson([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Directly ported from the parent controller.
     *
     * @param Order $cart
     * @noinspection NullPointerExceptionInspection
     */
    private function _setAddresses(Order $cart): void
    {
        $addresses = Commerce::getInstance()->getAddresses();

        // Address updating
        $shippingIsBilling = $this->request->getParam('shippingAddressSameAsBilling');
        $billingIsShipping = $this->request->getParam('billingAddressSameAsShipping');
        $estimatedBillingIsShipping = $this->request->getParam('estimatedBillingAddressSameAsShipping');
        $shippingAddress = $this->request->getParam('shippingAddress');
        $estimatedShippingAddress = $this->request->getParam('estimatedShippingAddress');
        $billingAddress = $this->request->getParam('billingAddress');
        $estimatedBillingAddress = $this->request->getParam('estimatedBillingAddress');

        // Override billing address with a particular ID
        $shippingAddressId = $this->request->getParam('shippingAddressId');
        $billingAddressId = $this->request->getParam('billingAddressId');

        // Shipping address
        if ($shippingAddressId && !$shippingIsBilling) {
            $address = $addresses->getAddressByIdAndCustomerId($shippingAddressId, $cart->customerId);

            $cart->setShippingAddress($address);
        } else if ($shippingAddress && !$shippingIsBilling) {
            $cart->setShippingAddress($shippingAddress);
        }

        // Billing address
        if ($billingAddressId && !$billingIsShipping) {
            $address = $addresses->getAddressByIdAndCustomerId($billingAddressId, $cart->customerId);

            $cart->setBillingAddress($address);
        } else if ($billingAddress && !$billingIsShipping) {
            $cart->setBillingAddress($billingAddress);
        }

        // Estimated Shipping Address
        if ($estimatedShippingAddress) {
            if ($cart->estimatedShippingAddressId) {
                $address = $addresses->getAddressById($cart->estimatedShippingAddressId);
                $address->setAttributes($estimatedShippingAddress, false);
                $estimatedShippingAddress = $address;
            }

            $cart->setEstimatedShippingAddress($estimatedShippingAddress);
        }

        // Estimated Billing Address
        if ($estimatedBillingAddress && !$estimatedBillingIsShipping) {
            if ($cart->estimatedBillingAddressId && ($cart->estimatedBillingAddressId != $cart->estimatedShippingAddressId)) {
                $address = $addresses->getAddressById($cart->estimatedBillingAddressId);
                $address->setAttributes($estimatedBillingAddress, false);
                $estimatedBillingAddress = $address;
            }

            $cart->setEstimatedBillingAddress($estimatedBillingAddress);
        }

        $cart->billingSameAsShipping = (bool)$billingIsShipping;
        $cart->shippingSameAsBilling = (bool)$shippingIsBilling;
        $cart->estimatedBillingSameAsShipping = (bool)$estimatedBillingIsShipping;

        // Set primary addresses
        if ($this->request->getBodyParam('makePrimaryShippingAddress')) {
            $cart->makePrimaryShippingAddress = true;
        }

        if ($this->request->getBodyParam('makePrimaryBillingAddress')) {
            $cart->makePrimaryBillingAddress = true;
        }

        // Shipping
        if ($shippingAddressId && !$shippingIsBilling && $billingIsShipping) {
            $cart->billingAddressId = $shippingAddressId;
        }

        // Billing
        if ($billingAddressId && !$billingIsShipping && $shippingIsBilling) {
            $cart->shippingAddressId = $billingAddressId;
        }
    }
}
