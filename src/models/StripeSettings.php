<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\models;
use Craft;
use craft\base\Model;
use craft\models\Site;
use yii\base\InvalidConfigException;

/**
 * Stripe Settings model
 *
 * @property Site $site
 * @property array $config
 *
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class StripeSettings extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var string Client ID
     */
    public $clientId;

    /**
     * @var string Secret key
     */
    public $secretKey;

    /**
     * @var string Publishable key
     */
    public $publishableKey;

    /**
     * @var string Redirect success URI
     */
    public $redirectSuccess;

    /**
     * @var string Redirect error URI
     */
    public $redirectError;

    /**
     * @var string UID
     */
    public $uid;

    /**
     * @var Site
     */
    private $_site;

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function __toString()
    {
        if ($this->getSite()) {
            return Craft::t('market', 'Stripe Settings ({siteName})', ['siteName' => $this->getSite()->name]);
        }

        return (string) $this->id;
    }


    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['id', 'siteId'], 'number', 'integerOnly' => true];
        $rules[] = [['siteId', 'clientId', 'secretKey', 'publishableKey', 'redirectSuccess', 'redirectError'], 'required'];

        return $rules;
    }

    /**
     * Returns the Site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if ($this->_site !== null) {
            return $this->_site;
        }

        if (!$this->siteId) {
            throw new InvalidConfigException('Stripe settings is missing its site ID');
        }

        if (($this->_site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $this->_site;
    }

    /**
     * Returns the project config array for this settings model.
     *
     * @return array
     * @throws InvalidConfigException
     */
    public function getConfig(): array
    {
        return [
            'site' => $this->getSite()->uid,
            'clientId' => $this->clientId,
            'secretKey' => $this->secretKey,
            'publishableKey' => $this->publishableKey,
            'redirectSuccess' => $this->redirectSuccess,
            'redirectError' => $this->redirectError,
        ];
    }
}
