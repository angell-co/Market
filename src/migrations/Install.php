<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2020 Angell & Co
 */

namespace angellco\market\migrations;

use angellco\market\elements\Vendor;
use craft\db\Migration;

/**
 * @author    Angell & Co
 * @package   Market
 * @since     2.0.0
 */
class Install extends Migration
{
    // Public Methods
    // -------------------------------------------------------------------------

    public function safeUp()
    {
        $this->_renameTables();
        $this->_updateElementReferences();
    }

    public function safeDown()
    {

    }

    // Private Methods
    // -------------------------------------------------------------------------

    private function _renameTables(): void
    {
        if ($this->db->tableExists('{{%marketplace_ordergroups}}')) {
            $this->renameTable('{{%marketplace_ordergroups}}', '{{%market_ordergroups}}');
        }

        if ($this->db->tableExists('{{%marketplace_ordergroups_commerce_orders}}')) {
            $this->renameTable('{{%marketplace_ordergroups_commerce_orders}}', '{{%market_ordergroups_commerce_orders}}');
        }

        if ($this->db->tableExists('{{%marketplace_shippingdestinations}}')) {
            $this->renameTable('{{%marketplace_shippingdestinations}}', '{{%market_shippingdestinations}}');
        }

        if ($this->db->tableExists('{{%marketplace_shippingprofiles}}')) {
            $this->renameTable('{{%marketplace_shippingprofiles}}', '{{%market_shippingprofiles}}');
        }

        if ($this->db->tableExists('{{%marketplace_stripesettings}}')) {
            $this->renameTable('{{%marketplace_stripesettings}}', '{{%market_stripesettings}}');
        }

        if ($this->db->tableExists('{{%marketplace_vendors}}')) {
            $this->renameTable('{{%marketplace_vendors}}', '{{%market_vendors}}');
        }

        if ($this->db->tableExists('{{%marketplace_vendorsettings}}')) {
            $this->renameTable('{{%marketplace_vendorsettings}}', '{{%market_vendorsettings}}');
        }
    }

    private function _updateElementReferences()
    {
        // Elements
        $this->update('{{%elements}}', ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);

        // Fields
//        $this->update('{{%fields}}', ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);
//        $this->update('{{%fields}}', ['type' => Vendors::class], ['type' => 'Marketplace_Vendors']);
//        $this->update('{{%fields}}', ['type' => ShippingProfile::class], ['type' => 'Marketplace_ShippingProfile']);

    }
}
