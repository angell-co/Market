<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2021 Angell & Co
 */

namespace angellco\market\controllers;

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\errors\SiteNotFoundException;
use craft\events\ElementEvent;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * TODO: multi-site
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class VendorsController extends Controller
{

    /**
     * @event ElementEvent The event that is triggered when a vendor’s template is rendered for Live Preview.
     */
    public const EVENT_PREVIEW_VENDOR = 'previewVendor';

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['view-shared-vendor'];

    // Public Methods
    // -------------------------------------------------------------------------

    /**
     * Displays the vendor edit page.
     *
     * @param int|null $vendorId The vendor’s ID, if editing an existing category.
     * @param string|null $siteHandle The site handle, if specified.
     * @param Vendor|null $vendor The vendor being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException if the requested site handle is invalid
     * @throws SiteNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionEditVendor(int $vendorId = null, string $siteHandle = null, Vendor $vendor = null): Response
    {
        $variables = [
            'vendorId' => $vendorId,
            'vendor' => $vendor
        ];

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        $this->_prepEditVendorVariables($variables);

        /** @var Site $site */
        $site = $variables['site'];
        /** @var Vendor $vendor */
        $vendor = $variables['vendor'];

        $this->_enforceEditVendorPermissions($vendor);


        // User selector variables
        // ---------------------------------------------------------------------
        $variables['userElementType'] = User::class;
        $variables['user'] = $variables['vendor']->getUser();


        // Vendor profile picture selector variables
        // ---------------------------------------------------------------------

        if ($variables['vendor']->id) {
            $variables['assetElementType'] = Asset::class;

            // Lifted from craft\fields\Assets::_getSourcePathByFolderId
            if ($variables['vendor']->accountFolderId && $folder = Craft::$app->getAssets()->getFolderById($variables['vendor']->accountFolderId)) {
                $folderPath = 'folder:' . $folder->uid;

                while ($folder->parentId && $folder->volumeId !== null) {
                    $parent = $folder->getParent();
                    $folderPath = 'folder:' . $parent->uid . '/' . $folderPath;
                    $folder = $parent;
                }

                $variables['profilePictureOptionSources'] = [$folderPath];

                $variables['profilePictureOptionCriteria'] = [
                    'kind' => ['image'],
                    'siteId' => $site->id,
                ];

                $variables['profilePicture'] = $variables['vendor']->getProfilePicture();
            }
        }


        // Variables
        // ---------------------------------------------------------------------

        // Body class
        $variables['bodyClass'] = 'edit-vendor site--' . $site->handle;

        // Page title
        if ($vendor->id === null) {
            $variables['title'] = Craft::t('market', 'Create a new vendor');
        } else {
            $variables['docTitle'] = $variables['title'] = trim($vendor->title) ?: Craft::t('market', 'Edit Vendor');
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Market'),
                'url' => UrlHelper::url('market')
            ],
            [
                'label' => Craft::t('app', 'Vendors'),
                'url' => UrlHelper::url('market/vendors')
            ]
        ];

        $variables['showPreviewBtn'] = false;

        // Enable Live Preview?
        if (!$this->request->isMobileBrowser(true) && Market::$plugin->getVendors()->isVendorTemplateValid($vendor->siteId)) {
            $this->getView()->registerJs('Craft.LivePreview.init(' . Json::encode([
                    'fields' => '#fields > .flex-fields > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $vendor->getUrl(),
                    'previewAction' => Craft::$app->getSecurity()->hashData('market/vendors/preview-vendor'),
                    'previewParams' => [
                        'vendorId' => $vendor->id,
                        'siteId' => $vendor->siteId,
                    ]
                ]) . ');');

            if (!Craft::$app->getConfig()->getGeneral()->headlessMode) {
                $variables['showPreviewBtn'] = true;
            }

            // Should we show the Share button too?
            if ($vendor->id !== null) {
                // If the vendor is enabled, use its main URL as its share URL.
                if ($vendor->getStatus() === Element::STATUS_ENABLED) {
                    $variables['shareUrl'] = $vendor->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::actionUrl('market/vendors/share-vendor',
                        [
                            'vendorId' => $vendor->id,
                            'siteId' => $vendor->siteId
                        ]);
                }
            }
        }

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = "market/vendors/{id}-{slug}";

        // Set the "Continue Editing" URL
        $siteSegment = Craft::$app->getIsMultiSite() && Craft::$app->getSites()->getCurrentSite()->id != $site->id ? "/{$site->handle}" : '';
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . $siteSegment;

        // Set the "Save and add another" URL
        $variables['nextVendorUrl'] = "market/vendors/new{$siteSegment}";

        // Render the template!
        return $this->renderTemplate('market/vendors/_edit', $variables);
    }

    /**
     * Previews a vendor.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws SiteNotFoundException
     */
    public function actionPreviewVendor(): Response
    {
        $this->requirePostRequest();

        $vendor = $this->_getVendorModel();
        $this->_enforceEditVendorPermissions($vendor);
        $this->_populateVendorModel($vendor);

        // Fire a 'previewCategory' event
        if ($this->hasEventHandlers(self::EVENT_PREVIEW_VENDOR)) {
            $this->trigger(self::EVENT_PREVIEW_VENDOR, new ElementEvent([
                'element' => $vendor,
            ]));
        }

        return $this->_showVendor($vendor);
    }

    /**
     * Saves a vendor.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     */
    public function actionSaveVendor(): ?Response
    {
        $this->requirePostRequest();

        $vendor = $this->_getVendorModel();

        $isNew = !$vendor->id;

        // Permission enforcement
        $this->_enforceEditVendorPermissions($vendor);

        // Are we duplicating the vendor?
        if ($this->request->getBodyParam('duplicate')) {
            // Swap $vendor with the duplicate
            try {
                $vendor = Craft::$app->getElements()->duplicateElement($vendor);
            } catch (InvalidElementException $e) {
                /** @var vendor $clone */
                $clone = $e->element;

                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'errors' => $clone->getErrors(),
                    ]);
                }

                $this->setFailFlash(Craft::t('market', 'Couldn’t duplicate vendor.'));

                // Send the original vendor back to the template, with any validation errors on the clone
                $vendor->addErrors($clone->getErrors());
                Craft::$app->getUrlManager()->setRouteParams([
                    'vendor' => $vendor
                ]);

                return null;
            } catch (\Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('market', 'An error occurred when duplicating the vendor.'), 0, $e);
            }
        }

        // Populate the vendor with post data
        $this->_populateVendorModel($vendor);

        // Save the vendor
        if ($vendor->enabled && $vendor->getEnabledForSite()) {
            $vendor->setScenario(Element::SCENARIO_LIVE);
        }

        if (!Craft::$app->getElements()->saveElement($vendor)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $vendor->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('market', 'Couldn’t save vendor.'));

            // Send the vendor back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'vendor' => $vendor
            ]);

            return null;
        }

        // Create the volume folders - we only want to do this if the vendor actually saved OK.
        // The method will only re-save the vendor if the folders actually need creating or are
        // different for some reason.
        if (!Market::$plugin->getVendors()->createVolumeFolders($vendor)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $vendor->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('market', 'Couldn’t save vendor volume folders.'));

            // Send the vendor back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'vendor' => $vendor
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'vendor' => $vendor->toArray()
            ]);
        }

        $this->setSuccessFlash(Craft::t('market', 'Vendor saved.'));
        return $this->redirectToPostedUrl($vendor);
    }

    /**
     * Deletes a vendor.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException if the requested vendor cannot be found
     * @throws \Throwable
     */
    public function actionDeleteVendor(): ?Response
    {
        $this->requirePostRequest();

        $vendorId = $this->request->getRequiredBodyParam('vendorId');
        $vendor = Market::$plugin->getVendors()->getVendorById($vendorId);

        if (!$vendor) {
            throw new NotFoundHttpException('Vendor not found');
        }

        // TODO Make sure they have permission to do this
//        $this->requirePermission('editCategories:' . $vendor->getGroup()->uid);

        // Delete it
        if (!Craft::$app->getElements()->deleteElement($vendor)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('market', 'Couldn’t delete vendor.'));

            // Send the vendor back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'vendor' => $vendor
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('market', 'Vendor deleted.'));
        return $this->redirectToPostedUrl($vendor);
    }

    /**
     * Redirects the client to a URL for viewing a disabled vendor on the front end.
     *
     * @param int $vendorId
     * @param int|null $siteId
     * @return Response
     * @throws Exception
     * @throws NotFoundHttpException if the requested vendor cannot be found
     * @throws ServerErrorHttpException if the vendor settings are not configured properly
     */
    public function actionShareVendor(int $vendorId, int $siteId = null): Response
    {
        $vendor = Market::$plugin->getVendors()->getVendorById($vendorId, $siteId);

        if (!$vendor) {
            throw new NotFoundHttpException('Vendor not found');
        }

        // Make sure they have permission to be viewing this vendor
        $this->_enforceEditVendorPermissions($vendor);

        // Make sure the vendor actually can be viewed
        if (!Market::$plugin->getVendors()->isVendorTemplateValid($vendor->siteId)) {
            throw new ServerErrorHttpException(Craft::t('market', 'Vendor settings not configured properly.'));
        }

        // Create the token and redirect to the vendor URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'market/vendors/view-shared-vendor',
            [
                'vendorId' => $vendorId,
                'siteId' => $vendor->siteId
            ]
        ]);

        if ($token === false) {
            throw new Exception('There was a problem generating the token.');
        }

        $url = UrlHelper::urlWithToken($vendor->getUrl(), $token);

        return $this->response->redirect($url);
    }

    /**
     * Shows a vendor/draft/version based on a token.
     *
     * @param int $vendorId
     * @param int|null $siteId
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException if the requested vendor cannot be found
     * @throws ServerErrorHttpException
     * @throws SiteNotFoundException
     */
    public function actionViewSharedVendor(int $vendorId, int $siteId = null): Response
    {
        $this->requireToken();

        $vendor = Market::$plugin->getVendors()->getVendorById($vendorId, $siteId);

        if (!$vendor) {
            throw new NotFoundHttpException('Vendor not found');
        }

        return $this->_showVendor($vendor);
    }

    /**
     * Preps vendor variables.
     *
     * @param array &$variables
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     * @throws NotFoundHttpException if the requested vendor cannot be found
     * @throws SiteNotFoundException|InvalidConfigException|ServerErrorHttpException
     */
    private function _prepEditVendorVariables(array &$variables): void
    {
        // Get the site
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            /** @noinspection PhpUnhandledExceptionInspection */
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        if (empty($variables['site'])) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $variables['site'] = Craft::$app->getSites()->getCurrentSite();

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Get the vendor settings
        // ---------------------------------------------------------------------

        $variables['vendorSettings'] = Market::$plugin->getVendorSettings()->getSettings($variables['site']->id);

        if (empty($variables['vendorSettings'])) {
            throw new NotFoundHttpException('Category group not found');
        }

        // Get the vendor
        // ---------------------------------------------------------------------

        if (empty($variables['vendor'])) {
            if (!empty($variables['vendorId'])) {
                $variables['vendor'] = Market::$plugin->getVendors()->getVendorById($variables['vendorId'], $site->id);

                if (!$variables['vendor']) {
                    throw new NotFoundHttpException('Vendor not found');
                }
            } else {
                $variables['vendor'] = new Vendor();
                $variables['vendor']->enabled = true;
                $variables['vendor']->siteId = $site->id;
            }
        }

        // Prep the form tabs & content
        $form = $variables['vendorSettings']->getFieldLayout()->createForm($variables['vendor']);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
    }

    /**
     * Fetches or creates a Vendor.
     *
     * @return Vendor
     * @throws NotFoundHttpException if the requested vendor cannot be found
     */
    private function _getVendorModel(): Vendor
    {
        $vendorId = $this->request->getBodyParam('vendorId');
        $siteId = $this->request->getBodyParam('siteId');

        if ($vendorId) {
            $vendor = Market::$plugin->getVendors()->getVendorById($vendorId, $siteId);

            if (!$vendor) {
                throw new NotFoundHttpException('Vendor not found');
            }
        } else {
            $vendor = new Vendor();

            if ($siteId) {
                $vendor->siteId = $siteId;
            }
        }

        return $vendor;
    }

    /**
     * Enforces all Edit vendor permissions.
     *
     * @param Vendor $vendor
     * @throws ForbiddenHttpException|InvalidConfigException
     */
    private function _enforceEditVendorPermissions(vendor $vendor): void
    {
        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $vendor->getSite()->uid);
        }

        // TODO Make sure the user is allowed to edit vendors in general
//        $this->requirePermission('editCategories:' . $vendor->getGroup()->uid);
    }

    /**
     * Populates a vendor with post data.
     *
     * @param Vendor $vendor
     */
    private function _populateVendorModel(Vendor $vendor): void
    {
        // Set the vendor attributes, defaulting to the existing values for whatever is missing from the post data
        $vendor->slug = $this->request->getBodyParam('slug', $vendor->slug);
        $vendor->code = $this->request->getBodyParam('code', $vendor->code);
        $vendor->enabled = (bool)$this->request->getBodyParam('enabled', $vendor->enabled);

        $vendor->title = $this->request->getBodyParam('title', $vendor->title);

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $vendor->setFieldValuesFromRequest($fieldsLocation);

        // User
        if (($userId = $this->request->getBodyParam('userId', $vendor->userId)) !== null) {
            if (is_array($userId)) {
                $userId = reset($userId) ?: null;
            }

            $vendor->userId = $userId;
        }

        // Profile picture - only if not new as we need to wait for the vendor folders to be created
        if ($vendor->id && ($profilePictureId = $this->request->getBodyParam('profilePictureId', $vendor->profilePictureId)) !== null) {
            if (is_array($profilePictureId)) {
                $profilePictureId = reset($profilePictureId) ?: null;
            }

            $vendor->profilePictureId = $profilePictureId;
        }
    }

    /**
     * Displays a vendor.
     *
     * @param Vendor $vendor
     * @return Response
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException if the vendor doesn't have a URL for the site it's configured with, or if the vendor's site ID is invalid
     * @throws SiteNotFoundException
     */
    private function _showVendor(vendor $vendor): Response
    {
        $site = Craft::$app->getSites()->getSiteById($vendor->siteId, true);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: ' . $vendor->siteId);
        }

        Craft::$app->language = $site->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($site->language));

        // Get the vendor settings
        $vendorSettings = $vendor->getSettings();

        // Have this vendor override any freshly queried categories with the same ID/site
        if ($vendor->id) {
            Craft::$app->getElements()->setPlaceholderElement($vendor);
        }

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($vendorSettings->template, [
            'vendor' => $vendor
        ]);
    }

}
