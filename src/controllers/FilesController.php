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
use craft\elements\Asset;
use craft\web\Controller;
use yii\web\BadRequestHttpException;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class FilesController extends Controller
{

    /**
     * Deletes assets - always returns true.
     *
     * Ideal for Sprig.
     *
     * @return bool
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionDeleteFiles(): bool
    {
        $this->requirePostRequest();
        $elementsService = Craft::$app->getElements();

        $assetIds = Craft::$app->getRequest()->getRequiredParam('fileIds');
        $assetIds = explode(',', $assetIds);

        $query = Asset::find()
            ->anyStatus()
            ->limit(null)
            ->id($assetIds);

        foreach ($query->all() as $asset) {
            if (!$asset->getIsDeletable()) {
                continue;
            }

            $elementsService->deleteElement($asset);
        }

        return true;
    }

}
