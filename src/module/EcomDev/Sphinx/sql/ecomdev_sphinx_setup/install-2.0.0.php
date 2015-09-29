<?php

use Varien_Db_Ddl_Table as Table;
use Varien_Db_Adapter_Interface as Adapter;

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/attribute'));
$table
    ->addColumn('attribute_id', Table::TYPE_SMALLINT, null, ['unsigned' => true, 'primary' => true])
    ->addColumn('is_fulltext', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('is_layered', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('is_active', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('is_system', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('is_seo', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('is_custom_value_allowed', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('is_sort', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('position', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => 0])
    ->addColumn('filter_type', Varien_Db_Ddl_Table::TYPE_TEXT, 255)
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/attribute', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id', 
        Adapter::FK_ACTION_CASCADE, Adapter::FK_ACTION_CASCADE
    )
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_fulltext'), 'is_fulltext')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_layered'), 'is_layered')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_sort'), 'is_sort')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_active'), 'is_active')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_seo'), 'is_seo')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_system'), 'is_system')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'position'), 'position')
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/field'));
$table
    ->addColumn('field_id', Table::TYPE_INTEGER, null, ['unsigned' => true,
        'primary' => true,
        'identity' => true])
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 255, ['nullable' => false], 'Type of the field')
    ->addColumn('code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, ['nullable' => false], 'Unique code of the field')
    ->addColumn('position', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => 0])
    ->addColumn('is_active', Table::TYPE_INTEGER, 1, ['unsigned' => true, 'nullable' => false])
    ->addColumn('configuration', Varien_Db_Ddl_Table::TYPE_TEXT, '512k', ['nullable' => false], 'Configuration of the field')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_active'), 'is_active')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'position'), 'position')
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/sort'));
$table
    ->addColumn('sort_id', Table::TYPE_INTEGER, null, ['unsigned' => true,
        'identity' => true,
        'primary' => true])
    ->addColumn('code', Table::TYPE_TEXT, 255, ['nullable' => false])
    ->addColumn('configuration', Table::TYPE_TEXT, '512k', array(), 'Configuration of the sort order')
    ->addIndex($this->getIdxName('ecomdev_sphinx/sort', 'code', Adapter::INDEX_TYPE_UNIQUE), 'code', ['type' => Adapter::INDEX_TYPE_UNIQUE])
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/scope'));
$table
    ->addColumn('scope_id', Table::TYPE_INTEGER, null, ['unsigned' => true,
        'primary' => true,
        'identity' => true])
    ->addColumn('parent_id', Table::TYPE_INTEGER, null, ['unsigned' => true,
        'nullable' => true])
    ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false])
    ->addColumn('configuration', Table::TYPE_TEXT, '512k', array())
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/scope', 'parent_id', 'ecomdev_sphinx/scope', 'scope_id'),
        'parent_id', $this->getTable('ecomdev_sphinx/scope'), 'scope_id',
        Adapter::FK_ACTION_CASCADE, Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_metadata'));
$table
    ->addColumn('code', Table::TYPE_TEXT, 255, ['primary' => true])
    ->addColumn('store_id', Table::TYPE_SMALLINT, null, ['primary' => true])
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_metadata', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Adapter::FK_ACTION_CASCADE,
        Adapter::FK_ACTION_NO_ACTION
    )
    ->addColumn('previous_reindex_at', Table::TYPE_DATETIME)
    ->addColumn('current_reindex_at', Table::TYPE_DATETIME)
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_category'));

$table
    ->addColumn('category_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'primary' => true], 'Category ID')
    ->addColumn('store_id', Table::TYPE_SMALLINT, null, ['unsigned' => true, 'primary' => true], 'Store ID')
    ->addColumn('path', Table::TYPE_TEXT, 255, [], 'Path of the category')
    ->addColumn('is_active', Table::TYPE_TEXT, 255, array(), 'Is category active')
    ->addColumn('position', Table::TYPE_INTEGER, null, array(), 'Category position')
    ->addColumn('level', Table::TYPE_INTEGER, null, array(), 'Category level')
    ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Latest record update')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_category', 'level'), 'level')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_category', 'updated_at'), 'updated_at')
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_category', 'category_id', 'catalog/category', 'entity_id'),
        'category_id', $this->getTable('catalog/category'), 'entity_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_category', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product'));

$table
    ->addColumn('product_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'primary' => true], 'Product identifier')
    ->addColumn('store_id', Table::TYPE_SMALLINT, null, ['unsigned' => true, 'primary' => true], 'Store identifier')
    ->addColumn('visibility', Table::TYPE_INTEGER, 1, ['unsigned' => true], 'Product visibility for store')
    ->addColumn('status', Table::TYPE_INTEGER, 1, ['unsigned' => true], 'Product status for store')
    ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Latest record update')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_product', 'visibility'), 'visibility')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_product', 'status'), 'status')
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product', 'product_id', 'catalog/product', 'entity_id'),
        'product_id', $this->getTable('catalog/product'), 'entity_id',
        Adapter::FK_ACTION_CASCADE,
        Adapter::FK_ACTION_NO_ACTION
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Adapter::FK_ACTION_NO_ACTION,
        Adapter::FK_ACTION_NO_ACTION
    )
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable(
    $this->getTable('ecomdev_sphinx/index_deleted')
);

$table
    ->addColumn('type', Table::TYPE_TEXT, 32, ['nullable' => false, 'primary' => true], 'Type of the deleted record')
    ->addColumn('entity_id', Table::TYPE_INTEGER, null, ['nullable' => false, 'unsigned' => true, 'primary' => true],  'Identifier of the deleted entity')
    ->addColumn('deleted_at', Table::TYPE_DATETIME, null, ['nullable' => false])
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_deleted', 'deleted_at'), 'deleted_at')
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable(
    $this->getTable('ecomdev_sphinx/index_updated')
);

$table
    ->addColumn('type', Table::TYPE_TEXT, 32, ['nullable' => false, 'primary' => true], 'Type of the updated record')
    ->addColumn('entity_id', Table::TYPE_INTEGER, null, ['nullable' => false, 'unsigned' => true, 'primary' => true],  'Identifier of the updated entity')
    ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => false])
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_updated', 'updated_at'), 'updated_at')
;

$this->getConnection()->createTable($table);

$this->endSetup();
