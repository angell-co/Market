<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\helpers;

use Craft;

/**
 * Class ShippingProfileHelper
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingProfileHelper
{
    /**
     * Returns the shipping profile processing times formatted ready for an
     * options field.
     *
     * @return array|array[]
     */
    public static function processingTimeOptions(): array
    {
        return [
            '1_day' => ['label' => Craft::t('market', '1 Business Day'), 'value' => '1_day'],
            '1_2_days' => ['label' => Craft::t('market', '1-2 Business Days'), 'value' => '1_2_days'],
            '1_3_days' => ['label' => Craft::t('market', '1-3 Business Days'), 'value' => '1_3_days', 'default' => true],
            '3_5_days' => ['label' => Craft::t('market', '3-5 Business Days'), 'value' => '3_5_days'],
            '1_2_weeks' => ['label' => Craft::t('market', '1-2 Weeks'), 'value' => '1_2_weeks'],
            '2_3_weeks' => ['label' => Craft::t('market', '2-3 Weeks'), 'value' => '2_3_weeks'],
            '3_4_weeks' => ['label' => Craft::t('market', '3-4 Weeks'), 'value' => '3_4_weeks'],
            '4_6_weeks' => ['label' => Craft::t('market', '4-6 Weeks'), 'value' => '4_6_weeks'],
            '6_8_weeks' => ['label' => Craft::t('market', '6-8 Weeks'), 'value' => '6_8_weeks'],
            'unknown' => ['label' => Craft::t('market', 'Unknown'), 'value' => 'unknown']
        ];
    }
}
