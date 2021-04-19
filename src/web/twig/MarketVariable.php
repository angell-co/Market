<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\web\twig;

use angellco\market\db\Table;
use angellco\market\helpers\ShippingProfileHelper;
use angellco\market\Market;
use angellco\market\models\StripeSettings;
use angellco\market\services\ShippingProfiles;
use angellco\market\services\Vendors;
use Craft;
use craft\base\ElementInterface;
use craft\commerce\db\Table as CommerceTable;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\db\Table as CraftTable;
use yii\di\ServiceLocator;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class MarketVariable extends ServiceLocator
{
    /**
     * @var Market|mixed|object|null
     */
    public $plugin;

    /**
     * @var StripeSettings
     */
    private $_stripeSettings;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $components = [
            'vendors' => Vendors::class,
            'shippingProfiles' => ShippingProfiles::class,
        ];

        $config['components'] = $components;

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     *
     * @throws \craft\errors\SiteNotFoundException
     */
    public function init(): void
    {
        parent::init();

        $this->plugin = Market::$plugin;
        $this->_stripeSettings = $this->plugin->getStripeSettings()->getSettings();
    }

    /**
     * Prepares structured elements for use in fields.
     *
     * @param ElementInterface[] $elements
     * @param null $branchLimit
     * @return array
     */
    public function prepStructuredElementsForField(array $elements, $branchLimit = null): array
    {
        $structures = Craft::$app->getStructures();

        // Fill in the gaps
        $structures->fillGapsInElements($elements);

        // Enforce the branch limit
        if ($branchLimit) {
            $structures->applyBranchLimitToElements($elements, $branchLimit);
        }

        return $elements;
    }

    /**
     * @return ShippingProfileHelper
     */
    public function shippingProfileHelper(): ShippingProfileHelper
    {
        return new ShippingProfileHelper();
    }

    /**
     * @return string
     */
    public function getStripeClientId(): string
    {
        return $this->_stripeSettings->clientId;
    }

    /**
     * @return array
     */
    public function getOrderCountByStatus(): array
    {
        $vendorId = Market::$plugin->getVendors()->getCurrentVendor()->id;

        $countGroupedByStatusId = (new Query())
            ->select(['[[o.orderStatusId]]', 'count(o.id) as orderCount'])
            ->where(['[[o.isCompleted]]' => true, '[[e.dateDeleted]]' => null, '[[v.id]]' => $vendorId])
            ->from([CommerceTable::ORDERS . ' o'])
            ->innerJoin([CraftTable::ELEMENTS . ' e'], '[[o.id]] = [[e.id]]')
            ->innerJoin([CraftTable::RELATIONS . ' r'], '[[o.id]] = [[r.sourceId]]')
            ->innerJoin([Table::VENDORS . ' v'], '[[r.targetId]] = [[v.id]]')
            ->groupBy(['[[o.orderStatusId]]'])
            ->indexBy('orderStatusId')
            ->all();

        // For those not in the groupBy
        $allStatuses = Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses();
        foreach ($allStatuses as $status) {
            if (!isset($countGroupedByStatusId[$status->id])) {
                $countGroupedByStatusId[$status->id] = [
                    'orderStatusId' => $status->id,
                    'handle' => $status->handle,
                    'orderCount' => 0
                ];
            }

            // Make sure all have their handle
            $countGroupedByStatusId[$status->id]['handle'] = $status->handle;
        }

        return $countGroupedByStatusId;
    }
}
