<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Limit
    extends EcomDev_Sphinx_Model_Sphinx_Facet_Filter_AbstractCondition
{
    const TYPE_FLOAT = 'decimal';
    const TYPE_INT = 'int';

    /**
     * Minimal filter value
     *
     * @var float|int|null
     */
    protected $_minValue;
    
    /**
     * Selected ranges list
     *
     * @var float|int|null
     */
    protected $_maxValue;

    /**
     * Constructs a ranged condition
     *
     * @param FacetInterface $facet
     * @param string $minValue
     * @param string $maxValue
     * @param string $type
     */
    public function __construct(FacetInterface $facet,
                                $minValue,
                                $maxValue,
                                $type = self::TYPE_FLOAT
    )
    {
        parent::__construct($facet);

        if ($minValue !== '') {
            $this->_minValue = $type === self::TYPE_FLOAT ? (float)$minValue : (int)$minValue;
        }

        if ($maxValue !== '') {
            $this->_maxValue = $type === self::TYPE_FLOAT ? (float)$maxValue : (int)$maxValue;
        }
        
        if ($this->_minValue === null && $this->_maxValue === null) {
            throw new RuntimeException('At least one limit should be specified');
        }
    }

    /**
     * Applies a filter to a SphinxQL query
     *
     * @param QueryBuilder $query
     * @return $this
     */
    public function apply(QueryBuilder $query)
    {
        if ($this->_minValue !== null) {
            $query->where($this->getFacet()->getColumnName(), '>=', $this->_minValue);
        }
        
        if ($this->_maxValue !== null) {
            $query->where($this->getFacet()->getColumnName(), '<=', $this->_maxValue);
        }
        return $this;
    }
}
