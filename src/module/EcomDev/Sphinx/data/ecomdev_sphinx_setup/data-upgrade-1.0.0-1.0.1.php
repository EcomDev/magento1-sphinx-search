<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$rows = array();

$select = $this->getConnection()->select();

$select->from($this->getTable('catalog/eav_attribute'), array('attribute_id', 'position'))
    ->where('is_filterable = ?', '1')
    ->orWhere('is_filterable_in_search = ?', '1');

foreach ($this->getConnection()->fetchPairs($select) as $attributeId => $position) {
    $rows[] = array(
        'attribute_id' => $attributeId,
        'is_fulltext' => 0,
        'is_layered' => 1,
        'is_active' => 1,
        'is_system' => 0,
        'position' => $position,
        'is_sort' => 0,
        'filter_type' => EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_MULTIPLE,
        'is_custom_value_allowed' => 0
    );
}

$this->getConnection()->insertOnDuplicate(
    $this->getTable('ecomdev_sphinx/attribute'),
    $rows,
    array('is_layered', 'position')
);
