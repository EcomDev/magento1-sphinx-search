<?php

/**
 * Option hash for attributes
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav_Option_Hash
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractResource
{
    private $options = [];
    private $optionsToLoad = [];

    protected function _construct()
    {
        $this->_setResource('ecomdev_sphinx');
    }
    /**
     * @param int $value
     * @param $attributeCode
     * @return bool
     */
    public function getOption($value, $attributeCode)
    {
        if (isset($this->options[$value . '_' . $attributeCode])) {
            return $this->options[$value . '_' . $attributeCode];
        }

        return false;
    }

    /**
     * Adds option to hash
     *
     * @param string $value
     * @param string $attributeCode
     * @return $this
     */
    public function addOptionValues($value, $attributeCode)
    {
        if (empty($value)) {
            return $this;
        }

        if (!is_array($value)) {
            $this->options[$value . '_' . $attributeCode] = false;
            $this->optionsToLoad[$value] = $value;
        } else {
            foreach ($value as $option) {
                $this->options[$option . '_' . $attributeCode] = true;
                $this->optionsToLoad[$option] = $option;
            }
        }

        return $this;
    }

    /**
     * Loads options for attributes
     *
     * @param Mage_Core_Model_Abstract $storeId
     * @return $this
     */
    public function loadOptions($storeId)
    {
        if (!$this->optionsToLoad) {
            return $this;
        }

        $this->fillMemoryTable('option_id', $this->optionsToLoad);

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('option' => $this->getTable('eav/attribute_option')),
                array()
            )
            ->join(
                array('option_id' => $this->getMemoryTableName('option_id')),
                'option.option_id = option_id.id',
                []
            )
            ->join(
                array('option_value' => $this->getTable('eav/attribute_option_value')),
                'option_value.option_id = option.option_id',
                array()
            )
            ->join(
                array('attribute' => $this->getTable('eav/attribute')),
                'attribute.attribute_id = option.attribute_id',
                array()
            )
            ->where('option_value.store_id IN(:store_id, 0)')
            ->columns([
                'option_id' => 'option.option_id',
                'attribute_code' => 'attribute.attribute_code',
                'label' => 'option_value.value'
            ])
            ->order('option_value.store_id ASC')
        ;

        foreach ($this->_getReadAdapter()->query($select, ['store_id' => $storeId]) as $row) {
            $this->options[$row['option_id'] . '_' . $row['attribute_code']] = $row['label'];
        }

        return $this;
    }
}
