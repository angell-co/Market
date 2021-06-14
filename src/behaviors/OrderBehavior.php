<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\behaviors;

use craft\commerce\elements\Order;
use craft\errors\InvalidFieldException;
use yii\base\Behavior;

/**
 * Order behavior
 *
 * @property-read null $attachedVendor
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

class OrderBehavior extends Behavior
{

    /** @var Order */
    public $owner;

    /**
     * Returns the attached vendor
     *
     * @return mixed|null
     * @throws InvalidFieldException
     */
    public function getAttachedVendor()
    {
        return $this->owner->getFieldValue('vendor')[0] ?? null;
    }

}
