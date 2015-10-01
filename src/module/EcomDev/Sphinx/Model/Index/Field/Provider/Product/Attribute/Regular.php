<?php

use EcomDev_Sphinx_Model_Config as SphinxConfig;
use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Model_Index_Field as RegularField;

/**
 * Regular attribute providers
 *
 */
class EcomDev_Sphinx_Model_Index_Field_Provider_Product_Attribute_Regular
    extends EcomDev_Sphinx_Model_Index_Field_AbstractProvider
{
    /**
     * @var SphinxConfig
     */
    private $sphinxConfig;

    /**
     * Sphinx attributes
     *
     * @var EcomDev_Sphinx_Model_Attribute[]
     */
    private $attributes;

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
     * Returns all sphinx attributes
     *
     * @return EcomDev_Sphinx_Model_Attribute[]
     */
    private function getAttributes()
    {
        if ($this->attributes === null) {
            $this->attributes = $this->sphinxConfig->getPlainAttributes();
        }

        return $this->attributes;
    }

    /**
     * Returns fields based on internal logic
     *
     * @return FieldInterface[]
     */
    public function getFields()
    {
        $fields = [];

        foreach ($this->getAttributes() as $code => $attribute) {
            $fields[] = new RegularField($attribute->getSphinxType(), $code);
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
        $codeByType = [];
        foreach ($this->getAttributes() as $code => $attribute) {
            $codeByType[$attribute->getBackendType()][] = $code;
        }

        return $codeByType;
    }
}
