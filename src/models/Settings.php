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
use Craft;
use craft\base\Model;

/**
 * Plugin Settings model
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Settings extends Model
{
    /**
     * @var int|null
     */
    public $vendorDashboardLogoId;

    /**
     * @var string
     */
    public $vendorDashboardTitle = 'Market';

    /**
     * @var string|null
     */
    public $applyToSell;

    /**
     * @var string|null
     */
    public $orderConfirmation;

    /**
     * @var string|null
     */
    public $googleAnalyticsUA;

    /**
     * @var array
     */
    public $productSidebarFieldHandles = [];

}
