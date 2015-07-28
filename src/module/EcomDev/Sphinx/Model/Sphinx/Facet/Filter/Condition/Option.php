<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Option
    extends EcomDev_Sphinx_Model_Sphinx_Facet_Filter_AbstractCondition
{
    /**
     * Selected value
     * 
     * @var int
     */
    protected $_value;

    /**
     * Constructs a filter facet
     * 
     * @param EcomDev_Sphinx_Model_Sphinx_FacetInterface $facet
     * @param int $value
     */
    public function __construct(FacetInterface $facet, $value)
    {
        parent::__construct($facet);
        $this->_value = (int)$value;
    }

    /**
     * Applies multiple filter for facet
     * 
     * @param QueryBuilder $query
     * @return $this
     */
    public function apply(QueryBuilder $query)
    {
        $query->where($this->getFacet()->getColumnName(), $this->_value);
        return $this;
    }
}
