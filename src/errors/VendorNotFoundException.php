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

use craft\errors\ElementNotFoundException;

/**
 * Class VendorNotFoundException
 */
class VendorNotFoundException extends ElementNotFoundException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Vendor not found';
    }
}
