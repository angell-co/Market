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

    public function actionIndex(): Response
    {
        return $this->redirect('market/settings/general');
    }

    public function actionGeneral(): Response
    {
        $variables = [];
        return $this->renderTemplate('market/settings/_general.html', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
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

    public function actionVendorsFields(): Response
    {
        $variables = [];
        return $this->renderTemplate('market/settings/vendors/_fields.html', $variables, View::TEMPLATE_MODE_CP);
    }

}
