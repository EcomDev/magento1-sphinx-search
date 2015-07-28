<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Multiple
    extends EcomDev_Sphinx_Model_Sphinx_Facet_Filter_AbstractCondition
{
    /**
     * List of selected values
     * 
     * @var int[]
     */
    protected $_values;

    /**
     * Constructs a multiple filter facet
     * 
     * @param EcomDev_Sphinx_Model_Sphinx_FacetInterface $facet
     * @param array $values
     */
    public function __construct(FacetInterface $facet, array $values)
    {
        parent::__construct($facet);
        $values = array_filter(array_map('intval', $values));
        if (empty($values)) {
            throw new RuntimeException(
                'Invalid values specified for multiple filter condition facet'
            );
        }
        $this->_values = $values;
    }

    /**
     * Applies multiple filter for facet
     * 
     * @param QueryBuilder $query
     * @return $this
     */
    public function apply(QueryBuilder $query)
    {
        $query->where($this->getFacet()->getColumnName(), 'IN', $this->_values);
        return $this;
    }
}
