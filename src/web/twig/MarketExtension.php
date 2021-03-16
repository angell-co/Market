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

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class MarketExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @return MarketVariable[]|array
     */
    public function getGlobals(): array
    {
        return ['market' => new MarketVariable()];
    }
}
