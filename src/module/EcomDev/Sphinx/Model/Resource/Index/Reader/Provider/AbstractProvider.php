<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use EcomDev_Sphinx_Model_Resource_Index_Reader_Filter_Date as FilterDate;

abstract class EcomDev_Sphinx_Model_Resource_Index_Reader_Provider_AbstractProvider
    extends Mage_Core_Model_Resource_Db_Abstract
    implements EcomDev_Sphinx_Contract_Reader_ProviderInterface
{
    /**
     * Date time filter
     *
     * @var DateTime
     */
    protected $startedAt;

    /**
     * Returns type of the record
     *
     * @return string
     */
    abstract protected function _getType();

    /**
     * Returns type of meta record
     *
     * @param ScopeInterface $scope
     * @return string
     */
    protected function _getMetaType(ScopeInterface $scope)
    {
        return $this->_getType();
    }

    /**
     * Returns meta type
     *
     * @param ScopeInterface $scope
     * @return string
     */
    public function getMetaType(ScopeInterface $scope)
    {
        return $this->_getMetaType($scope);
    }

    /**
     * Zend db adapter validation, in order to get rid of possible server gone away
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return bool
     */
    protected function _validateConnection(Zend_Db_Adapter_Abstract $adapter)
    {
        try {
            // Execute simple non heavy query
            return $adapter->fetchOne('SELECT 1') === '1';
        } catch (Zend_Db_Statement_Exception $e) {
            $adapter->closeConnection();
        }

        return $adapter->getConnection() !== null;
    }

    /**
     * Starts process of the indexer
     *
     * @param ScopeInterface $scope
     * @return $this
     */
    protected function startProcess(ScopeInterface $scope)
    {
        $startedAt = new DateTime('now', new DateTimeZone('UTC'));

        if ($scope->hasFilter('updated_at') && !$scope->getFilter('updated_at') instanceof FilterDate) {
            $updatedAt = $scope->getFilter('updated_at')->getValue();
            if (!$updatedAt instanceof DateTime) {
                $updatedAt = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $updatedAt,
                    new DateTimeZone('UTC')
                );
            }

            $scope->replaceFilter(new FilterDate('updated_at', [$updatedAt, $startedAt]));
        }

        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * Returns limit of rows for current scope
     *
     * @param ScopeInterface $scope
     * @return int[]
     */
    public function getLimit(ScopeInterface $scope)
    {
        $this->startProcess($scope);
        $select = $this->_getReadAdapter()->select();
        $entityIdField = $this->getIdFieldName();
        $select->from(
            ['index' => $this->getMainTable()],
            [
                'min' => 'MIN(' . $entityIdField . ')',
                'max' => 'MAX(' . $entityIdField . ')'
            ]
        );

        foreach ($scope->getFilters() as $filter) {
            $filter->render('index', $select);
        }

        $result = $this->_getReadAdapter()->fetchRow($select);
        if ($result) {
            return [$result['min'], $result['max']];
        }

        return [null, null];
    }

    /**
     * Ends the process of indexation
     *
     * @return $this
     */
    protected function endProcess(ScopeInterface $scope)
    {
        if ($this->startedAt
            && $scope->hasFilter('store_id')
            && $this->_validateConnection($this->_getWriteAdapter())) {
            $this->_getWriteAdapter()->insertOnDuplicate(
                $this->getTable('ecomdev_sphinx/index_metadata'),
                array(
                    'code' => $this->getMetaType($scope),
                    'store_id' => $scope->getFilter('store_id')->getValue(),
                    'current_reindex_at' => $this->startedAt->format('Y-m-d H:i:s')
                ),
                array(
                    'previous_reindex_at' => new Zend_Db_Expr('current_reindex_at'),
                    'current_reindex_at' => 'current_reindex_at'
                )
            );
        }
        return $this;
    }

    /**
     * Returns deleted and updated records
     *
     * @param ScopeInterface $scope
     * @return int[]
     */
    public function getKillRecords(ScopeInterface $scope)
    {
        if (!$scope->hasFilter('updated_at') || !$scope->hasFilter('store_id')) {
            return [];
        }

        $updatedAtFilter = $scope->getFilter('updated_at');

        $selects = [];

        $select = $this->_getReadAdapter()->select()
            ->from(['delete_index' => $this->getTable('ecomdev_sphinx/index_deleted')], 'entity_id')
            ->where('type = ?', $this->_getType());

        $updatedAtFilter->render('delete_index.deleted_at', $select);

        $selects[] = $select;

        $select = $this->_getReadAdapter()->select()
            ->from(['index' => $this->getMainTable()], $this->getIdFieldName());

        foreach ($scope->getFilters() as $filter) {
            $filter->render('index', $select);
        }

        $selects[] = $select;

        $union = $this->_getReadAdapter()->select()
            ->union($selects, Zend_Db_Select::SQL_UNION_ALL);

        $statement = $this->_getReadAdapter()->query($union);

        if ($statement->rowCount() > 1000) {
            return $statement;
        }

        return $statement->fetchColumn();
    }
}
