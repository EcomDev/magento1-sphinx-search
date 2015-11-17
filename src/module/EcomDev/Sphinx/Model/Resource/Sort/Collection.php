<?php

class EcomDev_Sphinx_Model_Resource_Sort_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/sort');
    }

    /**
     * After loads
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        /** @var EcomDev_Sphinx_Model_Sort $item */
        foreach ($this->getItems() as $item) {
            $this->getResource()->unserializeFields($item);
        }

        return parent::_afterLoad();
    }


}
