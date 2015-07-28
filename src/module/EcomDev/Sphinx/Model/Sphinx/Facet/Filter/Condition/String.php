<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

/**
 * Returns facet filter for a string
 *
 */
class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_String
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
     * @param string $value
     */
    public function __construct(FacetInterface $facet, $value)
    {
        parent::__construct($facet);
        $this->_value = (string)$value;
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
