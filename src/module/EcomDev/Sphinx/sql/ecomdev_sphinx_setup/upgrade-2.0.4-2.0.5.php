<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

use Varien_Db_Ddl_Table as Table;

$connection = $this->getConnection();

$connection->addColumn(
    $this->getTable('ecomdev_sphinx/attribute'),
    'is_child_data',
    [
        'type' => Table::TYPE_INTEGER,
        'length' => 1,
        'nullable' => false,
        'comment' => 'Flag for child data'
    ]
);

$connection->addColumn(
    $this->getTable('ecomdev_sphinx/attribute'),
    'is_child_data_stock',
    [
        'type' => Table::TYPE_INTEGER,
        'length' => 1,
        'nullable' => false,
        'comment' => 'Flag for child data in stock flag'
    ]
);


$connection->addIndex(
    $this->getTable('ecomdev_sphinx/attribute'),
    $this->getIdxName('ecomdev_sphinx/attribute', 'is_child_data'),
    'is_child_data'
);

$connection->addIndex(
    $this->getTable('ecomdev_sphinx/attribute'),
    $this->getIdxName('ecomdev_sphinx/attribute', 'is_child_data_stock'),
    'is_child_data_stock'
);

$select = $this->getConnection()->select()
    ->from($this->getTable('catalog/product_super_attribute'), ['attribute_id'])
    ->group('attribute_id');

$this->getConnection()->update(
    $this->getTable('ecomdev_sphinx/attribute'),
    ['is_child_data' => 1],
    ['attribute_id IN(?)' => $this->getConnection()->fetchCol($select)]
);

$this->endSetup();
