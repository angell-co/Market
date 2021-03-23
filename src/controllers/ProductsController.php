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

use Craft;
use craft\commerce\elements\Product;
use craft\web\Controller;
use yii\web\BadRequestHttpException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ProductsController extends Controller
{

    /**
     * Deletes products - returns true always.
     *
     * Ideal for Sprig.
     *
     * @return bool
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionDeleteProducts(): bool
    {
        $this->requirePostRequest();
        $elementsService = Craft::$app->getElements();

        $productIds = Craft::$app->getRequest()->getRequiredParam('productIds');
        $productIds = explode(',', $productIds);

        $query = Product::find()
            ->anyStatus()
            ->limit(null)
            ->id($productIds);

        foreach ($query->all() as $product) {
            if (!$product->getIsDeletable()) {
                continue;
            }
            if (!isset($deletedProductIds[$product->id])) {
                $elementsService->deleteElement($product);
            }
        }

        return true;
    }

}
