<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\records;

use angellco\market\db\Table;
use craft\commerce\records\Customer;
use craft\commerce\records\Order;
use craft\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;

/**
 * Order group record
 *
 * @property int $id
 * @property int $customerId
 * @property string $email
 * @property \DateTime $dateOrdered
 * @property ActiveQueryInterface $customer
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class OrderGroup extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::ORDERGROUPS;
    }

    /**
     * Returns the customer this order group belongs to.
     *
     * @return ActiveQueryInterface
     */
    public function getCustomer(): ActiveQueryInterface
    {
        return $this->hasOne(Customer::class, ['id' => 'customerId']);
    }

    /**
     * Returns the orders this group has.
     *
     * @return ActiveQueryInterface
     * @throws InvalidConfigException
     */
    public function getOrders(): ActiveQueryInterface
    {
        return $this->hasMany(Order::class, ['id' => 'orderId'])
            ->viaTable(Table::ORDERGROUPS_COMMERCEORDERS, ['orderGroupId' => 'id']);
    }
}
