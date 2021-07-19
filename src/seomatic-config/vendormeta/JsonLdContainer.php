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
use nystudio107\seomatic\models\MetaJsonLdContainer;
use nystudio107\seomatic\services\JsonLd as JsonLdService;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

return [
    MetaJsonLdContainer::CONTAINER_TYPE.JsonLdService::GENERAL_HANDLE => [
        'name'         => 'General',
        'description'  => 'JsonLd Tags',
        'handle'       => JsonLdService::GENERAL_HANDLE,
        'class'        => (string)MetaJsonLdContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
            'mainEntityOfPage' => [
                'type'             => '{seomatic.meta.mainEntityOfPage}',
                'name'             => '{seomatic.meta.seoTitle}',
                'headline'         => '{seomatic.meta.seoTitle}',
                'description'      => '{seomatic.meta.seoDescription}',
                'url'              => '{seomatic.meta.canonicalUrl}',
                'mainEntityOfPage' => '{seomatic.meta.canonicalUrl}',
                'dateCreated'      => '{vendor.dateCreated |atom}',
                'dateModified'     => '{vendor.dateUpdated |atom}',
                'datePublished'    => '{vendor.dateCreated |atom}',
                'copyrightYear'    => '{vendor.dateCreated |date("Y")}',
                'inLanguage'       => '{seomatic.meta.language}',
                'copyrightHolder'  => [
                    'id' => '{parseEnv(seomatic.site.identity.genericUrl)}#identity',
                ],
                'author'           => [
                    'id' => '{parseEnv(seomatic.site.identity.genericUrl)}#identity',
                ],
                'creator'          => [
                    'id' => '{parseEnv(seomatic.site.identity.genericUrl)}#creator',
                ],
                'publisher'        => [
                    'id' => '{parseEnv(seomatic.site.identity.genericUrl)}#creator',
                ],
                'image'            => [
                    'type' => 'ImageObject',
                    'url'  => '{seomatic.meta.seoImage}',
                ],
                'potentialAction'  => [
                    'type'        => 'SearchAction',
                    'target'      => '{seomatic.site.siteLinksSearchTarget}',
                    'query-input' => '{seomatic.helper.siteLinksQueryInput()}',
                ],
            ],
        ],
    ],
];
