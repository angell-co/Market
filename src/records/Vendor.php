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
use craft\records\Asset;
use craft\records\Element;
use craft\records\User;
use craft\records\VolumeFolder;
use yii\db\ActiveQueryInterface;

/**
 * Vendor record
 *
 * @property int $id ID
 * @property int $userId User ID
 * @property int $profilePictureId Profile picture ID
 * @property bool $suspended Suspended
 * @property bool $pending Pending
 * @property string $code Code
 * @property string $stripeUserId Stripe user ID
 * @property string $stripeRefreshToken Stripe refresh token
 * @property string $stripeAccessToken Stripe access token
 * @property int $mainFolderId Main folder ID
 * @property int $accountFolderId Account folder ID
 * @property int $filesFolderId Files folder ID
 * @property User $user
 * @property Asset $profilePicture
 * @property VolumeFolder $mainFolder
 * @property VolumeFolder $filesFolder
 * @property VolumeFolder $accountFolder
 * @property Element $element Element
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Vendor extends ActiveRecord
{

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::VENDORS;
    }

    /**
     * Returns the vendor’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the vendor’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * Returns the vendor’s profile picture asset.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getProfilePicture(): ActiveQueryInterface
    {
        return $this->hasOne(Asset::class, ['id' => 'profilePictureId']);
    }

    /**
     * Returns the vendor’s main volume folder.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getMainFolder(): ActiveQueryInterface
    {
        return $this->hasOne(VolumeFolder::class, ['id' => 'mainFolderId']);
    }

    /**
     * Returns the vendor’s account volume folder.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getAccountFolder(): ActiveQueryInterface
    {
        return $this->hasOne(VolumeFolder::class, ['id' => 'accountFolderId']);
    }

    /**
     * Returns the vendor’s files volume folder.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFilesFolder(): ActiveQueryInterface
    {
        return $this->hasOne(VolumeFolder::class, ['id' => 'filesFolderId']);
    }

}
