<?php

class EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index
    extends Mage_Core_Model_Resource_Db_Abstract
{
    const INDEX_PRODUCT = EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT;
    const INDEX_CATEGORY = EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY;



    /**
     * List of available tables
     * 
     * @var string[]
     */
    protected $_indexTables = [];

    /**
     * Index field identifiers
     *
     * @var string[]
     */
    protected $_indexIdField = [];
   
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_metadata', 'document_id');
        $this->_indexTables[self::INDEX_PRODUCT] = $this->getTable('ecomdev_sphinx/index_product');
        $this->_indexTables[self::INDEX_CATEGORY] = $this->getTable('ecomdev_sphinx/index_category');

        $this->_indexIdField[self::INDEX_PRODUCT] = 'product_id';
        $this->_indexIdField[self::INDEX_CATEGORY] = 'category_id';
    }

    /**
     * Returns information if index is ready
     *
     * @return bool
     */
    public function isIndexReady()
    {
        return true;
    }

    /**
     * Base select object for index
     *
     * @param string $timestampStart
     * @param string $timestampEnd
     * @return Varien_Db_Select
     */
    protected function _getIndexBaseSelect($type, $timestampStart, $timestampEnd)
    {
        if (strpos($type, self::INDEX_PRODUCT)) {

        }

        if (isset($this->_indexTables[$type])) {
            $mainTable = $this->_indexTables[$type];
        } else {
            throw new RuntimeException('Unknown index type specified');
        }

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('main' => $mainTable),
                array()
            );

        if ($timestampStart && $timestampEnd) {
            $select
                ->where('main.updated_at >= ' . $timestampStart)
                ->where('main.updated_at <= ' . $timestampEnd);
        }

        return $select;
    }

    /**
     * Returns type and additional conditions
     *
     * @param $index
     * @return array
     */
    private function getTypeAndCondition($index)
    {
        if (strpos($index, self::INDEX_PRODUCT) === 0) {
            $conditions = [];
            if (strpos($index, 'search')) {
                $conditions['main.visibility IN(?)'] = [
                    Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                    Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
                ];
            } else {
                $conditions['main.visibility IN(?)'] = [
                    Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                    Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
                ];
            }

            return [self::INDEX_PRODUCT,  $conditions];
        } elseif (strpos($index, self::INDEX_CATEGORY) === 0) {
            return [self::INDEX_CATEGORY, []];
        }


        return [$index, []];
    }

    /**
     * Returns amount of pending rows for index
     * 
     * @param $index
     * @param $storeId
     * @return mixed
     */
    public function getPendingRowCount($index, $storeId)
    {
        list($type, $conditions) = $this->getTypeAndCondition($index);

        $metaDataSelect = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'current_reindex_at')
            ->where('code = ?', $index)
            ->where('store_id = ?', $storeId);

        $select = $this->_getIndexBaseSelect($type, false, false)
            ->columns(sprintf('COUNT(main.%s)', $this->_indexIdField[$type]))
            ->where('main.updated_at > ?', $metaDataSelect)
            ->where('main.store_id = ?', $storeId);
        ;

        foreach ($conditions as $key => $value) {
            $select->where($key, $value);
        }
        
        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Returns amount of indexed rows for index
     *
     * @param $index
     * @param $storeId
     * @return mixed
     */
    public function getIndexedRowCount($index, $storeId)
    {
        list($type, $conditions) = $this->getTypeAndCondition($index);

        $metaDataSelect = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'current_reindex_at')
            ->where('code = ?', $index)
            ->where('store_id = ?', $storeId);

        $select = $this->_getIndexBaseSelect($type, false, false)
            ->columns(sprintf('COUNT(main.%s)', $this->_indexIdField[$type]))
            ->where('main.updated_at <= ?', $metaDataSelect)
            ->where('main.store_id = ?', $storeId);
        ;

        foreach ($conditions as $key => $value) {
            $select->where($key, $value);
        }

        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Current store id select
     *
     * @param string $type
     * @return Varien_Db_Select
     */
    protected function getCurrentStoreId($type)
    {
        return $this->_getReadAdapter()->select()
            ->from($this->getTable('ecomdev_sphinx/index_store'), 'store_id')
            ->where('type = ?', $type);
    }
}
