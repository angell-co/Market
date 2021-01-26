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

use angellco\market\db\Table;
use angellco\market\models\OrderGroup;
use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\commerce\Plugin;
use craft\db\Query;
use craft\helpers\AdminTable;
use craft\web\Controller;
use craft\web\View;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class OrderGroupsController extends Controller
{
    /**
     * Shipping index
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('market/orders/_index.html', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * Endpoint for the order groups CP index table
     *
     * @return Response
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionOrdersTable(): Response
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $page = $request->getParam('page', 1);
        $sort = $request->getParam('sort', null);
        $limit = $request->getParam('per_page', 10);
        $search = $request->getParam('search', null);
        $offset = ($page - 1) * $limit;

        $query = (new Query());
        $query->select([
            '[[ordergroups.id]] as id',
            '[[ordergroups.customerId]] as customerId',
            '[[ordergroups.email]] as email',
            '[[ordergroups.dateOrdered]] as dateOrdered',
            'CONCAT([[billing.firstName]], " ", [[billing.lastName]]) AS billingName',
        ]);
        $query->from(Table::ORDERGROUPS . ' ordergroups');

        // Add on the first order relationship only so we can get the customer billing name etc
        $query->leftJoin(Table::ORDERGROUPS_COMMERCEORDERS . ' relatedorders', '[[relatedorders.orderGroupId]] = [[ordergroups.id]]');
        $query->leftJoin(CommerceTable::ORDERS . ' orders', '[[orders.id]] = [[relatedorders.orderId]]');
        $query->leftJoin(CommerceTable::ADDRESSES . ' billing', '[[billing.id]] = [[orders.billingAddressId]]');

        if ($search) {
            $likeOperator = Craft::$app->getDb()->getIsPgsql() ? 'ILIKE' : 'LIKE';
            $query->andWhere([
                'or',
                [$likeOperator, '[[ordergroups.email]]', $search],
                [$likeOperator, '[[ordergroups.dateOrdered]]', $search],
                [$likeOperator, '[[orders.reference]]', $search],
                [$likeOperator, '[[orders.number]]', $search],
                [$likeOperator, 'CONCAT([[billing.firstName]], " ", [[billing.lastName]])', $search],
            ]);
        }

        $query->groupBy('id');

        $total = $query->count();

        $query->offset($offset);
        $query->limit($limit);

        if ($sort) {
            [$sortField, $sortDir] = explode('|', $sort);
            if ($sortField && $sortDir) {
                $query->orderBy('[[' . $sortField . ']] ' . $sortDir);
            }
        } else {
            $query->orderBy('[[ordergroups.dateOrdered]] DESC');
        }

        $rows = [];
        foreach ($query->all() as $row) {

            // Make a model so we can use the methods on it
            $orderGroup = new OrderGroup([
                'id' => $row['id'],
                'customerId' => $row['customerId'],
                'email' => $row['email'],
                'dateOrdered' => $row['dateOrdered']
            ]);

            // Get all the orders off it
            $orders = [];
            foreach ($orderGroup->getOrders()->all() as $order) {
                $orders[] = [
                    'reference' => $order->reference,
                    'cpEditUrl' => $order->cpEditUrl,
                ];
            }

            $currency = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrency();

            $rows[] = [
                'id' => $orderGroup->id,
                'title' => $row['billingName'],
                'email' => $orderGroup->email,
                'dateOrdered' => $orderGroup->dateOrdered,
                'total' => Craft::$app->getFormatter()->asCurrency($orderGroup->getTotal(), $currency, [], [], true),
                'orders' => $orders
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }
}
