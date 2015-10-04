<?php

/**
 * @method EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index getResource()
 */
class EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @var string[]
     */
    private $supportRowCount;

    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/sphinx_config_index');
        $this->setItemObjectClass('Varien_Object');
        $this->supportRowCount = [
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT . '_catalog',
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT . '_search',
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY
        ];
    }

    protected function _initSelect()
    {
        $this->getSelect()
            ->from(array('main_table' => $this->getMainTable()), [
                'id' => 'CONCAT(main_table.code, main_table.store_id)',
                'code' => 'code',
                'store_id' => 'store_id',
                'previous_reindex_at' => 'previous_reindex_at',
                'current_reindex_at' => 'current_reindex_at',
                'position' => $this->getConnection()->quoteInto(
                    'IF(code <> ?, 10, 999)',
                    EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_KEYWORD
                )
            ])
            ->order(['position asc', 'code asc'])
        ;

        return $this;
    }

    /**
     * Number of items in collection
     *
     * @return int
     */
    public function getSize()
    {
        return count(Mage::app()->getStores(false))*3;
    }

    /**
     * Updates grid with special actions
     * 
     * @return Varien_Data_Collection_Db
     */
    protected function _afterLoadData()
    {
        $missingItems = [];

        foreach (Mage::app()->getStores(false) as $store) {
            $missingItems[EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT . '_catalog'][$store->getId()] = true;
            $missingItems[EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT . '_search'][$store->getId()] = true;
            $missingItems[EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_KEYWORD][$store->getId()] = true;
        }


        $id = 1;
        foreach ($this->_data as $index => $item) {
            $this->_data[$index]['id'] = $id++;
            if (isset($missingItems[$item['code']][$item['store_id']])) {
                unset($missingItems[$item['code']][$item['store_id']]);
            }
        }
        
        if ($missingItems) {
            foreach ($missingItems as $code => $stores) {
                foreach (array_keys($stores) as $storeId) {
                    $this->_data[] = array(
                        'id' => $id ++,
                        'code' => $code,
                        'store_id' => $storeId,
                        'previous_reindex_at' => null,
                        'current_reindex_at' => null
                    );
                }
            }
        }
        
        foreach ($this->_data as $index => $item) {
            if (in_array($item['code'], $this->supportRowCount, true)) {
                $this->_data[$index]['pending_rows'] = $this->getResource()
                    ->getPendingRowCount($item['code'], $item['store_id']);
                $this->_data[$index]['indexed_rows'] = $this->getResource()
                    ->getIndexedRowCount($item['code'], $item['store_id']);
            } else {
                $this->_data[$index]['pending_rows'] = 0;
                $this->_data[$index]['indexed_rows'] = 0;
            }

            $state = EcomDev_Sphinx_Model_Source_Index_State::STATE_SYNCED;
            if ($this->_data[$index]['current_reindex_at'] === null) {
                $state = EcomDev_Sphinx_Model_Source_Index_State::STATE_NEW;
            } elseif (!empty($this->_data[$index]['pending_rows'])) {
                $state = EcomDev_Sphinx_Model_Source_Index_State::STATE_QUEUED;
            }
            
            $this->_data[$index]['state'] = $state;
        }
        
        return parent::_afterLoadData();
    }


}
