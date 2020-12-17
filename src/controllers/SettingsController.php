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

use angellco\market\Market;
use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

/**
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
        $variables = [];
        return $this->renderTemplate('market/settings/_general.html', $variables, View::TEMPLATE_MODE_CP);
    }


    // Vendor settings
    // -------------------------------------------------------------------------

    /**
     * Vendor settings edit view.
     *
     * @param array $variables
     * @return Response
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

        return $this->renderTemplate('market/settings/vendors/_settings.html', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * Vendor fields edit view.
     *
     * @return Response
     */
    public function actionVendorsFields(): Response
    {
        $variables = [];
        return $this->renderTemplate('market/settings/vendors/_fields.html', $variables, View::TEMPLATE_MODE_CP);
    }

    public function actionSaveVendors()
    {
        Craft::dd('here I am');
        // Next up, project config like this: https://craftcms.com/docs/3.x/extend/project-config.html#implementing-project-config-support
    }


    // Stripe settings
    // -------------------------------------------------------------------------

    // TODO
}
