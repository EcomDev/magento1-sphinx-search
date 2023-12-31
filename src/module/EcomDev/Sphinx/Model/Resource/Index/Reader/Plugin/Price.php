<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Price index data retriever
 *
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Price
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
{
    /**
     * Returns array of data per entity identifier
     *
     * @param int[] $identifiers
     * @param ScopeInterface $scope
     * @return array[]
     */
    public function read(array $identifiers, ScopeInterface $scope)
    {
        if (!$scope->hasFilter('store_id') || !$identifiers) {
            return [];
        }

        if (!$this->entityMemoryTable) {
            $this->fillMemoryTable('entity_id', $identifiers);
        }

        $least       = $this->_getReadAdapter()->getLeastSql(array('index.min_price', 'index.tier_price'));
        $minimalExpr = $this->_getReadAdapter()->getCheckSql('index.tier_price IS NOT NULL',
            $least, 'index.min_price');

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                ['index' => $this->getTable('catalog/product_index_price')],
                ['entity_id', 'customer_group_id', 'tax_class_id',
                 'price', 'final_price', 'min_price',
                 'max_price', 'tier_price', 'group_price', 'minimal_price' => $minimalExpr])
            ->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'entity_id.id = index.entity_id',
                []
            )
            ->join(
                ['store' => $this->getTable('core/store')],
                'store.website_id = index.website_id',
                []
            );

        $scope->getFilter('store_id')->render('store', $select);

        $data = [];
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $data[$row['entity_id']]['tax_class_id'] = $row['tax_class_id'];
            $data[$row['entity_id']]['price_index'][$row['customer_group_id']] = $row;
        }

        return $data;
    }
}
