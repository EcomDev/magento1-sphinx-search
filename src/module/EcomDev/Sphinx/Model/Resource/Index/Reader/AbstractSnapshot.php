<?php

abstract class EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractSnapshot
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractResource
    implements EcomDev_Sphinx_Contract_Reader_SnapshotInterface
{
    /**
     * Registry of created attribute type tables
     *
     * @var Varien_Db_Ddl_Table[]
     */
    private $attributeTypeTables = [];

    /**
     * Current attribute base snapshot
     *
     * @var Varien_Db_Ddl_Table
     */
    private $attributeBaseSnapshot;

    /**
     * Entity table name
     *
     * @var string|null
     */
    private $entityMemoryTable;

    /**
     * Describes table structure
     *
     * @param string $attributeType
     *
     * @return string[][]
     */
    private function describeTable($attributeType)
    {
        $originalColumns = $this->_getReadAdapter()->describeTable(
            $this->getAttributeTypeTable($attributeType)
        );

        $originalColumns['entity_id']['PRIMARY'] = true;
        $originalColumns['attribute_id']['PRIMARY'] = true;

        unset($originalColumns['value_id']);
        unset($originalColumns['store_id']);
        unset($originalColumns['entity_type_id']);

        return [
            'entity_id' => $originalColumns['entity_id'],
            'attribute_id' => $originalColumns['attribute_id']
        ] + $originalColumns;
    }

    /**
     * @param string $attributeType
     *
     * @return string
     */
    private function getAttributeTypeTable($attributeType)
    {
        return $this->getTable([$this->_mainTable, $attributeType]);
    }

    /**
     * Generates attribute snapshot data definition layer
     *
     * @param string $attributeType
     *
     * @return Varien_Db_Ddl_Table
     * @throws Zend_Db_Exception
     */
    protected function generateAttributeSnapshotDdl($attributeType)
    {
        $describe = $this->describeTable($attributeType);

        $table = $this->_getReadAdapter()->newTable(uniqid('tmp_eav_data_' . $attributeType));

        foreach ($describe as $columnData) {
            $this->addColumnByDdl($table, $columnData);
        }

        $this->attributeTypeTables[$attributeType] = $table;
        return $table;
    }

    /**
     * @param Varien_Db_Ddl_Table $table
     * @param $columnData
     *
     * @return $this
     * @throws Zend_Db_Exception
     */
    private function addColumnByDdl(Varien_Db_Ddl_Table $table, $columnData)
    {
        $columnInfo = $this->_getReadAdapter()->getColumnCreateByDescribe($columnData);

        $table->addColumn(
            $columnInfo['name'],
            $columnInfo['type'],
            $columnInfo['length'],
            $columnInfo['options'],
            $columnInfo['comment']
        );

        return $this;
    }

    protected function createAttributeBaseSnapshot($entityIdFrom, $entityIdTo)
    {
        $select = $this->_getReadAdapter()->select()
            ->from(['main_table' => $this->getMainTable()], [])
            ->joinCross(['attribute_id' => $this->getMemoryTableName('attribute_id')], [])
            ->columns([
                'entity_id',
                'attribute_id.id'
            ])
        ;

        $this->modifySelectFilters($select);

        $this->_getReadAdapter()->query(
            $this->_getReadAdapter()->insertFromSelect(
                $select,
                $this->attributeBaseSnapshot->getName(),
                ['entity_id', 'attribute_id'],
                Varien_Db_Adapter_Interface::INSERT_IGNORE
            ),
            ['start' => $entityIdFrom, 'end' => $entityIdTo]
        );


        return $this;
    }
    
    /**
     * Create data snapshot
     *
     * @param EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
     *
     * @return $this
     */
    public function createSnapshot(
        EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
    ) {
        if (!$scope->hasFilter('store_id')) {
            return $this;
        }

        foreach ($scope->getConfiguration()
                     ->getAttributeIdsGroupedByType() as $attributeType => $attributeIds) {
            if ($attributeType === 'static') {
                continue;
            }

            $table = $this->generateAttributeSnapshotDdl($attributeType);
            $this->createSnapshotTable($table);
            
            $this->createSnapshotForAttributeType(
                $attributeType,
                $scope->getFilter('store_id')->getValue(),
                $attributeIds
            );
        }

        return $this;
    }


    /**
     * Create snapshot for particular attribute type
     *
     * @param string $attributeType
     * @param string $storeId
     * @param int[] $attributeIds
     * @param int $entityIdFrom
     * @param int $entityIdTo
     *
     * @return $this
     * @throws InvalidArgumentException
     * @throws Zend_Db_Exception
     */
    protected function createSnapshotForAttributeType(
        $attributeType,
        $storeId,
        $attributeIds
    ) {
        $this->enableIndexSwitch();

        $table = $this->getSnapshotTable($attributeType);
        $columns = $this->describeTable($attributeType);
        $indexHint = $this->findIndexHint(
            $this->getAttributeTypeTable($attributeType),
            ['entity_id', 'attribute_id', 'store_id']
        );

        $selectColumns = [];
        foreach ($columns as $column) {
            $selectColumns[$column['COLUMN_NAME']] = sprintf('main_table.%s', $column['COLUMN_NAME']);
        }

        $select = new EcomDev_Sphinx_Model_Resource_Index_Reader_Select($this->_getReadAdapter());
        $select
            ->from(['main_table' => $this->getAttributeTypeTable($attributeType)], [])
            ->indexHint('main_table', $indexHint)
        ;

        $this->modifySelectFilters($select);

        $select->where('main_table.attribute_id IN(?)', array_map('intval', $attributeIds));
        $select->where('main_table.store_id = :store_id');

        $selectColumns = $this->modifySelectColumns($select, $selectColumns);
        
        $select->columns($selectColumns);

        
        foreach ([$storeId, 0] as $filterStoreId) {
            $this->_getReadAdapter()->query(
                $this->_getReadAdapter()->insertFromSelect(
                    $select,
                    $table,
                    array_keys($selectColumns),
                    Varien_Db_Adapter_Interface::INSERT_IGNORE
                ),
                [
                    'store_id' => (int)$filterStoreId
                ]
            );
        }

        $this->disableIndexSwitch();
        return $this;
    }

    /**
     * Modifies select snapshot
     *
     * @param Varien_Db_Select $select
     *
     * @return $this
     */
    abstract protected function modifySelectFilters($select);

    /**
     * Modify select columns list
     *
     * @param $select
     * @param string[] $columns
     *
     * @return string[]
     */
    protected function modifySelectColumns($select, $columns)
    {
        return $columns;
    }

    /**
     * Fills snapshot data from select
     *
     * @param string $attributeType
     *
     * @return Varien_Db_Select
     * @throws Zend_Db_Exception
     */
    protected function getSnapshotTableSelect($attributeType)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from(['filter_data' => $this->currentFilterTable->getName()], [])
            ->join(
                ['main_table' => $this->getAttributeTypeTable($attributeType)],
                'main_table.value_id = filter_data.value_id',
                []
            );

        return $select;
    }


    protected function createSnapshotTable(Varien_Db_Ddl_Table $table)
    {
        $this->_getReadAdapter()->createTemporaryTable($table);
        return $this;
    }

    public function getSnapshotTable($attributeType)
    {
        if (!isset($this->attributeTypeTables[$attributeType])) {
            throw new InvalidArgumentException('Unknown attribute type');
        }

        return $this->attributeTypeTables[$attributeType]->getName();
    }

    public function destroySnapshot()
    {
        foreach ($this->attributeTypeTables as $table) {
            $this->_getReadAdapter()->dropTemporaryTable($table->getName());
        }

        $this->attributeTypeTables = [];
        return $this;
    }

    /**
     * Sets entity table by filter
     *
     * @param null|string $name
     * @return $this
     */
    public function setEntityTableName($name)
    {
        $this->entityMemoryTable = $name;
        return $this;
    }

    /**
     * Returns entity table name
     *
     * @return null|string
     */
    public function getEntityTableName()
    {
        return $this->entityMemoryTable;
    }
}
