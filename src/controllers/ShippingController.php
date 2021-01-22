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

use angellco\market\db\Table;
use angellco\market\models\ShippingProfile;
use angellco\market\records\ShippingDestination as ShippingDestinationRecord;
use angellco\market\records\ShippingProfile as ShippingProfileRecord;
use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\AdminTable;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\View;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingController extends Controller
{
    /**
     * Shipping index
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('market/shipping/_index.html', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionShippingProfilesTable(): Response
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $page = $request->getParam('page', 1);
        $sort = $request->getParam('sort', null);
        $limit = $request->getParam('per_page', 10);
        $search = $request->getParam('search', null);
        $offset = ($page - 1) * $limit;

        $query = (new Query());
        $query->select([
            'shippingprofiles.id as id',
            'shippingprofiles.vendorId as vendorId',
            'vendorcontent.title as vendorTitle',
            'shippingprofiles.originCountryId as originCountryId',
            'shippingprofiles.name as name',
            'shippingprofiles.processingTime as processingTime',
            'countries.name as countryName',
            'countries.iso as countryIso',
        ]);
        $query->from(Table::SHIPPINGPROFILES . ' shippingprofiles');
        $query->leftJoin(CraftTable::CONTENT . ' vendorcontent', '[[vendorcontent.elementId]] = [[shippingprofiles.vendorId]]');
        $query->leftJoin(CommerceTable::COUNTRIES . ' countries', '[[countries.id]] = [[shippingprofiles.originCountryId]]');


        if ($search) {
            $likeOperator = Craft::$app->getDb()->getIsPgsql() ? 'ILIKE' : 'LIKE';
            $query->andWhere([
                'or',
                [$likeOperator, '[[shippingprofiles.name]]', $search],
                [$likeOperator, '[[shippingprofiles.processingTime]]', $search],
                [$likeOperator, '[[vendorcontent.title]]', $search],
                [$likeOperator, '[[countries.name]]', $search],
                [$likeOperator, '[[countries.iso]]', $search],
            ]);
        }

        $total = $query->count();

        $query->offset($offset);
        $query->limit($limit);

        if ($sort) {
            [$sortField, $sortDir] = explode('|', $sort);
            if ($sortField && $sortDir) {
                $query->orderBy('[[' . $sortField . ']] ' . $sortDir);
            }
        }

        // Get number of shipping destinations for shipping profiles
        $profileIds = $query->column();
        $destinationCountByProfileId = [];

        if (!empty($profileIds)) {
            $destinationCountByProfileId = ShippingDestinationRecord::find()
                ->select(['COUNT(*) as noDestinations', 'shippingProfileId'])
                ->groupBy('[[shippingProfileId]]')
                ->where(['shippingProfileId' => $profileIds])
                ->indexBy('shippingProfileId')
                ->asArray()
                ->column();
        }

        $rows = [];
        foreach ($query->all() as $row) {

            // Make a model so we can use the methods on it
            $shippingProfile = new ShippingProfile();
            $shippingProfile->id = $row['id'];
            $shippingProfile->vendorId = $row['vendorId'];
            $shippingProfile->originCountryId = $row['originCountryId'];
            $shippingProfile->name = $row['name'];
            $shippingProfile->processingTime = $row['processingTime'];

            // Get the vendor
            $vendor = $shippingProfile->getVendor();

            // Get the origin country
            $originCountry = $shippingProfile->getOriginCountry();

            $rows[] = [
                'id' => $shippingProfile->id,
                'title' => Html::encode($shippingProfile->name),
                'url' => UrlHelper::cpUrl("market/shipping/" . $shippingProfile->id),
                'vendor' => $vendor ? [
                    'title' => $vendor ? $vendor->__toString() : null,
                    'url' => $vendor ? $vendor->getCpEditUrl() : null,
                    'status' => $vendor ? $vendor->getStatus() : null,
                ] : null,
                'originCountry' => $originCountry->iso,
                'processingTime' => $shippingProfile->getProcessingTimeLabel(),
                'destinations' => $destinationCountByProfileId[$shippingProfile->id] ?? 0,
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }
}
