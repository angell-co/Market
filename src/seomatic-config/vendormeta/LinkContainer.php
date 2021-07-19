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
use nystudio107\seomatic\models\MetaLinkContainer;
use nystudio107\seomatic\services\Link as LinkService;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

return [
    MetaLinkContainer::CONTAINER_TYPE.LinkService::GENERAL_HANDLE => [
        'name'         => 'General',
        'description'  => 'Link Tags',
        'handle'       => LinkService::GENERAL_HANDLE,
        'class'        => (string)MetaLinkContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
        ],
    ],
];
