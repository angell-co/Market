<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\helpers\Currency;
use craft\commerce\Plugin as Commerce;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\errors\MissingComponentException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\ServerErrorHttpException;

/**
 * Carts service. This manages the cart(s) currently in the session, this service should mainly be used by web controller actions.
 *
 * @property int[] $cartTotals
 * @property bool|Order[] $carts
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Carts extends Component
{
    /**
     * Commerce session key for storing the cart number.
     *
     * @var string
     */
    protected $commerceCartName = 'commerce_cart';

    /**
     * Market session key for storing an array of cart numbers, one for each vendor.
     *
     * @var string
     */
    protected $marketCartsName = 'market_carts';

    /**
     * An array of Orders which represents all our carts for the
     * current session.
     *
     * @var array
     */
    private $_carts;

    /**
     * @var Commerce|mixed|null
     */
    private $_commerce;

    /**
     * @throws ServerErrorHttpException
     */
    public function init(): void
    {
        parent::init();

        $this->_commerce = Commerce::getInstance();
        if (!$this->_commerce) {
            throw new ServerErrorHttpException('Commerce platform unavailable.');
        }
    }

    /**
     * Returns an array of Orders that represent all the carts
     * for this session, keyed by the vendor ID.
     *
     * @param bool $asArray
     * @return Order[]|array
     * @throws MissingComponentException
     */
    public function getCarts($asArray = false): ?array
    {
        if (!$this->_carts) {

            $session = Craft::$app->getSession();
            $marketCartNumbers = $session->get($this->marketCartsName);

            if (!$marketCartNumbers) {
                return [];
            }

            $this->_carts = [];

            foreach ($marketCartNumbers as $cartKey => $cartNumber) {

                $cart = $this->_commerce->getOrders()->getOrderByNumber($cartNumber);

                if ($cart === null) {
                    continue;
                }

                // If the cart is already completed, trashed or has no line items, forget the cart and start again.
                if ($cart->isCompleted || $cart->trashed || !$cart->getLineItems()) {

                    // Remove the reference to it from our cookie
                    unset($marketCartNumbers[$cartKey]);
                    $session->set($this->marketCartsName, $marketCartNumbers);

                    // Make sure commerce forgets it
                    $this->_commerce->getCarts()->forgetCart();
                    $this->_commerce->getCustomers()->forgetCustomer();

                    continue;
                }

                $this->_carts[$cartKey] = $cart;
            }
        }

        if ($asArray) {
            return array_map([$this, '_cartToArray'], $this->_carts);
        }

        return $this->_carts;
    }

    /**
     * Returns the totals of all the carts in the current session.
     *
     * @return array|int[]
     * @throws MissingComponentException
     */
    public function getCartTotals(): array
    {
        $itemSubtotal = 0;
        $totalQty = 0;
        $totalPrice = 0;
        $totalShippingCost = 0;

        /** @var Order $cart */
        foreach ($this->getCarts() as $cart) {
            $itemSubtotal += $cart->itemSubtotal;
            $totalQty += $cart->totalQty;
            $totalPrice += $cart->totalPrice;
            $totalShippingCost += $cart->totalShippingCost;
        }

        // Round the resulting currency values
        $itemSubtotal = Currency::round($itemSubtotal);
        $totalPrice = Currency::round($totalPrice);
        $totalShippingCost = Currency::round($totalShippingCost);

        return [
            'itemSubtotal' => $itemSubtotal,
            'itemSubtotalAsCurrency' => Currency::formatAsCurrency($itemSubtotal, null, false, true, true),
            'totalQty' => $totalQty,
            'totalPrice' => $totalPrice,
            'totalPriceAsCurrency' => Currency::formatAsCurrency($totalPrice, null, false, true, true),
            'totalShippingCost' => $totalShippingCost,
            'totalShippingCostAsCurrency' => Currency::formatAsCurrency($totalShippingCost, null, false, true, true),
        ];
    }

    /**
     * Returns the cart as an array ready for use in the API.
     *
     * TODO: refactor out the custom field stuff into events or something
     *
     * @param Order $cart
     * @return array
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     */
    private function _cartToArray(Order $cart): array
    {
        $array = $cart->toArray();

        $array['lineItems'] = [];

        foreach ($cart->getLineItems() as $lineItem) {

            /** @var Variant $variant */
            $variant = $lineItem->getPurchasable();
            $product = $variant->getProduct();
            $img = $product->getFieldValue('primaryImage')[0] ?? null;
            $imgUrl = $img->getUrl([
                'w' => 200,
                'h' => 200,
                'fit' => 'crop',
                'crop' => 'focalpoint',
                'auto' => 'format,compress'
            ]);

            $array['lineItems'][] = array_merge($lineItem->toArray(), [
                'purchasable' => [
                    'title' => $variant->title,
                    'sku' => $variant->sku,
                    'minQty' => $variant->minQty,
                    'maxQty' => $variant->maxQty,
                    'hasUnlimitedStock' => $variant->hasUnlimitedStock,
                    'stock' => $variant->stock,
                ],
                'product' => [
                    'title' => $product->title,
                    'url' => $product->getUrl(),
                    'image' => $imgUrl,
                    'hasVariants' => (bool) $product->getType()->hasVariants
                ]
            ]);
        }

        $array['vendor'] = $cart->getAttachedVendor()->toArray([
            'id',
            'title',
            'url'
        ]);

        return $array;
    }

    /**
     * Switches the active cart to one specific for a given vendor and stores
     * the cart numbers in a cookie so we can keep track of them.
     *
     * @param int $vendorId
     * @return bool
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws MissingComponentException
     * @throws \Throwable
     */
    public function switchCart(int $vendorId): bool
    {
        $cartKey = 'vendor-'.$vendorId;

        $session = Craft::$app->getSession();
        $marketCartNumbers = $session->get($this->marketCartsName);

        $commerceCustomers = $this->_commerce->getCustomers();
        $commerceCarts = $this->_commerce->getCarts();

        // If there aren’t any vendor carts stored, then this is the first time
        // we have added something so therefore we can simply use the current
        // cart and store the vendor ID against it.
        if (!$marketCartNumbers) {
            $commerceCarts->forgetCart();
            $commerceCustomers->forgetCustomer();
            $cart = $commerceCarts->getCart();

            // Set vendor on cart
            $cart->setFieldValue('vendor', [$vendorId]);
            if (!Craft::$app->getElements()->saveElement($cart, false)) {
//                TODO throw error
            }

            $session->set($this->marketCartsName, [$cartKey => $cart->number]);

            return true;
        }

        // So, we have some vendor specific carts in play - but do we have any
        // for _this_ vendor?
        if (isset($marketCartNumbers[$cartKey])) {

            // Get the cart
            $cartNumber = $marketCartNumbers[$cartKey];
            $currentCart = Order::find()->number($cartNumber)->trashed(null)->anyStatus()->one();

            if ($currentCart) {

                // So the cart exists which means we can forget the current one
                $commerceCarts->forgetCart();

                // Add vendor one to the session for commerce to pick up
                $session->set($this->commerceCartName, $currentCart->number);

                // Finally, load it into commerce
                $cart = $commerceCarts->getCart();

//                // Also add on the addresses
//                $shippingAddress = $currentCart->getShippingAddress();
//                $billingAddress = $currentCart->getBillingAddress();
//
//                // If we don’t have any, check the other carts
//                if (!$shippingAddress || !$billingAddress) {
//                    $allCarts = $this->getVendorCarts();
//                    foreach ($allCarts as $allCart) {
//                        $shippingAddress = $allCart->getShippingAddress();
//                        $billingAddress = $allCart->getBillingAddress();
//
//                        if ($shippingAddress && $billingAddress) {
//                            break;
//                        }
//                    }
//                }
//
//                if ($shippingAddress) {
//                    $cart->setAttribute('shippingAddressId', $shippingAddress->id);
//                }
//
//                if ($billingAddress) {
//                    $cart->setAttribute('billingAddressId', $billingAddress->id);
//                }
//
//
                // Finally, set the vendor on it
                $cart->setFieldValue('vendor', [$vendorId]);
                if (!Craft::$app->getElements()->saveElement($cart, false)) {
//                TODO throw error
                }

                return true;
            }
        }

        // We have vendor specific carts present, but not one for _this_
        // vendor so we can forget the current cart, get a new one and store it
        // against this vendor.
        $commerceCarts->forgetCart();
        $cart = $commerceCarts->getCart();

        $marketCartNumbers = array_merge($marketCartNumbers, [$cartKey => $cart->number]);
        $session->set($this->marketCartsName, $marketCartNumbers);


//        // Given this is a new cart, there won’t be any addresses on it,
//        // _but_ the user may have already added them to another cart - so lets
//        // get and set them
//        $allCarts = $this->getVendorCarts();
//        $shippingAddress = null;
//        $billingAddress = null;
//        /** @var Commerce_OrderModel $allCart */
//        foreach ($allCarts as $allCart) {
//            $shippingAddress = $allCart->getShippingAddress();
//            $billingAddress = $allCart->getBillingAddress();
//
//            if ($shippingAddress && $billingAddress) {
//                break;
//            }
//        }
//
//        if ($shippingAddress) {
//            $cart->setAttribute('shippingAddressId', $shippingAddress->id);
//        }
//
//        if ($billingAddress) {
//            $cart->setAttribute('billingAddressId', $billingAddress->id);
//        }
//
//
        // Set the vendor on it
        $cart->setFieldValue('vendor', [$vendorId]);
        if (!Craft::$app->getElements()->saveElement($cart, false)) {
//                TODO throw error
        }

        return true;
    }

}
