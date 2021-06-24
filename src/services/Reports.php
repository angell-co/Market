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

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\Address;
use craft\commerce\models\LineItem;
use League\Csv\CannotInsertRecord;
use League\Csv\Writer;
use yii\web\HttpException;

/**
 * Reports service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Reports extends Component
{

    const CSV_HEADER_ROW_ORDERS = [
        'Order Reference',
        'Order Status',
        'Customer Email',
        'Date Ordered',
        'Shipping Profile',
        'Shipping Total',
        'Order Total',
        'Billing Name',
        'Billing Address1',
        'Billing Address2',
        'Billing City',
        'Billing Postal Code',
        'Billing Province',
        'Billing Country',
        'Shipping Name',
        'Shipping Address1',
        'Shipping Address2',
        'Shipping City',
        'Shipping Postal Code',
        'Shipping Province',
        'Shipping Country',
    ];

    const CSV_HEADER_ROW_ORDERS_LINEITEMS = [
        'Line Item',
        'SKU',
        'Qty',
        'Sub Total',
        'Notes',
    ];

    /**
     * @param array $orderIds
     * @param string $format
     * @param Vendor|null $vendor
     * @return bool|Writer
     * @throws CannotInsertRecord
     * @throws HttpException
     */
    public function createOrdersCsv(array $orderIds = [], string $format = 'standard', Vendor $vendor = null)
    {
        if (!$orderIds) {
            return false;
        }

        // Check we’ve been given a vendor or are logged in as one
        if (!$vendor && !$vendor = Market::$plugin->getVendors()->getCurrentVendor()) {
            throw new HttpException(403, Craft::t('market', 'Sorry you must be a Vendor to perform this action.'));
        }

        $query = Order::find()
            ->anyStatus()
            ->isCompleted(true)
            ->limit(null)
            ->id($orderIds)
            ->orderBy('dateOrdered desc')
            ->relatedTo([
                'targetElement' => $vendor->id,
                'field' => 'vendor'
            ]);

        // Make the row data
        $rows = [];
        if ($format === 'expanded') {
            /** @var Order $order */
            foreach ($query->all() as $index => $order) {
                foreach ($order->getLineItems() as $lineItem) {
                    $rows[] = $this->_expandedOrderRow($order, $lineItem);
                }
            }
        } else {
            /** @var Order $order */
            foreach ($query->all() as $index => $order) {
                $rows[] = $this->_standardOrderRow($order);
            }
        }

        // Create a CSV
        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        // Headers
        if ($format === 'expanded') {
            $csv->insertOne(array_merge(self::CSV_HEADER_ROW_ORDERS, self::CSV_HEADER_ROW_ORDERS_LINEITEMS));
        } else {
            $csv->insertOne(self::CSV_HEADER_ROW_ORDERS);
        }

        // Insert rows and return
        $csv->insertAll($rows);
        return $csv;
    }

    private function _standardOrderRow(Order $order)
    {
        $shippingProfile = null;
        foreach ($order->getAdjustments() as $adjustment) {
            if ($adjustment->type === 'Shipping') {
                $shippingProfile = $adjustment->getAttributes();
                break;
            }
        }

        $billingAddress= $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        return [
            $order->reference,
            $order->getOrderStatus()->name,
            $order->email,
            $order->dateOrdered->format(\DateTimeInterface::RFC3339),
            $shippingProfile ? $shippingProfile['name'] : '',
            $order->totalShippingCostAsCurrency,
            $order->totalPriceAsCurrency,
            $billingAddress ? $billingAddress->firstName.' '.$billingAddress->lastName : '',
            $billingAddress ? $billingAddress->address1 : '',
            $billingAddress ? $billingAddress->address2 : '',
            $billingAddress ? $billingAddress->city : '',
            $billingAddress ? $billingAddress->zipCode : '',
            $billingAddress ? $billingAddress->getStateText() : '',
            $billingAddress ? $billingAddress->getCountryText() : '',
            $shippingAddress ? $shippingAddress->firstName.' '.$shippingAddress->lastName : '',
            $shippingAddress ? $shippingAddress->address1 : '',
            $shippingAddress ? $shippingAddress->address2 : '',
            $shippingAddress ? $shippingAddress->city : '',
            $shippingAddress ? $shippingAddress->zipCode : '',
            $shippingAddress ? $shippingAddress->getStateText() : '',
            $shippingAddress ? $shippingAddress->getCountryText() : '',
        ];
    }

    private function _expandedOrderRow(Order $order, LineItem $lineItem)
    {
        return array_merge($this->_standardOrderRow($order), [
            $lineItem->getDescription(),
            $lineItem->getSku(),
            $lineItem->qty,
            $lineItem->subtotalAsCurrency,
            $lineItem->note,
        ]);
    }

}
