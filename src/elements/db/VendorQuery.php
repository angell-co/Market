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

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorQuery extends ElementQuery
{
//    public $userId;
//
//    public function userId($value)
//    {
//        $this->userId = $value;
//
//        return $this;
//    }
//
//
//    protected function beforePrepare(): bool
//    {
//        $this->joinElementTable('market_vendors');
//
//        $this->query->select([
//            'products.price',
//            'products.currency',
//        ]);
//
//        if ($this->price) {
//            $this->subQuery->andWhere(Db::parseParam('products.price', $this->price));
//        }
//
//        if ($this->currency) {
//            $this->subQuery->andWhere(Db::parseParam('products.currency', $this->currency));
//        }
//
//        return parent::beforePrepare();
//    }
}
