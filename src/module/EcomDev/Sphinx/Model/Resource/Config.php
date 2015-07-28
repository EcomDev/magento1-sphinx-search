<?php

class EcomDev_Sphinx_Model_Resource_Config
    extends Mage_Core_Model_Resource_Db_Abstract
{
    protected $_systemAttributes;
    
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product', 'document_id');
    }

    /**
     * Returns metadata table name
     * 
     * @return string
     */
    public function getMetaDataTable()
    {
        return $this->getTable('ecomdev_sphinx/index_metadata');
    }
    
    /**
     * @return array
     */
    public function getUsedAttributeCodes()
    {
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('sphinx_attribute' => $this->getTable('ecomdev_sphinx/attribute')),
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

    /**
     * List of system attribute codes
     * 
     * @return string[]
     */
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
     * Returns all customer group ids
     * 
     * @return array
     */
    public function getAllCustomerGroupIds()
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('customer/customer_group'), 'customer_group_id');
        return $this->_getReadAdapter()->fetchCol($select);
    }
    
    /**
     * Returns price columns for index
     *
     * @return string[]
     */
    public function getPriceIndexColumns()
    {
        $baseColumns = $this->_getReadAdapter()->describeTable(
            $this->getTable('ecomdev_sphinx/index_product_price')
        );

        unset($baseColumns['document_id']);
        unset($baseColumns['customer_group_id']);

        return array_keys($baseColumns);
    }

    /**
     * Returns columns for index
     *
     * @return string[]
     */
    public function getIndexColumns()
    {
        $attributeColumns = $this->_getReadAdapter()->describeTable(
            $this->getTable('ecomdev_sphinx/index_product_attribute')
        );
        
        unset($attributeColumns['document_id']);
        
        return array_keys($attributeColumns);
    }
    
}
