<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Index_Field as RegularField;

class EcomDev_Sphinx_Model_Index_Field_Provider_Category
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
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'category_id', null, 'entity_id'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'name'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'description'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_STRING, 'image'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_STRING, 'thumbnail'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'path'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_BOOL, 'is_active'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_BOOL, 'include_in_menu'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'position'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'level'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'sphinx_scope'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'request_path')
        ];

        Mage::dispatchEvent('ecomdev_sphinx_provider_category_fields', ['container' => $container]);
        return $container->fields;
    }

    /**
     * Return list of attribute codes for categories that should be loaded
     *
     * @return string[][]
     */
    public function getAttributeCodeByType()
    {
        $container = new stdClass();
        $container->attributeCodeByType = [
            'static' => [
                'entity_id', 'path', 'position', 'level'
            ],
            'varchar' => [
                'name', 'image', 'thumbnail'
            ],
            'int' => [
                'is_active', 'include_in_menu', 'sphinx_scope'
            ],
            'text' => [
                'description'
            ]
        ];

        Mage::dispatchEvent(
            'ecomdev_sphinx_provider_category_attribute_codes',
            ['container' => $container]
        );

        return $container->attributeCodeByType;
    }
}
