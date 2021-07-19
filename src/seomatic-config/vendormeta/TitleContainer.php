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
use nystudio107\seomatic\models\MetaTitleContainer;
use nystudio107\seomatic\services\Title as TitleService;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

return [
    MetaTitleContainer::CONTAINER_TYPE.TitleService::GENERAL_HANDLE => [
        'name'         => 'General',
        'description'  => 'Meta Title Tag',
        'handle'       => TitleService::GENERAL_HANDLE,
        'class'        => (string)MetaTitleContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
            'title' => [
                'title'            => '{seomatic.meta.seoTitle}',
                'siteName'         => '{seomatic.site.siteName}',
                'siteNamePosition' => '{seomatic.meta.siteNamePosition}',
                'separatorChar'    => '{seomatic.config.separatorChar}',
            ],
        ],
    ],
];
