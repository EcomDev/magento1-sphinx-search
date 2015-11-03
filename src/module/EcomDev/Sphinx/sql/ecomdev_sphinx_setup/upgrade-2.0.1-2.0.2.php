<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

// Remove redundant triggers

$triggers = $this->getConnection()->fetchAll(
    'show triggers where `Table` = :category_product and `Trigger` like :sphinx_prefix',
    [
        'category_product' => $this->getTable('catalog/category_product_index'),
        'sphinx_prefix' => 'ecomdev_sphinx%'
    ]
);

foreach ($triggers as $row) {
    $this->getConnection()->dropTrigger($row['Trigger']);
}

$this->endSetup();
