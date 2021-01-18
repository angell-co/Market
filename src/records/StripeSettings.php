<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\records;

use angellco\market\db\Table;
use craft\db\ActiveRecord;

/**
 * Stripe Settings record
 *
 * @property int $id
 * @property int $siteId
 * @property string $clientId
 * @property string $secretKey
 * @property string $publishableKey
 * @property string $redirectSuccess
 * @property string $redirectError
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class StripeSettings extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::STRIPESETTINGS;
    }

}
