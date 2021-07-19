<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

use angellco\market\seoelements\SeoVendor;
use nystudio107\seomatic\helpers\ArrayHelper;
use nystudio107\seomatic\helpers\Config;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

$filePaths = [
    'vendormeta/TagContainer',
    'vendormeta/LinkContainer',
    'vendormeta/ScriptContainer',
    'vendormeta/JsonLdContainer',
    'vendormeta/TitleContainer',
];
$mergedConfig = [];
foreach ($filePaths as $filePath) {
    $mergedConfig = ArrayHelper::merge($mergedConfig, Config::getConfigFromFile($filePath, '@angellco/market'));
}

return [
    'bundleVersion'              => '1.0.0',
    'sourceBundleType'           => SeoVendor::getMetaBundleType(),
    'sourceId'                   => null,
    'sourceName'                 => null,
    'sourceHandle'               => null,
    'sourceType'                 => 'vendor',
    'typeId'                     => null,
    'sourceTemplate'             => '',
    'sourceSiteId'               => null,
    'sourceAltSiteSettings'      => [
    ],
    'sourceDateUpdated'          => new \DateTime(),
    'metaGlobalVars'             => Config::getConfigFromFile('vendormeta/GlobalVars', '@angellco/market'),
    'metaSiteVars'               => Config::getConfigFromFile('vendormeta/SiteVars', '@angellco/market'),
    'metaSitemapVars'            => Config::getConfigFromFile('vendormeta/SitemapVars', '@angellco/market'),
    'metaBundleSettings'         => Config::getConfigFromFile('vendormeta/BundleSettings', '@angellco/market'),
    'metaContainers'             => $mergedConfig,
    'redirectsContainer'         => Config::getConfigFromFile('vendormeta/RedirectsContainer', '@angellco/market'),
    'frontendTemplatesContainer' => Config::getConfigFromFile('vendormeta/FrontendTemplatesContainer', '@angellco/market'),
];
