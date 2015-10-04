<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

use Varien_Db_Ddl_Table as Table;
use Varien_Db_Adapter_Interface as Adapter;

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/index_keyword'));
$table
    ->addColumn('keyword_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, ['nullable' => false, 'primary' => true, 'identity' => true], 'Keyword')
    ->addColumn('keyword', Table::TYPE_TEXT, 255, ['nullable' => false], 'Keyword')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, 5,  ['nullable' => false], 'Store')
    ->addColumn('trigram_list', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [], 'Trigram Lists')
    ->addColumn('frequency', Varien_Db_Ddl_Table::TYPE_INTEGER, null, ['nullable' => false])
    ->addIndex(
        $this->getIdxName('ecomdev_sphinx/word_form', ['keyword', 'store_id'], Adapter::INDEX_TYPE_UNIQUE),
        ['keyword', 'store_id'],
        ['type' => Adapter::INDEX_TYPE_UNIQUE]
    )
    ->addIndex(
        $this->getIdxName('ecomdev_sphinx/index_keyword', 'frequency'),
        'frequency'
    )
    ->addForeignKey(
        $this->getFkName('ecomdev_sphinx/index_keyword', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Adapter::FK_ACTION_CASCADE,
        Adapter::FK_ACTION_NO_ACTION
    )
;

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('ecomdev_sphinx/word_form'));
$table
    ->addColumn('word_form_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, ['nullable' => false, 'primary' => true, 'identity' => true], 'Keyword')
    ->addColumn('locale', Varien_Db_Ddl_Table::TYPE_TEXT, 5,  ['nullable' => false], 'Locale')
    ->addColumn('source', Varien_Db_Ddl_Table::TYPE_TEXT, 255, ['nullable' => false], 'Source word form')
    ->addColumn('target', Varien_Db_Ddl_Table::TYPE_TEXT, 255, ['nullable' => false], 'Target word form')
    ->addIndex(
        $this->getIdxName('ecomdev_sphinx/word_form', ['locale', 'source'], Adapter::INDEX_TYPE_UNIQUE),
        ['locale', 'source'],
        ['type' => Adapter::INDEX_TYPE_UNIQUE]
    )
;

$this->getConnection()->createTable($table);

$this->endSetup();
