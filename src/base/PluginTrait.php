<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\base;

use angellco\market\services\VendorSettings;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * @property VendorSettings $vendorSettings the vendor settings service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
trait PluginTrait
{

    /**
     * Returns the vendor settings service
     *
     * @return VendorSettings The vendor settings service
     */
    public function getVendorSettings(): VendorSettings
    {
        return $this->get('vendorSettings');
    }

    /**
     * Sets the components of the commerce plugin
     */
    private function _setPluginComponents()
    {
        $this->setComponents([
            'vendorSettings' => [
                'class' => VendorSettings::class,
            ],
        ]);
    }

    private function _registerSiteRoutes()
    {
        //
    }

    private function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['market/settings'] = 'market/settings/index';
                $event->rules['market/settings/general'] = 'market/settings/general';
                $event->rules['market/settings/vendors/general'] = 'market/settings/vendors';
                $event->rules['market/settings/vendors/fields'] = 'market/settings/vendors-fields';
            }
        );
    }
}
