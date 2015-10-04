<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Index_Field as RegularField;

class EcomDev_Sphinx_Model_Index_Field_Provider_Keyword
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
            new RegularField(FieldInterface::TYPE_FIELD_STRING, 'keyword'),
            new RegularField(FieldInterface::TYPE_FIELD, 'trigram_list'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'length'),
            new RegularField(FieldInterface::TYPE_ATTRIBUTE_INT, 'frequency')
        ];

        Mage::dispatchEvent('ecomdev_sphinx_provider_keyword_fields', ['container' => $container]);
        return $container->fields;
    }

    /**
     * Return list of attribute codes for categories that should be loaded
     *
     * @return string[][]
     */
    public function getAttributeCodeByType()
    {
        return [];
    }
}
