<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Stock index data retriever
 *
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Stock
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
{
    /**
     * Stock identifier
     *
     * @var int
     */
    private $stockId;

    /**
     * Based on entity type a table is found
     *
     * @param int $stockId
     */
    public function __construct($stockId)
    {
        parent::__construct();
        $this->stockId = $stockId;
    }

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

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                ['index' => $this->getTable('cataloginventory/stock_status')],
                ['product_id', 'stock_status']
            )
            ->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'entity_id.id = index.product_id',
                []
            )
            ->join(
                ['stock' => $this->getTable('cataloginventory/stock_item')],
                'stock.stock_id = index.stock_id and stock.product_id = index.product_id',
                ['qty']
            )
            ->join(
                ['store' => $this->getTable('core/store')],
                'store.website_id = index.website_id',
                []
            );

        $scope->getFilter('store_id')->render('store', $select);
        $select->where('index.stock_id = ?', $this->stockId);

        $data = [];
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $data[$row['product_id']]['stock_status'] = $row['stock_status'];
            $data[$row['product_id']]['stock_qty'] = $row['qty'];
        }

        return $data;
    }
}
