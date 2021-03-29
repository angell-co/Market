<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\controllers;

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\web\Controller;
use Stripe\Exception\OAuth\OAuthErrorException;
use Stripe\OAuth;
use Stripe\Stripe;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class StripeController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws HttpException
     * @throws \Throwable
     * @throws SiteNotFoundException
     */
    public function actionHandleOnboarding(): void
    {
        $request = Craft::$app->getRequest();
        $settings = Market::$plugin->getStripeSettings()->getSettings();

        // Validate CSRF if enabled
        if (Craft::$app->getConfig()->general->enableCsrfProtection) {
            $state = $request->getParam('state');

            if (!$state || !$request->validateCsrfToken($state)) {
                throw new HttpException(400, Craft::t('app','The CSRF token could not be verified.'));
            }

        }

        // Get the Stripe authorization code
        $code = $request->getRequiredParam('code');

        // Try to get an access token using the authorization code grant.
        try {
            // Get the currently logged in Vendor
            /** @var Vendor $vendor */
            $vendor = Market::$plugin->getVendors()->getCurrentVendor();
            if (!$vendor) {
                throw new HttpException(403, Craft::t('market', 'Sorry you must be a Vendor to perform this action.'));
            }

            // Make the token call
            Stripe::setApiKey($settings->secretKey);
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            // Set the new Stripe data on it
            $vendor->stripeUserId = $response->stripe_user_id;
            $vendor->stripeRefreshToken = $response->refresh_token;
            $vendor->stripeAccessToken = $response->access_token;

            // Save it
            if (Craft::$app->getElements()->saveElement($vendor)) {
                Craft::$app->getSession()->setNotice(Craft::t('market', 'Successfully connected to Stripe.'));
                $this->redirect($settings->redirectSuccess);
            } else {
                Craft::$app->getSession()->setError(Craft::t('market', 'Couldn’t connect to Stripe.'));
                $this->redirect($settings->redirectError);
            }

        } catch (OAuthErrorException $e) {
            // Failed to get the access token or user details.

            // TODO: log or throw something
            // throw new HttpException(403, Craft::t('Couldn’t complete Stripe authorization.'));
            // See here: https://stripe.com/docs/connect/standalone-accounts#token-request

            Craft::$app->getSession()->setError(Craft::t('market', 'Couldn’t connect to Stripe as their was a problem with your account.'));
            $this->redirect($settings->redirectError);

        } catch (Exception $e) {
            Craft::$app->getSession()->setError(Craft::t('market', 'There was a problem connecting to Stripe.'));
            $this->redirect($settings->redirectError);
        }

        Craft::$app->getSession()->setError(Craft::t('market', 'There was a problem connecting to Stripe.'));
        $this->redirect($settings->redirectError);
    }


    /**
     * Disconnect the user from the platform Stripe account.
     *
     * @throws Exception
     * @throws HttpException
     * @throws MissingComponentException
     * @throws \Throwable
     */
    public function actionDisconnect(): void
    {
        $settings = Market::$plugin->getStripeSettings()->getSettings();

        // Get the currently logged in Vendor
        /** @var Vendor $vendor */
        $vendor = Market::$plugin->getVendors()->getCurrentVendor();
        if (!$vendor) {
            throw new HttpException(403, Craft::t('market', 'Sorry you must be a Vendor to perform this action.'));
        }

        // Check if they are already disconnected
        if (!$vendor->stripeUserId) {
            Craft::$app->getSession()->setNotice(Craft::t('market', 'Already disconnected.'));
            $this->redirect($settings->redirectSuccess);
        }

        try {
            Stripe::setApiKey($settings->secretKey);

            // Call stripe deauthorize
            OAuth::deauthorize([
                'client_id' => $settings->clientId,
                'stripe_user_id' => $vendor->stripeUserId,
            ]);

            // If that worked, then remove the stripe details
            $this->_removeStripeDetailsFromVendor($vendor);

        } catch (OAuthErrorException $e) {

            // If it is an invalid client, then they aren’t connected, so just
            // remove their connection details and pretend it all worked
            if ($e->getError()->error === 'invalid_client') {
                $this->_removeStripeDetailsFromVendor($vendor);
            }

            // Fallback to sending the actual error back
            Craft::$app->getSession()->setError($e->getError()->error_description);
            $this->redirect($settings->redirectError);

        } catch (Exception $e) {
            // TODO: Log this
            throw $e;
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Sorry there was an unknown error.'));
        $this->redirect($settings->redirectError);

    }

    // Private Methods
    // =========================================================================

    /**
     * Removes the Stripe details from the Vendor
     *
     * @param Vendor $vendor
     * @throws MissingComponentException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _removeStripeDetailsFromVendor(Vendor $vendor): void
    {
        $settings = Market::$plugin->getStripeSettings()->getSettings();

        // Null the Stripe details
        $vendor->stripeUserId = null;
        $vendor->stripeRefreshToken = null;
        $vendor->stripeAccessToken = null;

        // Save the Vendor
        if (Craft::$app->getElements()->saveElement($vendor)) {
            Craft::$app->getSession()->setNotice(Craft::t('market', 'Account disconnected.'));
            $this->redirect($settings->redirectSuccess);
        }

    }

    // ProtectedMethods
    // =========================================================================

    /**
     * Creates a new instance of the OAuth2 Provider
     */
//    protected function getProvider()
//    {

//        // Get the provider instance
//        if (!$this->_provider) {
//            $this->_provider = new GenericProvider([
//                'clientId'                => $this->_settings->clientId,
//                'clientSecret'            => $this->_settings->secretKey,
//                'redirectUri'             => UrlHelper::getActionUrl('marketplace/stripe/handleOnboarding'),
//                'urlAuthorize'            => 'https://connect.stripe.com/oauth/authorize',
//                'urlAccessToken'          => 'https://connect.stripe.com/oauth/token',
//                'urlResourceOwnerDetails' => 'https://api.stripe.com/v1/accounts'
//            ]);
//        }
//
//        return $this->_provider;
//    }
}
