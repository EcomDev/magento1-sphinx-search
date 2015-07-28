<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use Foolz\SphinxQL\Expression;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Range
    extends EcomDev_Sphinx_Model_Sphinx_Facet_Filter_AbstractCondition
{
    /**
     * Selected ranges list
     * 
     * @var Expression
     */
    protected $_select;
    
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
        
        $collectedConditions = array();
        foreach ($selectedRanges as $rangeId) {
            if (!isset($availableRanges[$rangeId])) {
                continue;
            }
            
            if ($availableRanges[$rangeId] === 0) {
                continue;
            }
            
            $maxRange = $availableRanges[$rangeId];
            $minRange = 0;
            
            if (isset($availableRanges[$rangeId - 1])) {
                $minRange = $availableRanges[$rangeId - 1];
            }
            
            $collectedConditions[] = sprintf(
                '(%1$s >= %2$d and %1$s < %3$d)', 
                $this->getFacet()->getColumnName(),
                (float)$minRange,
                (float)$maxRange
            );
        }
        
        
        
        if (empty($collectedConditions)) {
            throw new RuntimeException('No valid ranges selected');
        }
        
        $this->_select = new Expression(
            sprintf(
                'IF(%s,1,0) as %s_range_match', 
                implode(' OR ', $collectedConditions), 
                $this->getFacet()->getColumnName()
            )
        );
    }

    /**
     * Applies a filter to a SphinxQL query
     * 
     * @param QueryBuilder $query
     * @return $this
     */
    public function apply(QueryBuilder $query)
    {
        $query->select($this->_select);
        $query->where(sprintf('%s_range_match', $this->getFacet()->getColumnName()), 1);
        return $this;
    }
}
