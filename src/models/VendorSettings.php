<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\models;

use angellco\market\elements\Vendor;
use Craft;
use craft\base\Model;
use craft\base\Volume;
use craft\behaviors\FieldLayoutBehavior;
use craft\commerce\models\Country;
use craft\commerce\Plugin as CommercePlugin;
use craft\models\FieldLayout;
use craft\models\Site;
use yii\base\InvalidConfigException;

/**
 * Vendor Settings model
 *
 * @property Volume $volume
 * @property-read Site $site
 * @property Country $shippingOrigin
 * @property-read FieldLayout $fieldLayout
 * @mixin FieldLayoutBehavior
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorSettings extends Model
{
    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int Site ID
     */
    public $siteId;

    /**
     * @var int Volume ID
     */
    public $volumeId;

    /**
     * @var int Field Layout ID
     */
    public $fieldLayoutId;

    /**
     * @var int Shipping Origin ID
     */
    public $shippingOriginId;

    /**
     * @var string Template Path
     */
    public $template;

    /**
     * @var string URL Format
     */
    public $urlFormat;

    /**
     * @var string UID
     */
    public $uid;

    /**
     * @var Site
     */
    private $_site;

    /**
     * @var Volume
     */
    private $_volume;

    /**
     * @var Country
     */
    private $_shippingOrigin;

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function __toString()
    {
        if ($this->getSite()) {
            return Craft::t('market', 'Vendor Settings ({siteName})', ['siteName' => $this->getSite()->name]);
        }

        return (string) $this->id;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['id', 'siteId', 'volumeId', 'fieldLayoutId', 'shippingOriginId'], 'number', 'integerOnly' => true];
        $rules[] = [['siteId', 'volumeId', 'shippingOriginId', 'urlFormat'], 'required'];

        return $rules;
    }

    /**
     * Returns the Site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if ($this->_site !== null) {
            return $this->_site;
        }

        if (!$this->siteId) {
            throw new InvalidConfigException('Vendor settings is missing its site ID');
        }

        if (($this->_site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $this->_site;
    }

    /**
     * Returns the Volume.
     *
     * @return Volume
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     */
    public function getVolume(): Volume
    {
        if ($this->_volume !== null) {
            return $this->_volume;
        }

        if (!$this->volumeId) {
            throw new InvalidConfigException('Vendor settings is missing its volume ID');
        }

        if (($this->_volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: ' . $this->volumeId);
        }

        return $this->_volume;
    }

    /**
     * Sets the Volume.
     *
     * @param Volume $volume
     */
    public function setVolume(Volume $volume): void
    {
        $this->_volume = $volume;
    }

    /**
     * Returns the Shipping Origin (Country).
     *
     * @return Country
     * @throws InvalidConfigException if [[shippingOriginId]] is missing or invalid
     */
    public function getShippingOrigin(): Country
    {
        if ($this->_shippingOrigin !== null) {
            return $this->_shippingOrigin;
        }

        if (!$this->shippingOriginId) {
            throw new InvalidConfigException('Vendor settings is missing its shipping origin ID');
        }

        if (($this->_shippingOrigin = CommercePlugin::getInstance()->getCountries()->getCountryById($this->shippingOriginId)) === null) {
            throw new InvalidConfigException('Invalid shipping origin ID: ' . $this->shippingOriginId);
        }

        return $this->_shippingOrigin;
    }

    /**
     * Sets the Shipping Origin (Country).
     *
     * @param Country $country
     */
    public function setShippingOrigin(Country $country): void
    {
        $this->_shippingOrigin = $country;
    }

    /**
     * @return FieldLayout
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @return array|string[]
     */
    public function behaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Vendor::class,
                'idAttribute' => 'fieldLayoutId'
            ]
        ];
    }

}
