<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

use Varien_Db_Ddl_Table as Table;

$this->getConnection()->addColumn(
    $this->getTable('ecomdev_sphinx/index_keyword'),
    'category_info',
    [
        'type' => Table::TYPE_TEXT,
        'length' => '1k',
        'nullable' => false,
        'comment' => 'Category Information'
    ]
);

$this->endSetup();
