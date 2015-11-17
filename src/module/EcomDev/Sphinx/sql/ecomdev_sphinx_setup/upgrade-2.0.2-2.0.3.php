<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

use Varien_Db_Ddl_Table as Table;
use Varien_Db_Adapter_Interface as Adapter;

$connection = $this->getConnection();

$connection->addColumn(
    $this->getTable('ecomdev_sphinx/sort'),
    'name',
    [
        'type' => Table::TYPE_TEXT,
        'length' => 255,
        'nullable' => false,
        'comment' => 'Name of the sort order'
    ]
);

$connection->addColumn(
    $this->getTable('ecomdev_sphinx/sort'),
    'position',
    [
        'type' => Table::TYPE_INTEGER,
        'length' => null,
        'nullable' => false,
        'default' => 0,
        'comment' => 'Name of the sort order'
    ]
);


$connection->addColumn(
    $this->getTable('ecomdev_sphinx/field'),
    'name',
    [
        'type' => Table::TYPE_TEXT,
        'length' => 255,
        'nullable' => false,
        'comment' => 'Is usable in sort?'
    ]
);

$connection->addColumn(
    $this->getTable('ecomdev_sphinx/field'),
    'is_sort',
    [
        'type' => Table::TYPE_INTEGER,
        'length' => 1,
        'nullable' => false,
        'unsigned' => true,
        'default' => 0,
        'comment' => 'Is usable in sort?'
    ]
);

$connection->addIndex(
    $this->getTable('ecomdev_sphinx/sort'),
    $this->getIdxName('ecomdev_sphinx/sort', 'position'),
    'position'
);

$connection->addIndex(
    $this->getTable('ecomdev_sphinx/field'),
    $this->getIdxName('ecomdev_sphinx/field', 'is_sort'),
    'is_sort'
);

$this->endSetup();
