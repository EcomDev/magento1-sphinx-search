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
        'is_layered' => 0,
        'is_active' => 1,
        'is_system' => 0,
        'is_sort' => 0,
        'filter_type' => null,
        'is_custom_value_allowed' => 0
    );
}

$this->getConnection()->insertOnDuplicate(
    $this->getTable('ecomdev_sphinx/attribute'),
    $rows
);
