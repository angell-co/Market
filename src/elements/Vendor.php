<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\elements;

use Craft;
use craft\base\Element;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Vendor extends Element
{
    // Statuses
    // -------------------------------------------------------------------------

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING   = 'pending';

    // Public Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('market', 'Vendor');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('market', 'Vendors');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => Craft::t('market', 'Active'),
            self::STATUS_PENDING => Craft::t('market', 'Pending'),
            self::STATUS_SUSPENDED => Craft::t('market', 'Suspended'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled')
        ];
    }

}
