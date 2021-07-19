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
        'siteType'                      => 'CreativeWork',
        'siteSubType'                   => 'WebPage',
        'siteSpecificType'              => '',

        'seoTitleSource'                => 'fromField',
        'seoTitleField'                 => 'title',
        'siteNamePositionSource'        => 'sameAsGlobal',
        'seoDescriptionSource'          => 'fromCustom',
        'seoDescriptionField'           => '',
        'seoKeywordsSource'             => 'fromCustom',
        'seoKeywordsField'              => '',
        'seoImageIds'                   => [],
        'seoImageSource'                => 'fromAsset',
        'seoImageField'                 => '',
        'seoImageTransform'             => true,
        'seoImageTransformMode'         => 'crop',
        'seoImageDescriptionSource'     => 'fromCustom',
        'seoImageDescriptionField'      => '',

        'twitterCreatorSource'          => 'sameAsSite',
        'twitterCreatorField'           => '',
        'twitterTitleSource'            => 'sameAsSeo',
        'twitterTitleField'             => '',
        'twitterSiteNamePositionSource' => 'sameAsGlobal',
        'twitterDescriptionSource'      => 'sameAsSeo',
        'twitterDescriptionField'       => '',
        'twitterImageIds'               => [],
        'twitterImageSource'            => 'sameAsSeo',
        'twitterImageField'             => '',
        'twitterImageTransform'         => true,
        'twitterImageTransformMode'     => 'crop',
        'twitterImageDescriptionSource' => 'sameAsSeo',
        'twitterImageDescriptionField'  => '',

        'ogTitleSource'                 => 'sameAsSeo',
        'ogTitleField'                  => '',
        'ogSiteNamePositionSource'      => 'sameAsGlobal',
        'ogDescriptionSource'           => 'sameAsSeo',
        'ogDescriptionField'            => '',
        'ogImageIds'                    => [],
        'ogImageSource'                 => 'sameAsSeo',
        'ogImageField'                  => '',
        'ogImageTransform'              => true,
        'ogImageTransformMode'          => 'crop',
        'ogImageDescriptionSource'      => 'sameAsSeo',
        'ogImageDescriptionField'       => '',
    ],
];
