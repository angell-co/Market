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

use angellco\market\elements\Vendor;
use angellco\market\Market;
use Craft;
use craft\base\Element;
use craft\errors\SiteNotFoundException;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
    const EVENT_PREVIEW_VENDOR = 'previewVendor';

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
     * Previews a category.
     *
     * @return Response
     */
    public function actionPreviewCategory(): Response
    {
        $this->requirePostRequest();

        $vendor = $this->_getCategoryModel();
        $this->_enforceEditVendorPermissions($vendor);
        $this->_populateCategoryModel($vendor);

        // Fire a 'previewCategory' event
        if ($this->hasEventHandlers(self::EVENT_PREVIEW_CATEGORY)) {
            $this->trigger(self::EVENT_PREVIEW_CATEGORY, new ElementEvent([
                'element' => $vendor,
            ]));
        }

        return $this->_showCategory($vendor);
    }

    /**
     * Saves an category.
     *
     * @return Response|null
     * @throws ServerErrorHttpException
     */
    public function actionSaveCategory()
    {
        $this->requirePostRequest();

        $vendor = $this->_getCategoryModel();
        $vendorVariable = $this->request->getValidatedBodyParam('categoryVariable') ?? 'vendor';

        // Permission enforcement
        $this->_enforceEditVendorPermissions($vendor);

        // Are we duplicating the category?
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

                $this->setFailFlash(Craft::t('app', 'Couldn’t duplicate category.'));

                // Send the original vendor back to the template, with any validation errors on the clone
                $vendor->addErrors($clone->getErrors());
                Craft::$app->getUrlManager()->setRouteParams([
                    'vendor' => $vendor
                ]);

                return null;
            } catch (\Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('app', 'An error occurred when duplicating the category.'), 0, $e);
            }
        }

        // Populate the vendor with post data
        $this->_populateCategoryModel($vendor);

        // Save the category
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

            $this->setFailFlash(Craft::t('app', 'Couldn’t save category.'));

            // Send the vendor back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                $vendorVariable => $vendor
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $vendor->id,
                'title' => $vendor->title,
                'slug' => $vendor->slug,
                'status' => $vendor->getStatus(),
                'url' => $vendor->getUrl(),
                'cpEditUrl' => $vendor->getCpEditUrl()
            ]);
        }

        $this->setSuccessFlash(Craft::t('app', 'vendor saved.'));
        return $this->redirectToPostedUrl($vendor);
    }

    /**
     * Deletes a category.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested vendor cannot be found
     */
    public function actionDeleteCategory()
    {
        $this->requirePostRequest();

        $vendorId = $this->request->getRequiredBodyParam('vendorId');
        $vendor = Craft::$app->getCategories()->getCategoryById($vendorId);

        if (!$vendor) {
            throw new NotFoundHttpException('vendor not found');
        }

        // Make sure they have permission to do this
        $this->requirePermission('editCategories:' . $vendor->getGroup()->uid);

        // Delete it
        if (!Craft::$app->getElements()->deleteElement($vendor)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete category.'));

            // Send the vendor back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'vendor' => $vendor
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', 'vendor deleted.'));
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
     * @throws ServerErrorHttpException if the vendor group is not configured properly
     */
    public function actionShareCategory(int $vendorId, int $siteId = null): Response
    {
        $vendor = Craft::$app->getCategories()->getCategoryById($vendorId, $siteId);

        if (!$vendor) {
            throw new NotFoundHttpException('vendor not found');
        }

        // Make sure they have permission to be viewing this category
        $this->_enforceEditVendorPermissions($vendor);

        // Make sure the vendor actually can be viewed
        if (!Craft::$app->getCategories()->isGroupTemplateValid($vendor->getGroup(), $vendor->siteId)) {
            throw new ServerErrorHttpException('vendor group not configured properly');
        }

        // Create the token and redirect to the vendor URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'categories/view-shared-category',
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
     * Shows an category/draft/version based on a token.
     *
     * @param int $vendorId
     * @param int|null $siteId
     * @return Response
     * @throws NotFoundHttpException if the requested vendor cannot be found
     */
    public function actionViewSharedCategory(int $vendorId, int $siteId = null): Response
    {
        $this->requireToken();

        $vendor = Craft::$app->getCategories()->getCategoryById($vendorId, $siteId);

        if (!$vendor) {
            throw new NotFoundHttpException('vendor not found');
        }

        return $this->_showCategory($vendor);
    }

    /**
     * Preps vendor variables.
     *
     * @param array &$variables
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     * @throws NotFoundHttpException if the requested vendor cannot be found
     * @throws SiteNotFoundException|InvalidConfigException
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
     * Fetches or creates a Category.
     *
     * @return Category
     * @throws BadRequestHttpException if the requested vendor group doesn't exist
     * @throws NotFoundHttpException if the requested vendor cannot be found
     */
    private function _getCategoryModel(): Category
    {
        $vendorId = $this->request->getBodyParam('vendorId');
        $siteId = $this->request->getBodyParam('siteId');

        if ($vendorId) {
            $vendor = Craft::$app->getCategories()->getCategoryById($vendorId, $siteId);

            if (!$vendor) {
                throw new NotFoundHttpException('vendor not found');
            }
        } else {
            $groupId = $this->request->getRequiredBodyParam('groupId');
            if (($group = Craft::$app->getCategories()->getGroupById($groupId)) === null) {
                throw new BadRequestHttpException('Invalid vendor group ID: ' . $groupId);
            }

            $vendor = new Category();
            $vendor->groupId = $group->id;
            $vendor->fieldLayoutId = $group->fieldLayoutId;

            if ($siteId) {
                $vendor->siteId = $siteId;
            }
        }

        return $vendor;
    }

    /**
     * Enforces all Edit vendor permissions.
     *
     * @param vendor $vendor
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
     * Populates an vendor with post data.
     *
     * @param vendor $vendor
     */
    private function _populateCategoryModel(vendor $vendor)
    {
        // Set the vendor attributes, defaulting to the existing values for whatever is missing from the post data
        $vendor->slug = $this->request->getBodyParam('slug', $vendor->slug);
        $vendor->enabled = (bool)$this->request->getBodyParam('enabled', $vendor->enabled);

        $vendor->title = $this->request->getBodyParam('title', $vendor->title);

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $vendor->setFieldValuesFromRequest($fieldsLocation);

        // Parent
        if (($parentId = $this->request->getBodyParam('parentId')) !== null) {
            if (is_array($parentId)) {
                $parentId = reset($parentId) ?: '';
            }

            $vendor->newParentId = $parentId ?: '';
        }
    }

    /**
     * Displays a category.
     *
     * @param vendor $vendor
     * @return Response
     * @throws ServerErrorHttpException if the vendor doesn't have a URL for the site it's configured with, or if the category's site ID is invalid
     */
    private function _showCategory(vendor $vendor): Response
    {
        $vendorGroupSiteSettings = $vendor->getGroup()->getSiteSettings();

        if (!isset($vendorGroupSiteSettings[$vendor->siteId]) || !$vendorGroupSiteSettings[$vendor->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The vendor ' . $vendor->id . ' doesn’t have a URL for the site ' . $vendor->siteId . '.');
        }

        $site = Craft::$app->getSites()->getSiteById($vendor->siteId, true);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: ' . $vendor->siteId);
        }

        Craft::$app->language = $site->language;
        Craft::$app->set('locale', Craft::$app->getI18n()->getLocaleById($site->language));

        // Have this vendor override any freshly queried categories with the same ID/site
        if ($vendor->id) {
            Craft::$app->getElements()->setPlaceholderElement($vendor);
        }

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($vendorGroupSiteSettings[$vendor->siteId]->template, [
            'vendor' => $vendor
        ]);
    }

}
