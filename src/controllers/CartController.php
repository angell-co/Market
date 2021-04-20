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
use craft\commerce\controllers\CartController as CommerceCartController;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class CartController extends CommerceCartController
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * Switch carts then hit the Commerce update cart action
     *
     * @return void|Response|null
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws MissingComponentException
     * @throws Exception
     */
    public function actionUpdateCart(): ?Response
    {
        $this->requirePostRequest();

        $vendorId = $this->request->getRequiredParam('vendorId');

        // Switch the commerce cart around
        Market::$plugin->getCarts()->switchCart($vendorId);
        return parent::actionUpdateCart();
    }
}
