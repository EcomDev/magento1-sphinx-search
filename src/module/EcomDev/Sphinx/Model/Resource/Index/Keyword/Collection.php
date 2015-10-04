<?php

class EcomDev_Sphinx_Model_Resource_Index_Keyword_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_keyword');
    }

    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        $this->_select = $this->getSelect()->from(
            array('main_table' => $this->getMainTable()), ['id' => "CONCAT(keyword, '----', store_id)", '*']
        );

        return $this;
    }


}
