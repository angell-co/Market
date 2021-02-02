<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace sprig\components\market;

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\commerce\elements\Order;
use craft\web\View;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\variables\PaginateVariable;

/**
 * Class Orders
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Orders extends Component
{
    /**
     * @var int
     */
    public $page;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var PaginateVariable
     */
    public $pageInfo;

    /**
     * @var Vendor
     */
    public $currentVendor;

    /**
     * @var string
     */
    public $sort;

    /**
     * @var string
     */
    public $sortDir;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $query;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->currentVendor = Market::$plugin->getVendors()->getCurrentVendor();
    }

    /**
     * @inheritdoc
     */
    public function render(): string
    {
        return Craft::$app->getView()->renderTemplate('market/vendor-dashboard/_components/orders-index', $this->getAttributes(), View::TEMPLATE_MODE_CP);
    }

}
