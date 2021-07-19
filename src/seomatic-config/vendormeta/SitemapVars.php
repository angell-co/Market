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
    'sitemapUrls'          => true,
    'sitemapAssets'        => true,
    'sitemapFiles'         => true,
    'sitemapAltLinks'      => true,
    'sitemapChangeFreq'    => 'weekly',
    'sitemapPriority'      => 0.5,
    'sitemapLimit'         => null,
    'structureDepth'       => null,
    'sitemapImageFieldMap' => [
        ['property' => 'title', 'field' => 'title'],
        ['property' => 'caption', 'field' => ''],
        ['property' => 'geo_location', 'field' => ''],
        ['property' => 'license', 'field' => ''],
    ],
    'sitemapVideoFieldMap' => [
        ['property' => 'title', 'field' => 'title'],
        ['property' => 'description', 'field' => ''],
        ['property' => 'thumbnailLoc', 'field' => ''],
        ['property' => 'duration', 'field' => ''],
        ['property' => 'category', 'field' => ''],
    ],
];
