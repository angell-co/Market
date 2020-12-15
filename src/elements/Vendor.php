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

use angellco\market\elements\db\VendorQuery;
use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
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
    public function getIsEditable(): bool
    {
//        return Craft::$app->getUser()->checkPermission('commerce-manageOrders');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('market/vendors/'.$this->id);
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => [
                'label' => Craft::t('market', 'Active'),
            ],
            self::STATUS_PENDING => [
                'label' => Craft::t('market', 'Pending')
            ],
            self::STATUS_SUSPENDED => [
                'label' => Craft::t('market', 'Suspended')
            ],
            self::STATUS_DISABLED => [
                'label' => Craft::t('app', 'Disabled')
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        $status = parent::getStatus();

        if ($status == static::STATUS_ENABLED) {

            if ($this->suspended) {
                return self::STATUS_SUSPENDED;
            }

            if ($this->pending) {
                return self::STATUS_PENDING;
            }

            return self::STATUS_ACTIVE;

        }

        return $status;
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
                ->insert('{{%market_vendors}}', array_merge(['id' => $this->id], $columns))
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%market_vendors}}', $columns, ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new VendorQuery(static::class);
    }


    // Protected Methods
    // -------------------------------------------------------------------------

    protected static function defineSources(string $context = null): array
    {
        return [
            '*' => [
                'label' => Craft::t('market', 'All vendors'),
                'hasThumbs' => false
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
//            'price' => \Craft::t('plugin-handle', 'Price'),
//            'currency' => \Craft::t('plugin-handle', 'Currency'),
        ];
    }

}
