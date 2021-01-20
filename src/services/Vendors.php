<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\services;

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\errors\SiteNotFoundException;
use craft\web\View;
use yii\base\Exception;

/**
 * Vendors service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Vendors extends Component
{
    /**
     * Returns if the Vendor settings’ template path is valid.
     *
     * @param int $siteId
     * @return bool
     * @throws SiteNotFoundException|Exception
     */
    public function isVendorTemplateValid(int $siteId): bool
    {
        $vendorSettings = Market::$plugin->getVendorSettings()->getSettings($siteId);

        if (!$vendorSettings) {
            return false;
        }

        $template = (string)$vendorSettings->template;
        return Craft::$app->getView()->doesTemplateExist($template, View::TEMPLATE_MODE_SITE);
    }

    /**
     * Returns a vendor by its ID.
     *
     * @param int $vendorId
     * @param int|null $siteId
     * @return Vendor|null
     */
    public function getVendorById(int $vendorId, int $siteId = null): ?Vendor
    {
        if (!$vendorId) {
            return null;
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($vendorId, Vendor::class, $siteId);
    }

    /**
     * Returns a vendor by its user ID
     *
     * @param int $userId
     * @return array|ElementInterface|null
     */
    public function getVendorByUserId(int $userId)
    {
        $query = Vendor::find();
        $query->userId = $userId;
        return $query->one();
    }

    /**
     * Fetches the Vendor that is currently logged in. Returns false if there is
     * anything wrong (not logged in or user isn’t attached to a vendor)
     *
     * @return array|bool|ElementInterface|null
     */
    public function getCurrentVendor()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser)
        {
            return false;
        }

        if ($vendor = $this->getVendorByUserId($currentUser->id))
        {
            return $vendor;
        }

        return false;
    }

}
