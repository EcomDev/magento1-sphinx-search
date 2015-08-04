<?php

class EcomDev_Sphinx_Model_Resource_Scope
    extends EcomDev_Sphinx_Model_Resource_AbstractModel
{
    protected $_parentScopeIds;

    /**
     * Make configuration field as serializable
     *
     * @var array
     */
    protected $_serializableFields = array(
        'configuration' => array(array(), array())
    );


    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/scope', 'scope_id');
    }
    
    public function getParentScopeIds()
    {
        if ($this->_parentScopeIds !== null) {
            return $this->_parentScopeIds;
        }
        
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('scope_id', 'parent_id'))
            ->order('parent_id ASC');
        
        $idPairs = $this->_getReadAdapter()->fetchPairs($select);
        foreach ($idPairs as $scopeId => $parentId) {
            $this->_parentScopeIds[$scopeId] = array();
            while ($parentId) {
                $this->_parentScopeIds[$scopeId][] = $parentId;
                if (empty($this->_parentScopeIds[$parentId])) {
                    break;
                }
                $parentId = reset($this->_parentScopeIds[$parentId]);
            }
        }
        
        return $this->_parentScopeIds;
    }

    /**
     * Return option id by option label
     * 
     * @param $optionIds
     * @param $storeId
     * @return string[]
     */
    public function getOptionNames($optionIds, $storeId)
    {
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('option_default' => $this->getTable('eav/attribute_option_value')),
                array()
            )
            ->join(
                array('option' => $this->getTable('eav/attribute_option')),
                'option.option_id = option_default.option_id',
                array()
            )
            ->joinLeft(
                array('option_store' => $this->getTable('eav/attribute_option_value')),
                'option_store.option_id = option_default.option_id and option_store.store_id = :store_id',
                array()
            )
            ->where('option_default.store_id = 0')
            ->where('option_default.option_id IN(?)', $optionIds)
            ->columns(
                array(
                    'option_id', 
                    'label' => 'IF(option_store.option_id IS NULL, option_default.value, option_store.value)'
                ),
                'option_default'
            )
            ->order(array('option.attribute_id ASC', 'option.sort_order ASC', 'option.option_id ASC'));
        ;
        
        return $this->_getReadAdapter()->fetchPairs(
            $select, 
            array('store_id' => $storeId)
        );
    }
}
