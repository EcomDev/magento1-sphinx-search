<?php

class EcomDev_Sphinx_Model_Resource_Field
    extends EcomDev_Sphinx_Model_Resource_AbstractModel
{
    /**
     * Make configuration field as serializable
     *
     * @var array
     */
    protected $_serializableFields = [
        'configuration' => [
            [],
            []
        ]
    ];

    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/field', 'field_id');
        $this->addUniqueField(
            ['field' => 'code', 'title' => Mage::helper('ecomdev_sphinx')->__(
                'Field with the same code'
            )]
        );
    }

    /**
     * Returns available options options
     *
     * @return string[]
     */
    public function getAvailableOptions($attributeId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from(
                ['option' => $this->getTable('eav/attribute_option')],
                ['option_id']
            )
            ->join(
                ['option_value_default' => $this->getTable('eav/attribute_option_value')],
                'option_value_default.option_id = option.option_id and option_value_default.store_id = 0',
                ['option_value_default.value']
            )
            ->where('option.attribute_id = ?', $attributeId)
        ;

        return $this->_getReadAdapter()->fetchPairs($select);
    }
}
