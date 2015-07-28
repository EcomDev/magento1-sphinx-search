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
     * @param null $filterName
     */
    public function __construct(Attribute $attribute, $filterName = null)
    {
        if ($filterName === null) {
            $filterName = $attribute->getAttributeCode();
        }
        
        $this->_attribute = $attribute;
        
        parent::__construct(
            $attribute->getAttributeCode(),
            $filterName,
            $attribute->getAttribute()->getFrontendLabel() 
        );
    }

    /**
     * Facet SphinxQL for retrieval of data
     *
     * @return QueryBuilder
     */
    public function getFacetSphinxQL(QueryBuilder $baseQuery)
    {
        $query = clone $baseQuery;
        $query->select(
            $query->exprFormat('GROUPBY() as %s', $query->quoteIdentifier('value')),
            $query->exprFormat('COUNT(*) as %s', $query->quoteIdentifier('count'))
        );
        
        $query->from($this->_getIndexNames())
            ->groupBy($this->getColumnName())
            ->orderBy('count', 'desc')
            ->limit(200);
        
        return $query;
    }
}

