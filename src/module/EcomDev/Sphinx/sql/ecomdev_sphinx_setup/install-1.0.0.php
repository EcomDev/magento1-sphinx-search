<?php

use Varien_Db_Ddl_Table as Table;
use Varien_Db_Adapter_Interface as Adapter;

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/attribute'));
$table
    ->addColumn('attribute_id', Table::TYPE_SMALLINT, null, array(
        'unsigned' => true, 
        'primary' => true
    ))
    ->addColumn('is_fulltext', Table::TYPE_INTEGER, 1, array('unsigned' => true, 'nullable' => false))
    ->addColumn('is_layered', Table::TYPE_INTEGER, 1, array('unsigned' => true, 'nullable' => false))
    ->addColumn('is_active', Table::TYPE_INTEGER, 1, array('unsigned' => true, 'nullable' => false))
    ->addColumn('is_system', Table::TYPE_INTEGER, 1, array('unsigned' => true, 'nullable' => false))
    ->addColumn('is_custom_value_allowed', Table::TYPE_INTEGER, 1, array('unsigned' => true, 'nullable' => false))
    ->addColumn('is_sort', Table::TYPE_INTEGER, 1, array('unsigned' => true, 'nullable' => false))
    ->addColumn('position', Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false, 'default' => 0))
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
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'is_system'), 'is_system')
    ->addIndex($this->getIdxName('ecomdev_sphinx/attribute', 'position'), 'position')
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/sort'));
$table
    ->addColumn('sort_id', Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'identity' => true,
        'primary' => true
    ))
    ->addColumn('code', Table::TYPE_TEXT, 255, array('nullable' => false))
    ->addColumn('configuration', Table::TYPE_TEXT, '512k', array(), 'Configuration of the sort order')
    ->addIndex($this->getIdxName('ecomdev_sphinx/sort', 'code', Adapter::INDEX_TYPE_UNIQUE), 'code', array('type' => Adapter::INDEX_TYPE_UNIQUE))
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/scope'));
$table
    ->addColumn('scope_id', Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'primary' => true,
        'identity' => true
    ))
    ->addColumn('parent_id', Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true
    ))
    ->addColumn('name', Table::TYPE_TEXT, 255, array('nullable' => false))
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
    ->addColumn('code', Table::TYPE_TEXT, 255, array(
        'primary' => true
    ))
    ->addColumn('previous_reindex_at', Table::TYPE_DATETIME)
    ->addColumn('current_reindex_at', Table::TYPE_DATETIME)
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_category'));

$table
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'primary' => true,
        'identity' => true
    ))
    ->addColumn('category_id', Table::TYPE_INTEGER, null, array(
        'unsigned' => true
    ))
    ->addColumn('store_id', Table::TYPE_SMALLINT, null, array(
        'unsigned' => true
    ))
    ->addColumn('path', Table::TYPE_TEXT, 255, array())
    ->addColumn('name', Table::TYPE_TEXT, 255, array())
    ->addColumn('is_active', Table::TYPE_TEXT, 255, array())
    ->addColumn('request_path', Table::TYPE_TEXT, 255, array())
    ->addColumn('position', Table::TYPE_INTEGER)
    ->addColumn('level', Table::TYPE_INTEGER)
    ->addColumn('include_in_menu', Table::TYPE_INTEGER, null, array('default' => 0), 'Include in Menu flag')
    ->addColumn('image', Table::TYPE_TEXT, 255, array(), 'Image')
    ->addColumn('thumbnail', Table::TYPE_TEXT, 255, array(), 'Thumbnail Image')
    ->addColumn('description', Table::TYPE_TEXT, '512k', array(), 'Description of category')
    ->addColumn('updated_at', Table::TYPE_DATETIME)
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_category', 'updated_at'), 'updated_at')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_category', 'level'), 'level')
    ->addIndex(
        $this->getIdxName(
            'ecomdev_sphinx/index_category',
            array('category_id', 'store_id'), 
            Adapter::INDEX_TYPE_UNIQUE
        ),
        array('category_id','store_id'),
        array('type' => Adapter::INDEX_TYPE_UNIQUE)
    )
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
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'primary' => true,
        'identity' => true
    ))
    ->addColumn('product_id', Table::TYPE_INTEGER, null, array('unsigned' => true))
    ->addColumn('store_id', Table::TYPE_SMALLINT, null, array('unsigned' => true))
    ->addColumn('type_id', Table::TYPE_TEXT, 255, array())
    ->addColumn('attribute_set_id', Table::TYPE_INTEGER, null, array(), 'Attribute set id')
    ->addColumn('tax_class_id', Table::TYPE_INTEGER, null, array('unsigned' => true))
    ->addColumn('visibility', Table::TYPE_INTEGER, 1, array('unsigned' => true))
    ->addColumn('status', Table::TYPE_INTEGER, 1, array('unsigned' => true))
    ->addColumn('sku', Table::TYPE_TEXT, 255, array())
    ->addColumn('name', Table::TYPE_TEXT, 255, array())
    ->addColumn('short_description', Table::TYPE_TEXT, '512k', array())
    ->addColumn('description', Table::TYPE_TEXT, '512k', array())
    ->addColumn('request_path', Table::TYPE_TEXT, 255, array())
    ->addColumn('category_names', Table::TYPE_TEXT, '512k')
    ->addColumn('stock_status', Table::TYPE_INTEGER, 1, array('unsigned' => true))
    ->addColumn('has_options', Table::TYPE_INTEGER, null, array(), 'Has options flag')
    ->addColumn('required_options', Table::TYPE_INTEGER, null, array(), 'Has required options flag')
    ->addColumn('updated_at', Table::TYPE_DATETIME)
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_product', 'visibility'), 'visibility')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_product', 'status'), 'status')
    ->addIndex($this->getIdxName('ecomdev_sphinx/index_product', 'updated_at'), 'updated_at')
    ->addIndex(
        $this->getIdxName(
            'ecomdev_sphinx/index_product', array('product_id', 'store_id'), Adapter::INDEX_TYPE_UNIQUE
        ),
        array('product_id','store_id'),
        array('type' => Adapter::INDEX_TYPE_UNIQUE)
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product', 'product_id', 'catalog/product', 'entity_id'),
        'product_id', $this->getTable('catalog/product'), 'entity_id',
        Adapter::FK_ACTION_CASCADE,
        Adapter::FK_ACTION_NO_ACTION
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Adapter::FK_ACTION_CASCADE,
        Adapter::FK_ACTION_NO_ACTION
    )
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_option'));

$table
    ->addColumn('attribute_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('option_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('label', Table::TYPE_TEXT, 255, array());

$idxTable = clone $table;

$table
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_option', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_option', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_option', 'option_id', 'eav/attribute_option', 'option_id'),
        'option_id', $this->getTable('eav/attribute_option'), 'option_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_option_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_option_tmp')
    )
);


$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_integer'));

$table
    ->addColumn('attribute_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('value', Table::TYPE_INTEGER);

$idxTable = clone $table;

$table
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_integer', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_integer', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);
$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_integer_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_integer_tmp')
    )
);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_string'));

$table
    ->addColumn('attribute_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('value', Table::TYPE_TEXT, 255, array());

$idxTable = clone $table;

$table
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_string', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_string', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);
$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_string_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_string_tmp')
    )
);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_text'));

$table
    ->addColumn('attribute_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('value', Table::TYPE_TEXT, '512k', array());

$idxTable = clone $table;

$table
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_text', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_text', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);
$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_text_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_text_tmp')
    )
);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_decimal'));

$table
    ->addColumn('attribute_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('value', Table::TYPE_DECIMAL, array(12,4), array());

$idxTable = clone $table;

$table->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_decimal', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_decimal', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);
$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_decimal_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_decimal_tmp')
    )
);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_timestamp'));

$table
    ->addColumn('attribute_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('value', Table::TYPE_DATETIME);

$idxTable = clone $table;

$table
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_timestamp', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_timestamp', 'attribute_id', 'eav/attribute', 'attribute_id'),
        'attribute_id', $this->getTable('eav/attribute'), 'attribute_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);
$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_timestamp_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_timestamp_tmp')
    )
);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_product_price'));

$table
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('customer_group_id', Table::TYPE_INTEGER, null, array('primary' => true))
    ->addColumn('price', Table::TYPE_DECIMAL, array(12,4), array())
    ->addColumn('final_price', Table::TYPE_DECIMAL, array(12,4), array())
    ->addColumn('min_price', Table::TYPE_DECIMAL, array(12,4), array())
    ->addColumn('max_price', Table::TYPE_DECIMAL, array(12,4), array())
;

$idxTable = clone $table;

$table
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_price', 'document_id', 'ecomdev_sphinx/index_product', 'document_id'),
        'document_id', $this->getTable('ecomdev_sphinx/index_product'), 'document_id',
        Adapter::FK_ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_product_price', 'customer_group_id', 'customer/customer_group', 'customer_group_id'),
        'customer_group_id', $this->getTable('customer/customer_group'), 'customer_group_id',
        Adapter::FK_ACTION_CASCADE
    )
;

$this->getConnection()->createTable($table);
$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_price_idx')
    )
);

$this->getConnection()->createTable(
    $idxTable->setName(
        $this->getTable('ecomdev_sphinx/index_product_price_tmp')
    )
);

$table = $this->getConnection()->newTable(
    $this->getTable('ecomdev_sphinx/index_deleted')
);

$table
    ->addColumn('type', Table::TYPE_TEXT, 32, array('nullable' => false, 'primary' => true), 'Type of the delete record')
    ->addColumn('document_id', Table::TYPE_INTEGER, null, array('nullable' => false, 'unsigned' => true, 'primary' => true),  'Identifier of the deleted document');

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable(
    $this->getTable('ecomdev_sphinx/index_store')
);

$table
    ->addColumn('type', Table::TYPE_TEXT, 32, array('nullable' => false, 'primary' => true), 'Type of index')
    ->addColumn('store_id', Table::TYPE_INTEGER, null, array('nullable' => false, 'unsigned' => true), 'Current store')
;

$this->getConnection()->createTable($table);


$this->endSetup();
