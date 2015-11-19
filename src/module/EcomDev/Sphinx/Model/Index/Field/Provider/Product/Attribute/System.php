<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Index_Field as RegularField;
use EcomDev_Sphinx_Model_Index_Field_Integer as IntegerField;
use EcomDev_Sphinx_Model_Index_Field_Product_Category as CategoryField;
use EcomDev_Sphinx_Model_Index_Field_Json as JsonField;


class EcomDev_Sphinx_Model_Index_Field_Provider_Product_Attribute_System
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
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'attribute_set_id'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_STRING, 'type_id'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'name'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'sku'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'description'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'short_description'),
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'request_path'),
            new IntegerField('tax_class_id', 4),
            new IntegerField('visibility', 4),
            new IntegerField('status', 4),
            new IntegerField('stock_status', 4),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_BOOL, 'has_options'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_BOOL, 'required_options'),
            new CategoryField(FieldInterface::TYPE_ATTRIBUTE_MULTI, 'direct_category_ids', '_direct_category_ids'),
            new CategoryField(FieldInterface::TYPE_ATTRIBUTE_MULTI, 'anchor_category_ids', '_anchor_category_ids'),
            new CategoryField(FieldInterface::TYPE_FIELD, 's_direct_category_ids', '_direct_category_ids', true),
            new CategoryField(FieldInterface::TYPE_FIELD, 's_anchor_category_ids', '_anchor_category_ids', true),
            new CategoryField(FieldInterface::TYPE_FIELD, 's_direct_category_names', '_direct_category_names', false),
            new CategoryField(FieldInterface::TYPE_FIELD, 's_anchor_category_names', '_anchor_category_names', false),
            new CategoryField(FieldInterface::TYPE_ATTRIBUTE_INT, 'i_best_direct_position', '_best_direct_position', false),
            new CategoryField(FieldInterface::TYPE_ATTRIBUTE_INT, 'i_best_anchor_position', '_best_anchor_position', false),
            new JsonField('j_category_position', '_category_position'),
            new JsonField('j_category_url', '_category_url'),
        ];

        Mage::dispatchEvent('ecomdev_sphinx_provider_product_system_fields', ['container' => $container]);
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
                'entity_id', 'type_id', 'attribute_set_id',
                'sku', 'has_options', 'required_options'
            ],
            'varchar' => [
                'name',
            ],
            'text' => [
                'description', 'short_description'
            ]
        ];

        Mage::dispatchEvent(
            'ecomdev_sphinx_provider_product_system_attribute_codes',
            ['container' => $container]
        );

        return $container->attributeCodeByType;
    }
}
