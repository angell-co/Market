<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2021 Angell & Co
 */

namespace angellco\market\fields;

use angellco\market\elements\Vendor;
use Craft;
use craft\fields\BaseRelationField;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Vendors extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('market', 'Vendors');
    }

    protected static function elementType(): string
    {
        return Vendor::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('market', 'Add a vendor');
    }
}
