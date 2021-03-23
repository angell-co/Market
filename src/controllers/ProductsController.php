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
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\BadRequestHttpException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ProductsController extends Controller
{

    /**
     * Deletes products - always returns true.
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
            $elementsService->deleteElement($product);
        }

        return true;
    }


    /**
     * Sets the status on products - always returns true.
     *
     * Ideal for Sprig.
     *
     * @return bool
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function actionSetStatus(): bool
    {

        $this->requirePostRequest();
        $elementsService = Craft::$app->getElements();

        $isLocalized = Product::isLocalized() && Craft::$app->getIsMultiSite();

        $status = Craft::$app->getRequest()->getRequiredParam('status');
        $productIds = Craft::$app->getRequest()->getRequiredParam('productIds');
        $productIds = explode(',', $productIds);

        $query = Product::find()
            ->anyStatus()
            ->limit(null)
            ->id($productIds);

        foreach ($query->all() as $product) {

            if ($status === Element::STATUS_ENABLED) {
                // Skip if there's nothing to change
                if ($product->enabled && $product->getEnabledForSite()) {
                    continue;
                }

                $product->enabled = true;
                $product->setEnabledForSite(true);
                $product->setScenario(Element::SCENARIO_LIVE);
            }

            if ($status === Element::STATUS_DISABLED) {
                // Is this a multi-site element?
                if ($isLocalized && count($product->getSupportedSites()) !== 1) {
                    // Skip if there's nothing to change
                    if (!$product->getEnabledForSite()) {
                        continue;
                    }
                    $product->setEnabledForSite(false);
                } else {
                    // Skip if there's nothing to change
                    if (!$product->enabled) {
                        continue;
                    }
                    $product->enabled = false;
                }
            }

            $elementsService->saveElement($product);
        }

        return true;
    }

}
