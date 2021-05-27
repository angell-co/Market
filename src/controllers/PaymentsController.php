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

use angellco\market\Market;
use Craft;
use craft\commerce\elements\Order;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;
use Stripe\Exception\ApiErrorException;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use function Arrayy\array_first;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class PaymentsController extends Controller
{

    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * Sets up the stripe customer and SetupIntent
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidFieldException
     * @throws MissingComponentException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws SiteNotFoundException
     * @throws Exception
     */
    public function actionSetup(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $settings = Market::$plugin->getStripeSettings()->getSettings();
        \Stripe\Stripe::setApiKey($settings->secretKey);

        // Get all the carts
        $carts = Market::$plugin->getCarts()->getCarts();
        /** @var Order $firstCart */
        $firstCart = ArrayHelper::firstValue($carts);

        // Get the billing address
        $billingAddress = $firstCart->getBillingAddress();
        if (!$billingAddress) {
            return $this->asErrorJson('Sorry there was a problem with your addresses, please check them and try again.');
        }

        // See if we have a stripe customer ID already
        $stripeCustomerId = null;
        $user = $firstCart->getCustomer()->getUser();
        if ($user) {
            $stripeCustomerId = $user->getFieldValue('stripeCustomerId');
        }

        // If we do, check it
        $stripeCustomer = null;
        if ($stripeCustomerId) {
            try {
                $stripeCustomer = \Stripe\Customer::retrieve($stripeCustomerId);

                // If that customer was deleted, set it to false so we create a new one
                if ($stripeCustomer->isDeleted()) {
                    $stripeCustomer = false;
                }

            } catch (ApiErrorException $e) {
                // If this fails, we just want to carry on
            }
        }

        // If we still don’t have a stripe customer, create it
        if (!$stripeCustomer) {
            try {
                $stripeCustomer = \Stripe\Customer::create([
                    'email' => $firstCart->email,
                    'name' => $billingAddress->firstName . ' ' . $billingAddress->lastName
                ]);
            } catch (ApiErrorException $e) {
                return $this->asErrorJson($e->getMessage());
            }
        }

        // Now we can silently store the customer ID for future use if they are logged in
        if ($user && $stripeCustomerId !== $stripeCustomer->id) {
            $user->setFieldValue('stripeCustomerId', $stripeCustomer->id);
            /** @noinspection PhpUnhandledExceptionInspection */
            Craft::$app->getElements()->saveElement($user);
        }

        // Finally create the SetupIntent - this will allow us to create a saved payment method we can re-use for each order
        try {
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $stripeCustomer->id
            ]);

            return $this->asJson([
                'customerId' => $stripeCustomer->id,
                'clientSecret' => $setupIntent->client_secret
            ]);
        } catch (ApiErrorException $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

}
