<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Index_Field as RegularField;
use EcomDev_Sphinx_Model_Index_Field_Product_Category as CategoryField;
use EcomDev_Sphinx_Model_Index_Field_Option as FieldOption;



class EcomDev_Sphinx_Model_Index_Field_Provider_Product_Keyword_System
    extends EcomDev_Sphinx_Model_Index_Field_AbstractProvider
{
    /**
     * Returns fields based on internal logic
     *
     * @return \EcomDev_Sphinx_Contract_FieldInterface[]
     */
    public function getFields()
    {
        $container = new stdClass();
        $container->fields = [
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'product_id', null, 'entity_id'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'name'),
            new FieldOption(FieldInterface::TYPE_FIELD, 'manufacturer'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_STRING, 'sku'),
            new CategoryField(FieldInterface::TYPE_FIELD, 'category_names', '_direct_category_names', false)
        ];

        Mage::dispatchEvent('ecomdev_sphinx_provider_product_keyword_system_fields', ['container' => $container]);
        return $container->fields;
    }

    /**
     * Returns attribute codes by type
     *
     * @return string[][]
     */
    public function getAttributeCodeByType()
    {
        $container = new stdClass();
        $container->attributeCodeByType = [
            'static' => [
                'entity_id', 'sku', 'has_options', 'required_options',
                'created_at', 'updated_at'
            ],
            'varchar' => [
                'name'
            ],
            'int' => [
                'manufacturer'
            ]
        ];

        Mage::dispatchEvent(
            'ecomdev_sphinx_provider_product_keyword_system_attribute_codes',
            ['container' => $container]
        );

        return $container->attributeCodeByType;
    }
}
