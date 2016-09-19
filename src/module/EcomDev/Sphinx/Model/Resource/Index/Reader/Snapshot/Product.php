<?php

use Varien_Db_Ddl_Table as Table;

/**
 * Product snapshot model
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Snapshot_Product
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractSnapshot
{
    /**
     * Is child data retrieval
     *
     * @var \Closure
     */
    private $selectCallback;

    /**
     * Stock table
     *
     * @var string
     */
    private $stockTable;

    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }

    protected function generateAttributeSnapshotDdl($attributeType)
    {
        return parent::generateAttributeSnapshotDdl($attributeType)
            ->addColumn('is_child_data', Table::TYPE_INTEGER, 1, [
                'unsigned' => true,
                'nullable' => false,
                'default' => '0'
            ])
            ->addColumn('is_child_data_stock', Table::TYPE_INTEGER, 1, [
                'unsigned' => true,
                'nullable' => false,
                'default' => '0'
            ])
            ->addIndex('IDX_DATA_FILTER', ['is_child_data', 'is_child_data_stock']);
    }

    public function createSnapshot(
        EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
    ) {
        $this->createStockSnapshot($scope);

        parent::createSnapshot($scope);

        $this->_getReadAdapter()->dropTemporaryTable($this->stockTable);

        return $this;
    }

    private function createStockSnapshot($scope)
    {
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(['relation' => $this->getTable('catalog/product_relation')], [])
            ->join(['entity_id' => $this->getEntityTableName()], 'entity_id.id = relation.parent_id', [])
            ->joinLeft(
                ['stock_status' => $this->getTable('cataloginventory/stock_status')],
                $this->_getReadAdapter()->quoteInto(
                    'stock_status.product_id = relation.child_id and stock_status.website_id = ?',
                    Mage::app()->getStore($scope->getFilter('store_id')->getValue())->getWebsiteId()
                ),
                []
            )
            ->columns([
                'entity_id' => 'relation.child_id',
                'stock_status' => 'IFNULL(MAX(stock_status.stock_status), 1)'
            ])
            ->group('relation.child_id')
        ;

        $this->stockTable = $this->createTemporaryTableFromSelect(
            $select,
            ['PRIMARY' => ['entity_id'], 'status' => ['stock_status']]
        );
        
        return $this;
    }


    /**
     * Creates snapshot for child and parent product, in case if child product data is required
     *
     * @param string $attributeType
     * @param string $storeId
     * @param int[] $attributeIds
     * @param int $entityIdFrom
     * @param int $entityIdTo
     *
     * @return $this
     */
    protected function createSnapshotForAttributeType(
        $attributeType,
        $storeId,
        $attributeIds
    ) {
        $callbacks = [
            [$this->selectDirectFilterClosure()]
        ];

        if (in_array($attributeType, ['int', 'varchar'])) {
            $callbacks[] = [$this->selectChildFilterClosure(), $this->selectChildColumnsClosure()];
        }

        foreach ($callbacks as $callback) {
            $this->selectCallback = $callback;
            parent::createSnapshotForAttributeType(
                $attributeType,
                $storeId,
                $attributeIds
            );
        }

        return $this;
    }

    /**
     * Child filter closure
     */
    private function selectChildFilterClosure()
    {
        return function ($select) {
            $select
                ->join(
                    ['relation' => $this->getTable('catalog/product_relation')],
                    'relation.child_id = main_table.entity_id',
                    []
                )
                ->join(
                    ['entity_id' => $this->getEntityTableName()],
                    'entity_id.id = relation.parent_id',
                    []
                )
            ;
        };
    }

    /**
     * Modifies select snapshot
     *
     * @param Varien_Db_Select $select
     *
     * @return $this
     */
    protected function modifySelectFilters($select)
    {
        $callback = $this->selectCallback[0];
        return $callback($select);
    }

    /**
     * @param $select
     * @param $columns
     *
     * @return mixed
     */
    protected function modifySelectColumns($select, $columns)
    {
        if (isset($this->selectCallback[1])) {
            $callback = $this->selectCallback[1];
            return $callback($select, $columns);
        }

        return $columns;
    }

    private function selectChildColumnsClosure()
    {
        return function ($select, $columns) {
            $select
                ->join(
                    ['stock' => $this->stockTable],
                    'stock.entity_id = main_table.entity_id',
                    []
                )
                ->join(
                    ['attribute' => $this->getTable('ecomdev_sphinx/attribute')],
                    'attribute.attribute_id = main_table.attribute_id',
                    []
                )
                ->where('attribute.is_child_data = ?', 1)
            ;

            $columns['is_child_data'] = 'attribute.is_child_data';
            $columns['is_child_data_stock'] = 'IF(attribute.is_child_data_stock, stock.stock_status, 1)';
            return $columns;
        };
    }

    private function selectDirectFilterClosure()
    {
        return function ($select) {
            $select->join(
                ['entity_id' => $this->getEntityTableName()],
                'entity_id.id = main_table.entity_id',
                []
            );
        };
    }
}
