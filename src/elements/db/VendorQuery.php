<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\elements\db;

use angellco\market\elements\Vendor;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorQuery extends ElementQuery
{
    public $suspended;
    public $pending;
//
    public function suspended($value)
    {
        $this->suspended = $value;
        return $this;
    }

    public function pending($value)
    {
        $this->pending = $value;
        return $this;
    }

//
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('market_vendors');

        $this->query->select([
            'market_vendors.id',
//            'market_vendors.userId',
            'market_vendors.suspended',
            'market_vendors.pending',
        ]);


//        if ($this->suspended) {
//            $this->subQuery->andWhere(Db::parseParam('market_vendors.suspended', $this->suspended));
//        }
//
//        if ($this->pending) {
//            $this->subQuery->andWhere(Db::parseParam('market_vendors.pending', $this->pending));
//        }

//        $this->emulateExecution = true;

//        \Craft::dd($this->all());
//

//
//        if ($this->currency) {
//            $this->subQuery->andWhere(Db::parseParam('products.currency', $this->currency));
//        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status)
    {
        switch ($status) {
            case Vendor::STATUS_SUSPENDED:
                return [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                    'market_vendors.suspended' => true
                ];

            case Vendor::STATUS_PENDING:
                return [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                    'market_vendors.suspended' => false,
                    'market_vendors.pending' => true
                ];

            case Vendor::STATUS_ACTIVE:
                return [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                    'market_vendors.suspended' => false,
                    'market_vendors.pending' => false
                ];

            default:
                return parent::statusCondition($status);
        }
    }
}
