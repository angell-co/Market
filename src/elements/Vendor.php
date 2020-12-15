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
use yii\db\Exception;

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

    /**
     * @var int User ID
     */
    public $userId;

    /**
     * @var int Profile Picture ID
     */
    public $profilePictureId;

    /**
     * @var bool Suspended
     */
    public $suspended = false;

    /**
     * @var bool Pending
     */
    public $pending = false;

    /**
     * @var string Code
     */
    public $code;

    /**
     * @var string Stripe User ID
     */
    public $stripeUserId;

    /**
     * @var string Stripe Refresh Token
     */
    public $stripeRefreshToken;

    /**
     * @var string Stripe Access Token
     */
    public $stripeAccessToken;

    /**
     * @var int Main folder ID
     */
    public $mainFolderId;

    /**
     * @var int Account folder ID
     */
    public $accountFolderId;

    /**
     * @var int Files folder ID
     */
    public $filesFolderId;


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

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        $columns = [
            'userId' => $this->userId,
            'profilePictureId' => $this->profilePictureId,
            'suspended' => $this->suspended,
            'pending' => $this->pending,
            'code' => $this->code,
            'stripeUserId' => $this->stripeUserId,
            'stripeRefreshToken' => $this->stripeRefreshToken,
            'stripeAccessToken' => $this->stripeAccessToken,
            'mainFolderId' => $this->mainFolderId,
            'filesFolderId' => $this->filesFolderId,
            'accountFolderId' => $this->accountFolderId
        ];

        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%products}}', array_merge(['id' => $this->id], $columns))
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%products}}', $columns, ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }
}
