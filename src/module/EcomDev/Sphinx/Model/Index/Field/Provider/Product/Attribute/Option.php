<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Config as SphinxConfig;
use EcomDev_Sphinx_Model_Index_Field_Option as FieldOption;

class EcomDev_Sphinx_Model_Index_Field_Provider_Product_Attribute_Option
    extends EcomDev_Sphinx_Model_Index_Field_AbstractProvider
{
    /**
     * @var SphinxConfig
     */
    private $sphinxConfig;

    /**
     * Receives sphinx config as argument
     *
     * @param SphinxConfig $sphinxConfig
     */
    public function __construct(SphinxConfig $sphinxConfig)
    {
        $this->sphinxConfig = $sphinxConfig;
    }

    /**
     * Returns fields based on internal logic
     *
     * @return FieldInterface[]
     */
    public function getFields()
    {
        $attributes = $this->sphinxConfig->getAttributesByType('option');
        $attributeIds = [];
        $options = [];

        foreach ($attributes as $attribute) {
            $attributeIds[] = $attribute->getId();
        }

        if ($attributeIds) {
            $options = $this->sphinxConfig->getAttributeOptions(
                $attributeIds, Mage_Catalog_Model_Product::ENTITY
            );
        }

        $fields = [];
        foreach ($attributes as $code => $attribute) {
            $fields[] = new FieldOption(
                FieldInterface::TYPE_ATTRIBUTE_MULTI,
                $code,
                isset($options[$attribute->getId()]) ? $options[$attribute->getId()] : []
            );

            if ($attribute->getIsFulltext()) {
                $fields[] = new FieldOption(
                    FieldInterface::TYPE_FIELD,
                    sprintf('s_%s_label', $code),
                    isset($options[$attribute->getId()]) ? $options[$attribute->getId()] : [],
                    $code
                );
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
        $options = [];
        foreach ($this->sphinxConfig->getAttributesByType('option') as $code => $attribute) {
            $options[$attribute->getBackendType()][] = $code;
        }

        return $options;
    }

}
