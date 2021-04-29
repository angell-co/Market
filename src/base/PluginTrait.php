<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\base;

use angellco\market\behaviors\OrderBehavior;
use angellco\market\elements\Vendor;
use angellco\market\fields\ShippingProfile as ShippingProfileField;
use angellco\market\fields\Vendors as VendorsField;
use angellco\market\Market;
use angellco\market\models\VendorsLinkItType;
use angellco\market\services\Carts;
use angellco\market\services\Reports;
use angellco\market\services\ShippingDestinations;
use angellco\market\services\ShippingProfiles;
use angellco\market\services\StripeSettings;
use angellco\market\services\Vendors;
use angellco\market\services\VendorSettings;
use angellco\market\web\twig\MarketExtension;
use Craft;
use craft\commerce\elements\Order;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TitleField;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\UrlManager;
use craft\web\View;
use fruitstudios\linkit\events\RegisterLinkTypesEvent;
use fruitstudios\linkit\services\LinkitService;
use yii\base\Event;
use yii\web\User;

/**
 * @property Vendors $vendors the vendors service
 * @property VendorSettings $vendorSettings the vendor settings service
 * @property StripeSettings $stripeSettings the stripe settings service
 * @property ShippingProfiles $shippingProfiles the shipping profiles service
 * @property ShippingDestinations $shippingDestinations the shipping destinations service
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
     * Returns the shipping profiles service
     *
     * @return ShippingProfiles The shipping profiles service
     */
    public function getShippingProfiles(): ShippingProfiles
    {
        return $this->get('shippingProfiles');
    }

    /**
     * Returns the shipping destinations service
     *
     * @return ShippingDestinations The shipping destinations service
     */
    public function getShippingDestinations(): ShippingDestinations
    {
        return $this->get('shippingDestinations');
    }

    /**
     * Returns the carts service
     *
     * @return Carts The carts service
     */
    public function getCarts(): Carts
    {
        return $this->get('carts');
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
            'shippingProfiles' => [
                'class' => ShippingProfiles::class,
            ],
            'shippingDestinations' => [
                'class' => ShippingDestinations::class,
            ],
            'reports' => [
                'class' => Reports::class,
            ],
            'carts' => [
                'class' => Carts::class,
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
                $event->types[] = VendorsField::class;
                $event->types[] = ShippingProfileField::class;
            }
        );

        // Linkit types
        Event::on(
            LinkitService::class,
            LinkitService::EVENT_REGISTER_LINKIT_FIELD_TYPES,
            function (RegisterLinkTypesEvent $event) {
                $event->types[] = new VendorsLinkItType();
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

        // Vendor dashboard template root
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['_market'] = __DIR__ . '/../templates/vendor-dashboard';
            }
        );

        // Order behaviors
        Event::on(
            Order::class,
            Order::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    OrderBehavior::class,
                ]);
            }
        );

        // Login / logout handlers
        if (!Craft::$app->getRequest()->isConsoleRequest) {
            Event::on(User::class, User::EVENT_AFTER_LOGIN, [$this->getCarts(), 'loginHandler']);
            Event::on(User::class, User::EVENT_AFTER_LOGOUT, [$this->getCarts(), 'logoutHandler']);
        }
    }

    private function _installSiteEventListeners(): void
    {
        Craft::$app->view->registerTwigExtension(new MarketExtension());

        $this->_registerSiteRoutes();
    }

    private function _registerSiteRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['market'] = ['template' => '_market/_index'];

                $event->rules['market/orders'] = ['template' => '_market/orders/_index'];
                $event->rules['market/orders/<orderId:\d+>'] = ['template' => '_market/orders/_edit'];

                $event->rules['market/products'] = ['template' => '_market/products/_index'];
                $event->rules['market/products/<productTypeHandle:{handle}>/new'] = ['template' => '_market/products/_edit'];
                $event->rules['market/products/<productTypeHandle:{handle}>/<productId:\d+><slug:(?:-[^\/]*)?>'] = ['template' => '_market/products/_edit'];

                $event->rules['market/files'] = ['template' => '_market/files/_index'];
                $event->rules['market/files/<assetId:\d+>'] = ['template' => '_market/files/_edit'];

                $event->rules['market/shipping'] = ['template' => '_market/shipping/_index'];
                $event->rules['market/shipping/new'] = ['template' => '_market/shipping/_edit'];
                $event->rules['market/shipping/<profileId:\d+>'] = ['template' => '_market/shipping/_edit'];

                $event->rules['market/shop-front'] = ['template' => '_market/shop-front/_edit'];

                $event->rules['market/reports'] = ['template' => '_market/reports/_index'];
                $event->rules['market/training'] = ['template' => '_market/_training'];

                $event->rules['market/settings'] = ['template' => '_market/settings/_index'];
                $event->rules['market/settings/details'] = ['template' => '_market/settings/_details'];
                $event->rules['market/settings/password'] = ['template' => '_market/settings/_password'];
                $event->rules['market/settings/payments'] = ['template' => '_market/settings/_payments'];
                $event->rules['market/settings/holiday'] = ['template' => '_market/settings/_holiday'];
                $event->rules['market/settings/product-catalogue'] = ['template' => '_market/settings/_product-catalogue'];

                $event->rules['market/login'] = ['template' => '_market/account/_login'];
                $event->rules['market/forgot-password'] = ['template' => '_market/account/_forgot-pass'];
                $event->rules['market/reset-password'] = ['template' => '_market/account/_set-pass'];
                $event->rules['market/invalid-password-reset-link'] = ['template' => '_market/account/_invalid-token'];
            }
        );
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

                // Shipping
                $event->rules['market/shipping'] = 'market/shipping/index';
                $event->rules['market/shipping/new'] = 'market/shipping/edit-shipping-profile';
                $event->rules['market/shipping/<profileId:\d+>'] = 'market/shipping/edit-shipping-profile';

                // Orders
                $event->rules['market/orders'] = 'market/order-groups/index';
            }
        );
    }
}
