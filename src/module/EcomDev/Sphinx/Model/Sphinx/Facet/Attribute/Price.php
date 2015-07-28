<?php

use EcomDev_Sphinx_Model_Attribute as Attribute;

class EcomDev_Sphinx_Model_Sphinx_Facet_Attribute_Price
    extends EcomDev_Sphinx_Model_Sphinx_Facet_AbstractRangedAttribute
{
    /**
     * Customer group id 
     * 
     * @var int
     */
    protected $_customerGroupId;
    
    public function __construct(Attribute $attribute, $rangeStep, $rangeCount, $customerGroupId = 0)
    {
        parent::__construct($attribute, $rangeStep, $rangeCount);
        $this->_customerGroupId = $customerGroupId;
        $this->_columnName = sprintf('price_index_min_price_%d', $this->_customerGroupId);
    }
}
