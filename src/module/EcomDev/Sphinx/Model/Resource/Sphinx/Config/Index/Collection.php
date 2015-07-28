<?php

/**
 * @method EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index getResource()
 */
class EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/sphinx_config_index');
        $this->setItemObjectClass('Varien_Object');
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->where(
            'main_table.code IN(?)', 
            array(
                EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT,
                EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY,
            )
        );
        return $this;
    }

    /**
     * Updates grid with special actions
     * 
     * @return Varien_Data_Collection_Db
     */
    protected function _afterLoadData()
    {
        $missingItems = array(
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT => true,
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY => true,
        );
        
        foreach ($this->_data as $item) {
            if (isset($missingItems[$item['code']])) {
                unset($missingItems[$item['code']]);
            }
        }
        
        if ($missingItems) {
            foreach (array_keys($missingItems) as $code) {
                $this->_data[] = array(
                    'code' => $code,
                    'previous_reindex_at' => null,
                    'current_reindex_at' => null
                );
            }
        }
        
        foreach ($this->_data as $index => $item) {
            $this->_data[$index]['pending_rows'] = $this->getResource()->getPendingRowCount($item['code']);
            $this->_data[$index]['indexed_rows'] = $this->getResource()->getIndexedRowCount($item['code']);
            
            $state = EcomDev_Sphinx_Model_Source_Index_State::STATE_SYNCED;
            if ($this->_data[$index]['current_reindex_at'] === null) {
                $state = EcomDev_Sphinx_Model_Source_Index_State::STATE_NEW;
            } elseif ($this->_data[$index]['pending_rows']) {
                $state = EcomDev_Sphinx_Model_Source_Index_State::STATE_QUEUED;
            }
            
            $this->_data[$index]['state'] = $state;
        }
        
        return parent::_afterLoadData();
    }


}
