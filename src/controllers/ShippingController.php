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

use angellco\market\db\Table;
use angellco\market\elements\Vendor;
use angellco\market\helpers\ShippingProfileHelper;
use angellco\market\Market;
use angellco\market\models\ShippingDestination;
use angellco\market\models\ShippingProfile;
use angellco\market\records\ShippingDestination as ShippingDestinationRecord;
use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\errors\SiteNotFoundException;
use craft\helpers\AdminTable;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\View;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
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
     * Shipping profile edit page
     *
     * @param int|null $profileId
     * @param ShippingProfile|null $shippingProfile
     * @return Response
     * @throws NotFoundHttpException
     * @throws SiteNotFoundException
     */
    public function actionEditShippingProfile(int $profileId = null, ShippingProfile $shippingProfile = null): Response
    {
        // TODO permissions

        $variables = [];

        // Sort out the main model
        if ($profileId !== null) {
            if ($shippingProfile === null) {
                $shippingProfile = Market::$plugin->getShippingProfiles()->getShippingProfileById($profileId);

                if (!$shippingProfile) {
                    throw new NotFoundHttpException('Shipping profile not found');
                }
            }

            $variables['title'] = trim($shippingProfile->name) ?: Craft::t('market', 'Edit shipping profile');
        } else {
            if ($shippingProfile === null) {
                $shippingProfile = new ShippingProfile();
            }

            $variables['title'] = Craft::t('market', 'Create a new shipping profile');
        }

        $variables['shippingProfile'] = $shippingProfile;

        // Vendor
        $variables['vendorElementType'] = Vendor::class;
        $variables['vendor'] = $variables['shippingProfile']->getVendor();

        // Vendor settings
        $variables['vendorSettings'] = Market::$plugin->getVendorSettings()->getSettings();

        // Countries
        $variables['originCountryOptions'] = Commerce::getInstance()->getCountries()->getAllEnabledCountriesAsList();

        // Processing Time options
        $variables['processingTimeOptions'] = ShippingProfileHelper::processingTimeOptions();

        // Shipping Destinations
        $variables['zoneOptions'] = [];
        foreach (Commerce::getInstance()->getShippingZones()->getAllShippingZones() as $zone) {
            $variables['zoneOptions'][] = [
                'label' => $zone->name,
                'value' => $zone->id
            ];
        }

        // Get existing destinations or make a default blank row
        if ($variables['shippingProfile']->getShippingDestinations()) {
            $variables['destinationsTableRows'] = [];
            $count = 1;
            foreach ($variables['shippingProfile']->getShippingDestinations() as $shippingDestination) {

                $rowKey = $shippingDestination->id;
                if (!$rowKey) {
                    $rowKey = 'new'.$count;
                    $count++;
                }

                $variables['destinationsTableRows'][$rowKey] = [
                    'zone' => [
                        'value' => $shippingDestination->shippingZoneId,
                        'hasErrors' => $shippingDestination->getErrors('shippingZoneId')
                    ],
                    'primaryRate' => [
                        'value' => $shippingDestination->primaryRate > 0 ? Craft::$app->getFormatter()->asDecimal($shippingDestination->primaryRate) : '',
                        'hasErrors' => $shippingDestination->getErrors('primaryRate')
                    ],
                    'secondaryRate' => [
                        'value' => $shippingDestination->secondaryRate > 0 ? Craft::$app->getFormatter()->asDecimal($shippingDestination->secondaryRate) : '',
                        'hasErrors' => $shippingDestination->getErrors('secondaryRate')
                    ],
                    'deliveryTime' => [
                        'value' => $shippingDestination->deliveryTime,
                        'hasErrors' => $shippingDestination->getErrors('deliveryTime')
                    ]
                ];
            }
        } else {
            $variables['destinationsTableRows'] = [
                'new0' => [
                    'zone' => $variables['zoneOptions'][0]['value'],
                    'primaryRate' => '',
                    'secondaryRate' => '',
                    'deliveryTime' => ''
                ]
            ];
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Market'),
                'url' => UrlHelper::url('market')
            ],
            [
                'label' => Craft::t('app', 'Shipping'),
                'url' => UrlHelper::url('market/shipping')
            ]
        ];

        // Continue Editing URL
        $variables['continueEditingUrl'] = 'market/shipping/{id}';

        return $this->renderTemplate('market/shipping/_edit', $variables);
    }

    /**
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionSaveShippingProfile(): void
    {
        $this->requirePostRequest();
        $shippingProfile = new ShippingProfile();

        // Shared attributes
        $shippingProfile->id = Craft::$app->getRequest()->getBodyParam('profileId');
        $shippingProfile->name = Craft::$app->getRequest()->getBodyParam('name');
        $shippingProfile->originCountryId = Craft::$app->getRequest()->getBodyParam('originCountryId');
        $shippingProfile->processingTime = Craft::$app->getRequest()->getBodyParam('processingTime');

        // Sort out the vendor ID
        $vendorId = Craft::$app->getRequest()->getBodyParam('vendorId');
        if (is_array($vendorId)) {
            $vendorId = array_shift($vendorId);
        }
        $shippingProfile->vendorId = $vendorId;

        // Set the shipping destinations as an array of models
        $destinationsPost = Craft::$app->getRequest()->getBodyParam('destinations');
        $destinations = [];
        foreach ($destinationsPost as $id => $destination) {

            // Set all the basic stuff
            $destinationModel = new ShippingDestination();
            $destinationModel->shippingProfileId = $shippingProfile->id;
            $destinationModel->shippingZoneId = $destination['zone'];
            $destinationModel->primaryRate = $destination['primaryRate'];
            $destinationModel->secondaryRate = $destination['secondaryRate'];
            $destinationModel->deliveryTime = $destination['deliveryTime'];

            // Check if this is an existing one and set the id accordingly
            if (strncmp($id, 'new', 3) !== 0) {
                $destinationModel->id = $id;
            }

            $destinations[] = $destinationModel;
        }

        $shippingProfile->setShippingDestinations($destinations);

        // Save it
        if (Market::$plugin->getShippingProfiles()->saveShippingProfile($shippingProfile)) {
            $this->setSuccessFlash(Craft::t('market', 'Shipping profile saved.'));
            $this->redirectToPostedUrl($shippingProfile);
        } else {
            $this->setFailFlash(Craft::t('market', 'Couldn’t save shipping profile.'));
        }

        // Send the model back to the template
        Craft::$app->getUrlManager()->setRouteParams(['shippingProfile' => $shippingProfile]);
    }

    /**
     * Deletes a shipping profile.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionDeleteShippingProfile(): ?Response
    {
        $this->requirePostRequest();

        // From the edit view
        $profileId = $this->request->getBodyParam('profileId');

        // From the index view
        if (!$profileId) {
            $profileId = $this->request->getBodyParam('id');
        }

        if (!Market::$plugin->getShippingProfiles()->deleteShippingProfileById($profileId)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('market', 'Couldn’t delete shipping profile.'));

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('market', 'Shipping profile deleted.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Endpoint for the shipping profiles CP index table
     *
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
