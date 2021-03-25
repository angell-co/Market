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

use angellco\market\helpers\ShippingProfileHelper;
use angellco\market\Market;
use angellco\market\services\Vendors;
use Craft;
use craft\base\ElementInterface;
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
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $components = [
            'vendors' => Vendors::class,
        ];

        $config['components'] = $components;

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->plugin = Market::$plugin;
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
}
