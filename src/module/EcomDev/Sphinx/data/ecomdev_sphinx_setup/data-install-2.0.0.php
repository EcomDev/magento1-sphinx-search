<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$attributes = array(
    'name' => array(true, false, null, false, true),
    'short_description' => array(true, false, null, false, false),
    'description' => array(true, false, null, false, false),
    'sku' => array(true, false, null, false, false),
    'price' => array(false, true, EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_RANGE, true, true)
);

$eavConfig =  Mage::getSingleton('eav/config');

$rows = array();
foreach ($attributes as $attributeCode => $data) {
    list($isFulltext, $isLayer, $filterType, $isCustomValue, $isSort) = $data;
    $rows[] = array(
        'attribute_id' => $eavConfig->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode)->getId(),
        'is_fulltext' => (int)$isFulltext,
        'is_layered' => (int)$isLayer,
        'is_active' => 1,
        'is_system' => 1,
        'is_sort' => (int)$isSort,
        'filter_type' => $filterType,
        'is_custom_value_allowed' => (int)$isCustomValue
    );
}

if ($rows) {
    $this->getConnection()->insertOnDuplicate(
        $this->getTable('ecomdev_sphinx/attribute'),
        $rows
    );
}

$rows = array();

$select = $this->getConnection()->select();
$select->from($this->getTable('catalog/eav_attribute'), array('attribute_id', 'position'))
    ->where('is_filterable = ?', '1')
    ->orWhere('is_filterable_in_search = ?', '1');

$attributeLayered = $this->getConnection()->fetchPairs($select);

$additionalAttributes = Mage::getSingleton('catalog/config')->getProductAttributes();
foreach ($additionalAttributes as $attributeCode) {
    if (isset($attributes[$attributeCode])) {
        continue;
    }
    $attribute = $eavConfig->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);
    if ($attribute->isStatic()) {
        continue;
    }

    $rows[] = array(
        'attribute_id' => $attribute->getId(),
        'is_fulltext' => 0,
        'is_layered' => isset($attributeLayered[$attribute->getId()]) ? 1 : 0,
        'is_active' => 1,
        'is_system' => 0,
        'is_sort' => 0,
        'filter_type' => isset($attributeLayered[$attribute->getId()]) ? EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_MULTIPLE : new Zend_Db_Expr('NULL'),
        'position' => isset($attributeLayered[$attribute->getId()]) ? $attributeLayered[$attribute->getId()] : 0,
        'is_custom_value_allowed' => 0
    );
}

if ($rows) {
    $this->getConnection()->insertOnDuplicate(
        $this->getTable('ecomdev_sphinx/attribute'),
        $rows
    );
}

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
        'is_seo' => 1,
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
