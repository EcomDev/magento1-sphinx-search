<?php

class EcomDev_Sphinx_Model_Resource_Attribute
    extends Mage_Core_Model_Resource_Db_Abstract
{
    protected $_isPkAutoIncrement = false;
    
    protected $_systemAttributes;
    
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/attribute', 'attribute_id');
    }
    
    public function getUsedAttributeCodes()
    {
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('sphinx_attribute' => $this->getMainTable()), 
                array()
            )
            ->join(
                array('attribute' => $this->getTable('eav/attribute')), 
                'attribute.attribute_id = sphinx_attribute.attribute_id',
                'attribute_code'
            )
        ;
        
        return array_merge($this->getSystemAttributes(), $this->_getReadAdapter()->fetchCol($select));
    }
    
    public function getSystemAttributes()
    {
        if ($this->_systemAttributes === null) {
            $this->_systemAttributes = array_keys(
                $this->_getReadAdapter()->describeTable(
                    $this->getTable('ecomdev_sphinx/index_product')
                )
            );
            
            $this->_systemAttributes[] = 'price';
        }
        
        return $this->_systemAttributes;
    }

    /**
     * Returns an index table for an attribute
     * 
     * @param EcomDev_Sphinx_Model_Attribute $attribute
     * @return string
     */
    public function getIndexTable(EcomDev_Sphinx_Model_Attribute $attribute)
    {
        if ($attribute->isOption()) {
            return $this->getTable('ecomdev_sphinx/index_product_option');
        } elseif ($attribute->getBackendType() === 'int') {
            return $this->getTable('ecomdev_sphinx/index_product_integer');
        } elseif ($attribute->getBackendType() === 'text') {
            return $this->getTable('ecomdev_sphinx/index_product_text');
        } elseif ($attribute->getBackendType() === 'datetime') {
            return $this->getTable('ecomdev_sphinx/index_product_timestamp');
        } elseif ($attribute->getAttributeCode() === 'price' ) {
            return $this->getTable('ecomdev_sphinx/index_product_price');
        } elseif ($attribute->getBackendType() === 'decimal') {
            return $this->getTable('ecomdev_sphinx/index_product_decimal');
        }
        
        return $this->getTable('ecomdev_sphinx/index_product_string');
    }
}
