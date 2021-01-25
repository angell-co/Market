<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\records;

use angellco\market\db\Table;
use craft\commerce\records\Order;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Order group / commerce order relationship record
 *
 * @property int $id
 * @property int $orderGroupId
 * @property int $orderId
 * @property ActiveQueryInterface $order
 * @property ActiveQueryInterface $orderGroup
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class OrderGroupCommerceOrder extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::ORDERGROUPS_COMMERCEORDERS;
    }

    /**
     * Returns the order group this relationship defines.
     *
     * @return ActiveQueryInterface
     */
    public function getOrderGroup(): ActiveQueryInterface
    {
        return $this->hasOne(OrderGroup::class, ['id' => 'orderGroupId']);
    }

    /**
     * Returns the commerce order this relationship defines.
     *
     * @return ActiveQueryInterface
     */
    public function getOrder(): ActiveQueryInterface
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}
