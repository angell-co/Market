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
use craft\commerce\records\Country;
use craft\db\ActiveRecord;
use craft\records\FieldLayout;
use craft\records\Site;
use craft\records\Volume;
use yii\db\ActiveQueryInterface;

/**
 * Vendor Settings record
 *
 * @property int $id
 * @property int $siteId
 * @property int $volumeId
 * @property int $fieldLayoutId
 * @property int $shippingOriginId
 * @property string $template
 * @property string $urlFormat
 * @property ActiveQueryInterface $site
 * @property ActiveQueryInterface $volume
 * @property ActiveQueryInterface $fieldLayout
 * @property ActiveQueryInterface $shippingOrigin
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorSettings extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::VENDORSETTINGS;
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id', 'siteId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getVolume(): ActiveQueryInterface
    {
        return $this->hasOne(Volume::class, ['id' => 'volumeId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getShippingOrigin(): ActiveQueryInterface
    {
        return $this->hasOne(Country::class, ['id' => 'shippingOriginId']);
    }

}
