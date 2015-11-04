<?php

class EcomDev_Sphinx_Model_Resource_Index_Keyword
    extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_keyword', 'keyword_id');
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
     * Validate connection
     *
     * @return bool
     */
    public function validateConnection()
    {
        $result = $this->_validateConnection($this->_getWriteAdapter());
        if ($this->_getWriteAdapter() !== $this->_getReadAdapter()) {
           $result = $result && $this->_validateConnection($this->_getReadAdapter());
        }

        return $result;
    }

    /**
     * Remove keywords in a store
     *
     * @param int $storeId
     * @return $this
     */
    public function startImport($storeId)
    {
        $this->validateConnection();
        $this->_getWriteAdapter()->beginTransaction();
        $this->_getWriteAdapter()->delete($this->getMainTable(), array('store_id = ?' => $storeId));
        return $this;
    }

    /**
     * Remove keywords in a store
     *
     * @param int $storeId
     * @return $this
     */
    public function finishImport($storeId)
    {
        $this->_getWriteAdapter()->commit();
        return $this;
    }

    /**
     * Inserts records
     *
     * @param string[][] $records
     * @return $this
     */
    public function insertRecords($records)
    {
        $this->_getWriteAdapter()->insertOnDuplicate(
            $this->getMainTable(),
            $records,
            ['trigram_list', 'frequency']
        );
        return $this;
    }
}
