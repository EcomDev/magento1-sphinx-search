<?php

class EcomDev_Sphinx_Model_Source_Scope
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{

    protected function _initOptions()
    {
        $this->_options = array();

        $collection = Mage::getModel('ecomdev_sphinx/scope')->getCollection();
        $dataList = $collection->getData();
        foreach ($dataList as $item) {
            $this->_options[$item['scope_id']] = $item['name'];
        }

        return $this;
    }

}
