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
use nystudio107\seomatic\models\MetaScriptContainer;
use nystudio107\seomatic\services\Script as ScriptService;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */

return [
    MetaScriptContainer::CONTAINER_TYPE.ScriptService::GENERAL_HANDLE => [
        'name'         => 'General',
        'description'  => 'Script Tags',
        'handle'       => ScriptService::GENERAL_HANDLE,
        'class'        => (string)MetaScriptContainer::class,
        'include'      => true,
        'dependencies' => [
        ],
        'data'         => [
        ],
    ],
];
