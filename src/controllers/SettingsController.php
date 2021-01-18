<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\controllers;

use angellco\market\elements\Vendor;
use angellco\market\Market;
use angellco\market\models\StripeSettings;
use angellco\market\models\VendorSettings;
use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\errors\SiteNotFoundException;
use craft\web\Controller;
use craft\web\View;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * TODO: multi-site
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class SettingsController extends Controller
{

    /**
     * Root settings URL redirects to general settings.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->redirect('market/settings/general');
    }


    // General settings
    // -------------------------------------------------------------------------

    /**
     * General settings edit view.
     *
     * @return Response
     */
    public function actionGeneral(): Response
    {
        $variables = [
            'settings' => Market::$plugin->getSettings()
        ];

        return $this->renderTemplate('market/settings/_general.html', $variables, View::TEMPLATE_MODE_CP);
    }

    // Vendor settings
    // -------------------------------------------------------------------------

    /**
     * Vendor settings edit view.
     *
     * @param array $variables
     * @return Response
     * @throws SiteNotFoundException
     */
    public function actionVendors(array $variables = []): Response
    {
        if (empty($variables['settings']))
        {
            $variables['settings'] = Market::$plugin->getVendorSettings()->getSettings();
        }

        $variables['volumeOptions'] = array();

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume)
        {
            $variables['volumeOptions'][] = array('label' => $volume->name, 'value' => $volume->id);
        }

        $variables['countryOptions'] = CommercePlugin::getInstance()->getCountries()->getAllCountriesAsList();

        return $this->renderTemplate('market/settings/_vendors.html', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * Saves the Vendor settings.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ErrorException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function actionSaveVendors(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $settingsService = Market::$plugin->getVendorSettings();
        $settingsId = $this->request->getBodyParam('settingsId');

        if ($settingsId) {
            $settings = $settingsService->getSettingsById($settingsId);
            if (!$settings) {
                throw new BadRequestHttpException("Invalid Vendor settings ID: $settingsId");
            }
        } else {
            $settings = new VendorSettings();
        }

        // Set the simple stuff
        $settings->urlFormat = $this->request->getBodyParam('urlFormat');
        $settings->template = $this->request->getBodyParam('template');
        $settings->volumeId = $this->request->getBodyParam('volumeId');
        $settings->shippingOriginId = $this->request->getBodyParam('shippingOriginId');

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Vendor::class;
        $settings->setFieldLayout($fieldLayout);

        // Save it
        if (!$settingsService->saveSettings($settings)) {
            $this->setFailFlash(Craft::t('market', 'Couldn’t save Vendor settings.'));

            // Send the tag group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('market', 'Vendor settings saved.'));
        return $this->redirectToPostedUrl($settings);
    }


    // Stripe settings
    // -------------------------------------------------------------------------

    /**
     * Stripe settings edit view.
     *
     * @param array $variables
     * @return Response
     * @throws SiteNotFoundException
     */
    public function actionStripe(array $variables = []): Response
    {
        if (empty($variables['settings']))
        {
            $variables['settings'] = Market::$plugin->getStripeSettings()->getSettings();
        }

        return $this->renderTemplate('market/settings/_stripe.html', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * Saves the Stripe settings.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ErrorException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function actionSaveStripe(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $settingsService = Market::$plugin->getStripeSettings();
        $settingsId = $this->request->getBodyParam('settingsId');

        if ($settingsId) {
            $settings = $settingsService->getSettingsById($settingsId);
            if (!$settings) {
                throw new BadRequestHttpException("Invalid Stripe settings ID: $settingsId");
            }
        } else {
            $settings = new StripeSettings();
        }

        // Set the simple stuff
        $settings->clientId = $this->request->getBodyParam('clientId');
        $settings->secretKey = $this->request->getBodyParam('secretKey');
        $settings->publishableKey = $this->request->getBodyParam('publishableKey');
        $settings->redirectSuccess = $this->request->getBodyParam('redirectSuccess');
        $settings->redirectError = $this->request->getBodyParam('redirectError');

        // Save it
        if (!$settingsService->saveSettings($settings)) {
            $this->setFailFlash(Craft::t('market', 'Couldn’t save Stripe settings.'));

            // Send the tag group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('market', 'Stripe settings saved.'));
        return $this->redirectToPostedUrl($settings);
    }
}
