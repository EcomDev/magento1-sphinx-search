<?php

use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

/**
 * Filter condition that gets applied to SphinxQL query
 */
interface EcomDev_Sphinx_Model_Sphinx_Facet_Filter_ConditionInterface
{
    public function getFacet();
    
    public function apply(QueryBuilder $query);
}
