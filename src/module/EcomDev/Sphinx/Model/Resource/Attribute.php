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
     * Returns list of attribute identifiers by attribute code
     *
     * @param string[] $attributeCodes
     * @return int[]
     */
    public function fetchAttributeIdsByCodes(array $attributeCodes, $entityType)
    {
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                ['attribute' => $this->getTable('eav/attribute')],
                ['attribute_code', 'attribute_id']
            )
            ->join(
                ['entity_type' => $this->getTable('eav/entity_type')],
                'attribute.entity_type_id = entity_type.entity_type_id',
                []
            )
            ->where('entity_type.entity_type_code = ?', $entityType)
            ->where('attribute_code IN(?)', $attributeCodes);

        return $this->_getReadAdapter()->fetchPairs($select);
    }
}
