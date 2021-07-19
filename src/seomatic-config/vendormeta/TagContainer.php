<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

use nystudio107\seomatic\helpers\Dependency;
use nystudio107\seomatic\models\MetaTagContainer;
use nystudio107\seomatic\services\Tag as TagService;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

return [
    MetaTagContainer::CONTAINER_TYPE.TagService::GENERAL_HANDLE  => [
        'name'         => 'General',
        'description'  => 'General Meta Tags',
        'handle'       => TagService::GENERAL_HANDLE,
        'class'        => (string)MetaTagContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
        ],
    ],
    MetaTagContainer::CONTAINER_TYPE.TagService::FACEBOOK_HANDLE => [
        'name'         => 'Facebook',
        'description'  => 'Facebook OpenGraph Meta Tags',
        'handle'       => TagService::FACEBOOK_HANDLE,
        'class'        => (string)MetaTagContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
        ],
    ],
    MetaTagContainer::CONTAINER_TYPE.TagService::TWITTER_HANDLE  => [
        'name'         => 'Twitter',
        'description'  => 'Twitter Card Meta Tags',
        'handle'       => TagService::TWITTER_HANDLE,
        'class'        => (string)MetaTagContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
        ],
    ],
    MetaTagContainer::CONTAINER_TYPE.TagService::MISC_HANDLE     => [
        'name'         => 'Miscellaneous',
        'description'  => 'Miscellaneous Meta Tags',
        'handle'       => TagService::MISC_HANDLE,
        'class'        => (string)MetaTagContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
        ],
    ],
];
