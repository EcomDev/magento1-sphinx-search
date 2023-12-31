<?php

use EcomDev_Sphinx_Model_Attribute as Attribute;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

abstract class EcomDev_Sphinx_Model_Sphinx_Facet_AbstractAttribute
    extends EcomDev_Sphinx_Model_Sphinx_AbstractFacet
{
    /**
     * An instance of attribute
     * 
     * @var Attribute
     */
    protected $_attribute;

    /**
     * Configuration for an attribute
     * 
     * @param EcomDev_Sphinx_Model_Attribute $attribute
     * @param null|string $filterName
     * @param null|string $columnName
     * @param null|string $label
     */
    public function __construct(Attribute $attribute, $filterName = null, $columnName = null, $label = null)
    {
        if ($filterName === null) {
            $filterName = $attribute->getAttributeCode();
        }
        
        $this->_attribute = $attribute;
        
        parent::__construct(
            $columnName ?: $attribute->getAttributeCode(),
            $filterName,
            $label ?: $attribute->getAttribute()->getStoreLabel()
        );
    }
}

