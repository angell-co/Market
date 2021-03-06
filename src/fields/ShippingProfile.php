<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\fields;

use angellco\market\elements\Vendor;
use angellco\market\errors\VendorShippingProfilesNotFoundException;
use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidFieldException;
use craft\fields\BaseOptionsField;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\web\ServerErrorHttpException;

/**
 * @property null|string $settingsHtml
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class ShippingProfile extends BaseOptionsField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('market', 'Shipping Profile');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return SingleOptionFieldData::class;
    }

    /**
     * @inheritdoc
     * @param $value
     * @param ElementInterface|null $element
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws InvalidFieldException
     * @throws Exception
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        /** @var SingleOptionFieldData $value */
        if (!$value->valid) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        }

        // If we have any error when setting the options then just render the error and stop
        if ($error = $this->setOptionsForVendor($element)) {
            return Craft::$app->getView()->renderTemplate('_special/missing-component', [
                'error' => $error,
                'showPlugin' => false
            ]);
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => $this->handle,
            'value' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        // There aren’t really any settings as they are dynamic, but we need
        // this because BaseOptionsField requires it
        return Craft::t('market', 'Shipping Profile Options');
    }

    /**
     * @inheritdoc
     *
     * @param mixed $value
     * @param ElementInterface|null $element
     * @return MultiOptionsFieldData|SingleOptionFieldData|mixed
     * @throws InvalidFieldException
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $this->setOptionsForVendor($element);
        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     *
     * @param mixed $value
     * @param ElementInterface $element
     * @return bool
     * @throws InvalidFieldException
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        $this->setOptionsForVendor($element);
        return parent::isValueEmpty($value, $element);
    }

    /**
     * @inheritdoc
     *
     * @param ElementInterface $element
     * @param bool $isNew
     * @return bool
     * @throws InvalidFieldException
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $this->setOptionsForVendor($element);
        return parent::beforeElementSave($element, $isNew);
    }

    /**
     * Sets the options for the attached Vendor, returns an error if it can’t.
     *
     * @param ElementInterface|null $element
     * @return string|null
     * @throws InvalidFieldException
     */
    public function setOptionsForVendor(ElementInterface $element = null): ?string
    {
        $error = null;
        $vendor = null;
        $shippingProfiles = null;

        // Check we are attached to an element
        if (!$element) {
            $error = Craft::t('market', 'This field only works if attached to an element.');
        }

        // Get the vendor
        /** @var Vendor $vendor */
        if (!$error) {
            $vendorQuery = $element->getFieldValue('vendor');
            $vendor = $vendorQuery->one();
            if (!$vendor) {
                $error = Craft::t('market', 'Please add a Vendor and save first.');
            }
        }

        // Get the vendor shipping options
        if (!$error) {
            try {
                $shippingProfiles = $vendor->getShippingProfiles();
            } catch (VendorShippingProfilesNotFoundException $e) {
                if (Craft::$app->request->isSiteRequest) {
                    $error = Craft::t('market', 'You don’t have any shipping profiles configured yet, please create one first.');
                } else {
                    $error = Craft::t('market', 'This Vendor doesn’t yet have any shipping profiles, please create some first.');
                }
            }
        }

        // If we had any errors then return the error and stop
        if ($error) {
            return $error;
        }

        // If we got this far we can set the options and display the field
        $this->options = [];
        foreach ($shippingProfiles as $shippingProfile) {
            $this->options[] = [
                'label' => $shippingProfile->name,
                'value' => $shippingProfile->id,
            ];
        }

        return null;
    }

}
