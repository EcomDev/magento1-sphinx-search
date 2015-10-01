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
     * Returns updated at timestamp for metadata
     *
     * @param string $code
     * @param int $storeId
     * @return string
     */
    public function getMetaDataUpdatedAt($code, $storeId)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('ecomdev_sphinx/index_metadata'), 'current_reindex_at');
        $select->where('store_id = ?', $storeId);
        $select->where('code = ?', $code);

        $reindexAt = $this->_getReadAdapter()->fetchOne($select);
        return $reindexAt ?: Varien_Date::now();
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
            $this->getTable('catalog/product_index_price')
        );

        unset($baseColumns['entity_id']);
        unset($baseColumns['customer_group_id']);
        unset($baseColumns['website_id']);
        unset($baseColumns['tax_class_id']);

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


    /**
     * Returns attribute options per store view
     *
     * @param int[] $attributeIds
     *
     * @return string[][][]
     */
    public function getAttributeOptions(array $attributeIds)
    {
        if (!$attributeIds) {
            return [];
        }

        $select = $this->_getReadAdapter()->select();
        $select->from(['option' => $this->getTable('eav/attribute_option')], ['attribute_id', 'option_id'])
            ->join(
                ['label' => $this->getTable('eav/attribute_option_value')],
                'label.option_id = option.option_id',
                ['store_id', 'value']
            )
            ->order(['option.attribute_id ASC', 'option.sort_order ASC', 'option.option_id ASC']);

        $select->where('option.attribute_id IN(?)', $attributeIds);

        $result = [];

        // Iterate over result, so no double fetch is done
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            if (empty($row['value'])) {
                continue;
            }
            $result[$row['attribute_id']][$row['option_id']][$row['store_id']] = $row['value'];
        }

        return $result;
    }
}
