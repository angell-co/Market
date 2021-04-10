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
use angellco\market\models\StripeSettings;
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

    /**
     * @var StripeSettings
     */
    private $_settings;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $this->_settings = Market::$plugin->getStripeSettings()->getSettings();
    }

    /**
     * Connects the user to their Stripe account and saves the details back on to the Vendor.
     *
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws HttpException
     * @throws \Throwable
     * @throws SiteNotFoundException
     */
    public function actionHandleOnboarding()
    {
        $request = Craft::$app->getRequest();

        // Validate CSRF if enabled
        if (Craft::$app->getConfig()->general->enableCsrfProtection) {
            $state = $request->getParam('state');

            if (!$state || !$request->validateCsrfToken($state)) {
                throw new HttpException(400, Craft::t('app', 'The CSRF token could not be verified.'));
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
            Stripe::setApiKey($this->_settings->secretKey);
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $code,
                // TODO: Redirect URI - redirect_uri param
            ]);

            // Set the new Stripe data on it
            $vendor->stripeUserId = $response->stripe_user_id;
            $vendor->stripeRefreshToken = $response->refresh_token;
            $vendor->stripeAccessToken = $response->access_token;

            // Save it
            if (Craft::$app->getElements()->saveElement($vendor)) {
                return $this->_returnSuccess(Craft::t('market', 'Successfully connected to Stripe.'));
            } else {
                return $this->_returnError(Craft::t('market', 'Couldn’t connect to Stripe.'));
            }
        } catch (OAuthErrorException $e) {
            // Failed to get the access token or user details.

            // TODO: log or throw something
            // throw new HttpException(403, Craft::t('Couldn’t complete Stripe authorization.'));
            // See here: https://stripe.com/docs/connect/standalone-accounts#token-request

            return $this->_returnError(Craft::t('market', 'Couldn’t connect to Stripe as their was a problem with your account.'));
        } catch (Exception $e) {
            return $this->_returnError(Craft::t('market', 'There was a problem connecting to Stripe.'));
        }

        return $this->_returnError(Craft::t('market', 'There was a problem connecting to Stripe.'));
    }


    /**
     * Disconnect the user from the platform Stripe account.
     *
     * @throws Exception
     * @throws HttpException
     * @throws MissingComponentException
     * @throws \Throwable
     */
    public function actionDisconnect()
    {
        $this->_settings = Market::$plugin->getStripeSettings()->getSettings();

        // Get the currently logged in Vendor
        /** @var Vendor $vendor */
        $vendor = Market::$plugin->getVendors()->getCurrentVendor();
        if (!$vendor) {
            throw new HttpException(403, Craft::t('market', 'Sorry you must be a Vendor to perform this action.'));
        }

        // Check if they are already disconnected
        if (!$vendor->stripeUserId) {
            return $this->_returnSuccess(Craft::t('market', 'Already disconnected.'));
        }

        try {
            Stripe::setApiKey($this->_settings->secretKey);

            // Call stripe deauthorize
            OAuth::deauthorize([
                'client_id' => $this->_settings->clientId,
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
            return $this->_returnError($e->getError()->error_description);
        } catch (Exception $e) {
            // TODO: Log this
            throw $e;
            return $this->_returnError(Craft::t('app', 'Sorry there was an unknown error.'));
        }

        return $this->_returnSuccess(Craft::t('market', 'Successfully disconnected.'));
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $message
     * @return \yii\web\Response
     * @throws MissingComponentException
     */
    private function _returnSuccess($message)
    {
        Craft::$app->getSession()->setNotice($message);
        return $this->redirect($this->_settings->redirectSuccess);
    }

    /**
     * @param $message
     * @return \yii\web\Response
     * @throws MissingComponentException
     */
    private function _returnError($message)
    {
        Craft::$app->getSession()->setError($message);
        return $this->redirect($this->_settings->redirectError);
    }

    /**
     * Removes the Stripe details from the Vendor
     *
     * @param Vendor $vendor
     * @throws MissingComponentException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _removeStripeDetailsFromVendor(Vendor $vendor)
    {
        $this->_settings = Market::$plugin->getStripeSettings()->getSettings();

        // Null the Stripe details
        $vendor->stripeUserId = null;
        $vendor->stripeRefreshToken = null;
        $vendor->stripeAccessToken = null;

        // Save the Vendor
        if (Craft::$app->getElements()->saveElement($vendor)) {
            return true;
        }

        return false;
    }

}
