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
use angellco\market\Market;
use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use yii\base\NotSupportedException;
use yii\db\Exception;

/**
 * @property Asset|null $profilePicture
 * @property-read bool $hasCheckeredThumb
 * @property User|null|bool|false $user
 *
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

    /**
     * @var User|null|false
     */
    private $_user;

    /**
     * @var Asset|null|false
     */
    private $_profilePicture;


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
        // TODO: permissions
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
     * @throws NotSupportedException
     */
    public function getThumbUrl(int $size)
    {
        $profilePicture = $this->getProfilePicture();

        if ($profilePicture) {
            return Craft::$app->getAssets()->getThumbUrl($profilePicture, $size, $size, false);
        }

        return Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/user.svg');
    }

    /**
     * @inheritdoc
     */
    public function getHasCheckeredThumb(): bool
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
            self::STATUS_DISABLED => Craft::t('app', 'Disabled'),
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
     * @return VendorQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new VendorQuery(static::class);
    }

    /**
     * Returns the vendor's user.
     *
     * ---
     * ```php
     * $user = $vendor->user;
     * ```
     * ```twig
     * <p>By {{ vendor.user.name }}</p>
     * ```
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === null) {
            if ($this->userId === null) {
                return null;
            }

            if (($this->_user = Craft::$app->getUsers()->getUserById($this->userId)) === null) {
                // The user is probably soft-deleted. Just no user is set
                $this->_user = false;
            }
        }

        return $this->_user ?: null;
    }

    /**
     * Sets the vendor's user.
     *
     * @param User|null $user
     */
    public function setUser(User $user = null): void
    {
        $this->_user = $user;
    }

    /**
     * Returns the vendor's profile picture
     *
     * @return Asset|null
     */
    public function getProfilePicture(): ?Asset
    {
        if ($this->_profilePicture === null) {
            if ($this->profilePictureId === null) {
                return null;
            }

            if (($this->_profilePicture = Craft::$app->getAssets()->getAssetById($this->profilePictureId)) === null) {
                // The asset is probably soft-deleted.
                $this->_profilePicture = false;
            }
        }

        return $this->_profilePicture ?: null;
    }

    /**
     * Sets the vendor's profile picture
     *
     * @param Asset|null $profilePicture
     */
    public function setProfilePicture(Asset $profilePicture = null): void
    {
        $this->_profilePicture = $profilePicture;
    }


    // Protected Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        // This is annoying, don’t really want to have to define sources but the base element index js
        // requires us to have at least one to work
        return [
            [
                'key' => '*',
                'label' => Craft::t('market', 'All vendors'),
                'hasThumbs' => true
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            'id' => Craft::t('app', 'ID'),
            'code' => Craft::t('market', 'Code'),
            'user' => Craft::t('app', 'User'),
            'profilePicture' => Craft::t('market', 'Profile Picture'),
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['code', 'user', 'link'];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'user':
                $user = $this->getUser();
                return $user ? Cp::elementHtml($user) : '';

            case 'profilePicture':
                $profilePicture = $this->getProfilePicture();
                return $profilePicture ? Cp::elementHtml($profilePicture) : '';
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $view = Craft::$app->getView();

        $html = parent::getEditorHtml();
        $html .= $view->renderTemplateMacro('market/vendors/_fields', 'generalMetaFields', [$this]);
        $html .= $view->renderTemplateMacro('market/vendors/_fields', 'stripeMeta', [$this]);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        $vendorSettings = Market::$plugin->getVendorSettings()->getSettings($this->siteId);

        if ($vendorSettings && $vendorSettings->fieldLayoutId) {
            return Craft::$app->fields->getLayoutById($vendorSettings->fieldLayoutId);
        }

        return null;
    }
}
