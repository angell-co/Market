<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

return [
    '*' => [
        'mainEntityOfPage'        => 'WebPage',
        'seoTitle'                => '{vendor.title}',
        'siteNamePosition'        => '',
        'seoDescription'          => '',
        'seoKeywords'             => '',
        'seoImage'                => '',
        'seoImageWidth'           => '',
        'seoImageHeight'          => '',
        'seoImageDescription'     => '',
        'canonicalUrl'            => '{vendor.url}',
        'robots'                  => 'all',
        'ogType'                  => 'website',
        'ogTitle'                 => '{seomatic.meta.seoTitle}',
        'ogSiteNamePosition'      => '',
        'ogDescription'           => '{seomatic.meta.seoDescription}',
        'ogImage'                 => '{seomatic.meta.seoImage}',
        'ogImageWidth'            => '{seomatic.meta.seoImageWidth}',
        'ogImageHeight'           => '{seomatic.meta.seoImageHeight}',
        'ogImageDescription'      => '{seomatic.meta.seoImageDescription}',
        'twitterCard'             => 'summary_large_image',
        'twitterCreator'          => '{seomatic.site.twitterHandle}',
        'twitterTitle'            => '{seomatic.meta.seoTitle}',
        'twitterSiteNamePosition' => '',
        'twitterDescription'      => '{seomatic.meta.seoDescription}',
        'twitterImage'            => '{seomatic.meta.seoImage}',
        'twitterImageWidth'       => '{seomatic.meta.seoImageWidth}',
        'twitterImageHeight'      => '{seomatic.meta.seoImageHeight}',
        'twitterImageDescription' => '{seomatic.meta.seoImageDescription}',
    ],
];
