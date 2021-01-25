<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\errors;

use yii\base\Exception;

/**
 * Class VendorShippingProfilesNotFoundException
 *
 */
class VendorShippingProfilesNotFoundException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Vendor does not have any shipping profiles configured';
    }
}
