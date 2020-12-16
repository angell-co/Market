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

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var int|null The user ID
     */
    public $userId;

    /**
     * @var bool Whether to return only suspended elements.
     * @used-by suspended()
     */
    public $suspended = false;

    /**
     * @var bool Whether to return only pending elements.
     * @used-by pending()
     */
    public $pending = false;


    // Element criteria parameter setters
    // -------------------------------------------------------------------------

    /**
     * Sets the [[$suspended]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @uses $suspended
     * @return static self reference
     */
    public function suspended(bool $value = true): VendorQuery
    {
        $this->suspended = $value;
        return $this;
    }

    /**
     * Sets the [[$pending]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @uses $pending
     * @return static self reference
     */
    public function pending(bool $value = true): VendorQuery
    {
        $this->pending = $value;
        return $this;
    }


    // Protected Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('market_vendors');

        $this->query->select([
            'market_vendors.id',
            'market_vendors.userId',
            'market_vendors.suspended',
            'market_vendors.pending',
        ]);

        if ($this->userId)
        {
            $this->subQuery->andWhere(Db::parseParam('market_vendors.userId', $this->userId));
        }

//

//
//        if ($this->currency) {
//            $this->subQuery->andWhere(Db::parseParam('products.currency', $this->currency));
//        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
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
