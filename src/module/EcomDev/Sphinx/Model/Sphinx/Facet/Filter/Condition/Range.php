<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use Foolz\SphinxQL\Expression;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Range
    extends EcomDev_Sphinx_Model_Sphinx_Facet_Filter_AbstractCondition
{
    /**
     * Available ranges
     *
     * @var float[]
     */
    protected $_availableRanges;

    /**
     * Available ranges
     *
     * @var float[]
     */
    protected $_selectedRanges;

    /**
     * Constructs a ranged condition
     * 
     * @param FacetInterface $facet
     * @param int[] $selectedRanges
     * @param array $availableRanges
     */
    public function __construct(FacetInterface $facet, 
                                array $selectedRanges, 
                                array $availableRanges)
    {
        parent::__construct($facet);

        $this->_selectedRanges = $selectedRanges;
        $this->_availableRanges = $availableRanges;
    }

    /**
     * Applies a filter to a SphinxQL query
     * 
     * @param QueryBuilder $query
     * @return $this
     */
    public function apply(QueryBuilder $query)
    {
        $column = $this->getFacet()->getColumnName();
        $collectedConditions = array();
        foreach ($this->_selectedRanges as $rangeId) {
            if (!isset($this->_availableRanges[$rangeId]) && isset($this->_availableRanges[$rangeId - 1])) {
                $collectedConditions[] = sprintf('%1$s >= %2$d', $column, (float)$this->_availableRanges[$rangeId - 1]);
                continue;
            } elseif (!isset($this->_availableRanges[$rangeId])) {
                continue;
            }

            if ($this->_availableRanges[$rangeId] === 0) {
                continue;
            }

            $maxRange = $this->_availableRanges[$rangeId];
            $minRange = 0;

            if (isset($this->_availableRanges[$rangeId - 1])) {
                $minRange = $this->_availableRanges[$rangeId - 1];
            }

            $collectedConditions[] = sprintf('(%1$s >= %2$d AND %1$s <= %3$d)', $column, (int)$minRange, (int)$maxRange);
        }

        if ($collectedConditions) {
           $query->select(new Expression(sprintf(
                'IF(%s,1,0) as %s_range_match',
                implode(' OR ', $collectedConditions),
                $this->getFacet()->getColumnName()
            )));

            $query->where(
                sprintf('%s_range_match', $this->getFacet()->getColumnName()),
                '=',
                1
            );
        }


        return $this;
    }
}
