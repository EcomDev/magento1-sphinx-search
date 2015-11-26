<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Index_Field as RegularField;
use EcomDev_Sphinx_Model_Index_Field_Integer as IntegerField;
use EcomDev_Sphinx_Model_Index_Field_Product_Category as CategoryField;
use EcomDev_Sphinx_Model_Index_Field_Json as JsonField;


class EcomDev_Sphinx_Model_Index_Field_Provider_Product_Attribute_Virtual
    extends EcomDev_Sphinx_Model_Index_Field_AbstractProvider
{
    /**
     * Returns fields based on internal logic
     *
     * @return \EcomDev_Sphinx_Contract_FieldInterface[]
     */
    public function getFields()
    {
        $fields = [];

        $virtualFields = Mage::getSingleton('ecomdev_sphinx/config')->getVirtualFields();
        foreach ($virtualFields as $field) {
            $indexField = $field->getIndexField();
            if ($indexField !== false) {
                $fields[] = $indexField;
            }
        }

        return $fields;
    }

    /**
     * Returns attribute codes by type
     *
     * @return string[][]
     */
    public function getAttributeCodeByType()
    {
        return [];
    }
}
