<?php
/**
 * Market plugin for Craft Commerce
 *
 * A fully-stocked multi-vendor solution for Craft Commerce.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) Angell & Co
 */

namespace angellco\market\migrations;

use angellco\market\db\Table;
use angellco\market\elements\Vendor;
use angellco\market\fields\ShippingProfile;
use angellco\market\fields\Vendors;
use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use craft\db\Table as CraftTable;
use craft\errors\SiteNotFoundException;

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
        // TODO: migration for clean install

        // Upgrade methods - safe to run on new installs
        // TODO: in 2.1 or greater add a check to see if we really need to run these
        $this->_renameTables();
        $this->_fixFksAndIndexes();
        $this->_updateElementReferences();
        $this->_updateVendorSettings();
        $this->_updateStripeSettings();
    }

    public function safeDown()
    {

    }

    // Private Methods
    // -------------------------------------------------------------------------

    private function _renameTables(): void
    {
        if ($this->db->tableExists('{{%marketplace_ordergroups}}')) {
            $this->renameTable('{{%marketplace_ordergroups}}', Table::ORDERGROUPS);
        }

        if ($this->db->tableExists('{{%marketplace_ordergroups_commerce_orders}}')) {
            $this->renameTable('{{%marketplace_ordergroups_commerce_orders}}', Table::ORDERGROUPS_COMMERCEORDERS);
        }

        if ($this->db->tableExists('{{%marketplace_shippingdestinations}}')) {
            $this->renameTable('{{%marketplace_shippingdestinations}}', Table::SHIPPINGDESTINATIONS);
        }

        if ($this->db->tableExists('{{%marketplace_shippingprofiles}}')) {
            $this->renameTable('{{%marketplace_shippingprofiles}}', Table::SHIPPINGPROFILES);
        }

        if ($this->db->tableExists('{{%marketplace_stripesettings}}')) {
            $this->renameTable('{{%marketplace_stripesettings}}', Table::STRIPESETTINGS);
        }

        if ($this->db->tableExists('{{%marketplace_vendors}}')) {
            $this->renameTable('{{%marketplace_vendors}}', Table::VENDORS);
        }

        if ($this->db->tableExists('{{%marketplace_vendorsettings}}')) {
            $this->renameTable('{{%marketplace_vendorsettings}}', Table::VENDORSETTINGS);
        }
    }

    private function _fixFksAndIndexes(): void
    {
        // TODO
        /**
         * ordergroups
         * ordergroups_commerce_orders
         */

        // Drop fks and indexes
        $this->_dropForeignKeys(Table::VENDORS);
        $this->_dropIndexes(Table::VENDORS);
        $this->_dropForeignKeys(Table::SHIPPINGDESTINATIONS);
        $this->_dropIndexes(Table::SHIPPINGDESTINATIONS);
        $this->_dropForeignKeys(Table::SHIPPINGPROFILES);
        $this->_dropIndexes(Table::SHIPPINGPROFILES);

        // Add in the foreign keys
        $this->addForeignKey(null, Table::VENDORS, 'id', CraftTable::ELEMENTS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::VENDORS, 'userId', CraftTable::USERS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::VENDORS, 'profilePictureId', CraftTable::ASSETS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::VENDORS, 'mainFolderId', CraftTable::VOLUMEFOLDERS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::VENDORS, 'accountFolderId', CraftTable::VOLUMEFOLDERS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::VENDORS, 'filesFolderId', CraftTable::VOLUMEFOLDERS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::SHIPPINGDESTINATIONS, 'shippingProfileId', Table::SHIPPINGPROFILES, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::SHIPPINGDESTINATIONS, 'shippingZoneId', CommerceTable::SHIPPINGZONES, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::SHIPPINGPROFILES, 'vendorId', Table::VENDORS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::SHIPPINGPROFILES, 'originCountryId', CommerceTable::COUNTRIES, 'id', 'CASCADE', null);

        // Add back in the indexes
        $this->createIndex(null, Table::VENDORS, 'userId', true);
        $this->createIndex(null, Table::VENDORS, 'code', true);
        $this->createIndex(null, Table::SHIPPINGDESTINATIONS, 'shippingProfileId');
        $this->createIndex(null, Table::SHIPPINGDESTINATIONS, 'shippingZoneId');
        $this->createIndex(null, Table::SHIPPINGPROFILES, 'vendorId');
        $this->createIndex(null, Table::SHIPPINGPROFILES, 'originCountryId');
        $this->createIndex(null, Table::SHIPPINGPROFILES, ['name', 'vendorId'], true);
    }

    private function _updateElementReferences(): void
    {
        // Elements
        $this->update(CraftTable::ELEMENTS, ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);

        // Field layouts
        $this->update(CraftTable::FIELDLAYOUTS, ['type' => Vendor::class], ['type' => 'Marketplace_Vendor']);

        // Fields
        $this->update(CraftTable::FIELDS, ['type' => Vendors::class], ['type' => 'Marketplace_Vendor']);
        $this->update(CraftTable::FIELDS, ['type' => Vendors::class], ['type' => 'Marketplace_Vendors']);
        $this->update(CraftTable::FIELDS, ['type' => ShippingProfile::class], ['type' => 'Marketplace_ShippingProfile']);

    }

    private function _updateVendorSettings(): void
    {
        $tableSchema = $this->db->getTableSchema(Table::VENDORSETTINGS);

        // Drop foreign keys and indexes
        $this->_dropForeignKeys(Table::VENDORSETTINGS);
        $this->_dropIndexes(Table::VENDORSETTINGS);

        // Rename assetSourceId col to volumeId
        if (in_array('assetSourceId', $tableSchema->columnNames, true)) {
            $this->renameColumn(Table::VENDORSETTINGS, 'assetSourceId', 'volumeId');
        }

        // Add siteId col
        if (!in_array('siteId', $tableSchema->columnNames, true)) {
            $this->addColumn(Table::VENDORSETTINGS, 'siteId', $this->integer()->notNull());
        }

        // Update existing row with the primary site ID
        try {
            $site = Craft::$app->getSites()->getPrimarySite();
            if ($site) {
                $this->update(Table::VENDORSETTINGS, ['siteId' => $site->id], ['id' => 1]);
            }
        } catch (SiteNotFoundException $e) {
        }

        // Add in the index and fk for the siteId col
        $this->createIndex(null, Table::VENDORSETTINGS, ['id', 'siteId'], true);
        $this->addForeignKey(null, Table::VENDORSETTINGS, 'siteId', CraftTable::SITES, 'id', 'CASCADE', 'CASCADE');

        // Add other foreign keys and indexes
        $this->addForeignKey(null, Table::VENDORSETTINGS, 'volumeId', CraftTable::VOLUMES, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::VENDORSETTINGS, 'fieldLayoutId', CraftTable::FIELDLAYOUTS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::VENDORSETTINGS, 'shippingOriginId', CommerceTable::COUNTRIES, 'id', 'CASCADE', 'CASCADE');
    }

    private function _updateStripeSettings(): void
    {
        $tableSchema = $this->db->getTableSchema(Table::STRIPESETTINGS);

        // Drop foreign keys and indexes
        $this->_dropForeignKeys(Table::STRIPESETTINGS);
        $this->_dropIndexes(Table::STRIPESETTINGS);

        // Add siteId col
        if (!in_array('siteId', $tableSchema->columnNames, true)) {
            $this->addColumn(Table::STRIPESETTINGS, 'siteId', $this->integer()->notNull());
        }

        // Update existing row with the primary site ID
        try {
            $site = Craft::$app->getSites()->getPrimarySite();
            if ($site) {
                $this->update(Table::STRIPESETTINGS, ['siteId' => $site->id], ['id' => 1]);
            }
        } catch (SiteNotFoundException $e) {
        }

        // Add in the index and fk for the siteId col
        $this->createIndex(null, Table::STRIPESETTINGS, ['id', 'siteId'], true);
        $this->addForeignKey(null, Table::STRIPESETTINGS, 'siteId', CraftTable::SITES, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * @param string $table
     */
    private function _dropForeignKeys(string $table): void
    {
        // Remove old keys
        $keys = $this->db->schema->getTableForeignKeys($table);
        $keyNames = array_map(static function($obj) {
            return $obj->name;
        }, $keys);

        foreach ($keyNames as $keyName) {
            if (!empty($keyName)) {
                $this->dropForeignKey($keyName, $table);
            }
        }
    }

    /**
     * @param string $table
     */
    private function _dropIndexes(string $table): void
    {
        // Remove old indexes
        $indexes = $this->db->schema->getTableIndexes($table);
        $indexNames = array_map(static function($obj) {
            return $obj->name;
        }, $indexes);

        foreach ($indexNames as $indexName) {
            if (!empty($indexName)) {
                $this->dropIndex($indexName, $table);
            }
        }
    }
}
