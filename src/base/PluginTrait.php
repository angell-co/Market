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

use angellco\market\elements\Vendor;
use angellco\market\services\StripeSettings;
use angellco\market\services\Vendors;
use angellco\market\services\VendorSettings;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TitleField;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * @property Vendors $vendors the vendors service
 * @property VendorSettings $vendorSettings the vendor settings service
 * @property StripeSettings $stripeSettings the stripe settings service
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
trait PluginTrait
{

    /**
     * Returns the vendors service
     *
     * @return Vendors The vendors service
     */
    public function getVendors(): Vendors
    {
        return $this->get('vendors');
    }

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
     * Returns the stripe settings service
     *
     * @return StripeSettings The stripe settings service
     */
    public function getStripeSettings(): StripeSettings
    {
        return $this->get('stripeSettings');
    }

    /**
     * Sets the components of the commerce plugin
     */
    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'vendors' => [
                'class' => Vendors::class,
            ],
            'vendorSettings' => [
                'class' => VendorSettings::class,
            ],
            'stripeSettings' => [
                'class' => StripeSettings::class,
            ],
        ]);
    }

    /**
     * Installs the global event listeners
     */
    private function _installGlobalEventListeners(): void
    {
        // Register the element type
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Vendor::class;
            }
        );

        // Add title fields to Vendors
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_STANDARD_FIELDS, function(DefineFieldLayoutFieldsEvent $event) {
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $event->sender;

            if ($fieldLayout->type == Vendor::class) {
                $event->fields[] = TitleField::class;
            }
        });

        // Register the field types
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Vendors::class;
            }
        );

        // Project config
        $vendorSettingsPath = VendorSettings::CONFIG_VENDOR_SETTINGS_KEY.".{uid}";
        $stripeSettingsPath = StripeSettings::CONFIG_STRIPE_SETTINGS_KEY.".{uid}";
        Craft::$app->projectConfig
            ->onAdd($vendorSettingsPath, [$this->vendorSettings, 'handleChangedSettings'])
            ->onUpdate($vendorSettingsPath, [$this->vendorSettings, 'handleChangedSettings'])
            ->onRemove($vendorSettingsPath, [$this->vendorSettings, 'handleDeletedSettings'])
            ->onAdd($stripeSettingsPath, [$this->stripeSettings, 'handleChangedSettings'])
            ->onUpdate($stripeSettingsPath, [$this->stripeSettings, 'handleChangedSettings'])
            ->onRemove($stripeSettingsPath, [$this->stripeSettings, 'handleDeletedSettings']);
    }

    private function _registerSiteRoutes(): void
    {
        //
    }

    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                // Settings
                $event->rules['market/settings'] = 'market/settings/index';
                $event->rules['market/settings/general'] = 'market/settings/general';
                $event->rules['market/settings/vendors'] = 'market/settings/vendors';
                $event->rules['market/settings/stripe'] = 'market/settings/stripe';

                // Vendor elements
                $event->rules['market/vendors/new'] = 'market/vendors/edit-vendor';
                $event->rules['market/vendors/new/<siteHandle:{handle}>'] = 'market/vendors/edit-vendor';
                $event->rules['market/vendors/<vendorId:\d+><slug:(?:-{slug})?>'] = 'market/vendors/edit-vendor';
                $event->rules['market/vendors/<vendorId:\d+><slug:(?:-{slug})?>/<siteHandle:{handle}>'] = 'market/vendors/edit-vendor';
            }
        );
    }
}
