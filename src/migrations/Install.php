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
        // Upgrade methods - safe to run on new installs
        $this->_renameTables();
        $this->_fixFksAndIndexes();
        $this->_updateElementReferences();
        $this->_updateVendorSettings();
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

    private function _fixFksAndIndexes(): void
    {
        // Remove old keys
        $keys = $this->db->schema->getTableForeignKeys('{{%market_vendors}}');
        $keyNames = array_map(static function($obj) {
            return $obj->name;
        }, $keys);

        $fks = [
            'craft_marketplace_vendors_id_fk',
            'craft_craft_marketplace_vendors_userId_fk',
            'craft_craft_marketplace_vendors_profilePictureId_fk',
            'craft_craft_marketplace_vendors_mainFolderId_fk',
            'craft_craft_marketplace_vendors_accountFolderId_fk',
            'craft_craft_marketplace_vendors_filesFolderId_fk',
        ];

        foreach ($keyNames as $keyName) {
            if (!empty($keyName)) {
                $this->dropForeignKey($keyName, '{{%market_vendors}}');
            }
        }

        // Remove old indexes
        $indexes = $this->db->schema->getTableIndexes('{{%market_vendors}}');
        $indexNames = array_map(static function($obj) {
            return $obj->name;
        }, $indexes);

        foreach ($indexNames as $indexName) {
            if (!empty($indexName)) {
                $this->dropIndex($indexName, '{{%market_vendors}}');
            }
        }

        // Add in the foreign keys
        $this->addForeignKey(null, '{{%market_vendors}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%market_vendors}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%market_vendors}}', 'profilePictureId', '{{%assets}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%market_vendors}}', 'mainFolderId', '{{%volumefolders}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%market_vendors}}', 'accountFolderId', '{{%volumefolders}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%market_vendors}}', 'filesFolderId', '{{%volumefolders}}', 'id', 'SET NULL', null);

        // Add back in the indexes
        $this->createIndex(null, '{{%market_vendors}}', 'userId', true);
        $this->createIndex(null, '{{%market_vendors}}', 'code', true);
    }

    private function _updateElementReferences(): void
    {
        // Elements
        $this->update('{{%elements}}', ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);

        // Field layouts
        $this->update('{{%fieldlayouts}}', ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);

        // Fields
//        $this->update('{{%fields}}', ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);
//        $this->update('{{%fields}}', ['type' => Vendors::class], ['type' => 'Marketplace_Vendors']);
//        $this->update('{{%fields}}', ['type' => ShippingProfile::class], ['type' => 'Marketplace_ShippingProfile']);

    }

    private function _updateVendorSettings(): void
    {

        // TODO
//        assetSourceId renamed to volumeId
        // Added siteId and updated existing row to primary site
    }
}
