<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\services;

use angellco\market\elements\Vendor;
use angellco\market\errors\VendorNotFoundException;
use angellco\market\Market;
use angellco\market\records\Vendor as VendorRecord;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\errors\AssetConflictException;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\errors\VolumeObjectExistsException;
use craft\helpers\Assets;
use craft\models\VolumeFolder;
use craft\web\View;
use yii\base\Exception;
use yii\web\ServerErrorHttpException;

/**
 * Vendors service
 *
 * @property null|bool|array|ElementInterface $currentVendor
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
     * TODO: cache this on the class
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

    /**
     * Activates a vendor.
     *
     * @param Vendor $vendor The vendor.
     * @return bool Whether the vendor was marked as pending successfully.
     * @throws \Throwable if reasons
     */
    public function activateVendor(Vendor $vendor): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $vendorRecord = $this->_getVendorRecordById($vendor->id);
            $vendorRecord->pending = false;
            $vendorRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Update the Vendor model too
        $vendor->pending = false;

        return true;
    }

    /**
     * Sets a vendor as pending.
     *
     * @param Vendor $vendor The vendor.
     * @return bool Whether the vendor was marked as pending successfully.
     * @throws \Throwable if reasons
     */
    public function setVendorPending(Vendor $vendor): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $vendorRecord = $this->_getVendorRecordById($vendor->id);
            $vendorRecord->pending = true;
            $vendorRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Update the Vendor model too
        $vendor->pending = true;

        return true;
    }

    /**
     * Suspends a vendor.
     *
     * @param Vendor $vendor The vendor.
     * @return bool Whether the vendor was suspended successfully.
     * @throws \Throwable if reasons
     */
    public function suspendVendor(Vendor $vendor): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $vendorRecord = $this->_getVendorRecordById($vendor->id);
            $vendorRecord->suspended = true;
            $vendorRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Update the Vendor model too
        $vendor->suspended = true;

        return true;
    }

    /**
     * Unsuspends a vendor.
     *
     * @param Vendor $vendor The vendor.
     * @return bool Whether the vendor was unsuspended successfully.
     * @throws \Throwable if reasons
     */
    public function unsuspendVendor(Vendor $vendor): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $vendorRecord = $this->_getVendorRecordById($vendor->id);
            $vendorRecord->suspended = false;
            $vendorRecord->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Update the Vendor model too
        $vendor->suspended = false;

        return true;
    }

    /**
     * Creates the volume folders for the vendor and saves their IDs on to it.
     *
     * @param Vendor $vendor
     * @return bool
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws SiteNotFoundException
     * @throws VolumeObjectExistsException
     * @throws \Throwable
     */
    public function createVolumeFolders(Vendor $vendor): bool
    {
        $volumeId = $vendor->getSettings()->volumeId;
        $rootFolder = Craft::$app->getAssets()->getRootFolderByVolumeId($volumeId);

        if (!$rootFolder) {
            return false;
        }

        // Create the main folder
        $mainFolder = $this->_createOrFetchVolumeFolder($vendor->code, $rootFolder);
        if (!$mainFolder) {
            return false;
        }

        // Create the account and files folder
        $accountFolder = $this->_createOrFetchVolumeFolder('Account', $mainFolder);
        $filesFolder = $this->_createOrFetchVolumeFolder('Files', $mainFolder);

        if (!$accountFolder || !$filesFolder) {
            return false;
        }

        // Check if we actually need to save the folder IDs onto the vendor at this point,
        // if nothing has changed then just return true
        if (
            (int) $vendor->mainFolderId === (int) $mainFolder->id
            && (int) $vendor->accountFolderId === (int) $accountFolder->id
            && (int) $vendor->filesFolderId === (int) $filesFolder->id
        ) {
            return true;
        }

        // Set attributes and save again
        $vendor->mainFolderId = $mainFolder->id;
        $vendor->accountFolderId = $accountFolder->id;
        $vendor->filesFolderId = $filesFolder->id;

        // Save the vendor again
        return Craft::$app->getElements()->saveElement($vendor);
    }

    /**
     * Creates or fetches a volume folder.
     *
     * @param string $folderName The name of the folder
     * @param VolumeFolder $parentFolder The parent folder, this can be the root folder of the volume
     * @return VolumeFolder|null
     * @throws VolumeObjectExistsException
     */
    private function _createOrFetchVolumeFolder(string $folderName, VolumeFolder $parentFolder): ?VolumeFolder
    {
        $assets = Craft::$app->getAssets();
        $folderName = Assets::prepareAssetName($folderName, false);
        $path = $parentFolder->path . $folderName . '/';

        $folder = new VolumeFolder();
        $folder->name = $folderName;
        $folder->parentId = $parentFolder->id;
        $folder->volumeId = $parentFolder->volumeId;
        $folder->path = $path;

        try {
            $assets->createFolder($folder);
        } catch (AssetConflictException $e) {
            // Thrown if a folder already exists with the same name, so just get it
            $folder = $assets->findFolder([
                'path' => $path,
                'volumeId' => $parentFolder->volumeId
            ]);
        }

        return $folder;
    }

    /**
     * Gets a vendor record by its ID.
     *
     * @param int $vendorId
     * @return VendorRecord
     * @throws VendorNotFoundException if $vendorId is invalid
     */
    private function _getVendorRecordById(int $vendorId): VendorRecord
    {
        $vendorRecord = VendorRecord::findOne($vendorId);

        if (!$vendorRecord) {
            throw new VendorNotFoundException("No vendor exists with the ID '{$vendorId}'");
        }

        return $vendorRecord;
    }

}
