<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\models;

use angellco\market\db\Table;
use angellco\market\records\OrderGroup as OrderGroupRecord;
use Craft;
use craft\base\Model;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\commerce\models\Customer;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;

/**
 * Order group model
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class OrderGroup extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int Commerce customer ID
     */
    public $customerId;

    /**
     * @var string Order email
     */
    public $email;

    /**
     * @var \DateTime Date ordered
     */
    public $dateOrdered;

    /**
     * @var Customer|null
     */
    private $_customer;

    /**
     * @var OrderQuery
     */
    private $_orders;

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['customerId', 'email', 'dateOrdered'], 'required'];

        return $rules;
    }

    /**
     * Returns the customer this order group belongs to
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        if (!$this->_customer) {
            $this->_customer = Commerce::getInstance()->getCustomers()->getCustomerById($this->customerId);
        }

        return $this->_customer;
    }

    /**
     * Returns the orders this group has
     *
     * @return OrderQuery|ElementQuery|ElementQueryInterface
     */
    public function getOrders()
    {
        if (!$this->_orders) {
            $query = (new Query());
            $query->select('orderId');
            $query->from(Table::ORDERGROUPS_COMMERCEORDERS);
            $query->where(['orderGroupId' => $this->id]);
            $orderIds = $query->column();

            $this->_orders = Order::find()->id($orderIds);
        }

        return $this->_orders;
    }

    /**
     * Returns the sum of all the orders’ storedTotalPrice attributes
     *
     * @return float|int
     */
    public function getTotal()
    {
        $total = 0;

        /** @var Order $order */
        foreach ($this->getOrders()->all() as $order) {
            $total += $order->storedTotalPrice;
        }

        return $total;
    }

// TODO Audit these

//    /**
//     * @return array
//     */
//    public function getNumbers()
//    {
//        $return = [];
//
//        /** @var Commerce_OrderModel $order */
//        foreach ($this->getOrders() as $order) {
//            $return[] = $order->number;
//        }
//
//        return $return;
//    }
//
//
//
//    /**
//     * @return float|int
//     */
//    public function getTotalShipping()
//    {
//        $total = 0;
//
//        /** @var Commerce_OrderModel $order */
//        foreach ($this->getOrders() as $order) {
//            $total += $order->totalShippingCost;
//        }
//
//        return $total;
//    }
//
//    /**
//     * @return float|int
//     */
//    public function getTotalCommission()
//    {
//        $total = 0;
//
//        /** @var Commerce_OrderModel $order */
//        foreach ($this->getOrders() as $order) {
//            $itemSubtotal = craft()->commerce_paymentCurrencies->convert($order->itemSubtotal, $order->paymentCurrency);
//            $total += (0.2 * $itemSubtotal);
//        }
//
//        return $total;
//    }

}
