<?php

class EcomDev_Sphinx_Model_Resource_Sort
    extends EcomDev_Sphinx_Model_Resource_AbstractModel
{
    /**
     * Make configuration field as serializable
     *
     * @var array
     */
    protected $_serializableFields = [
        'configuration' => [
            ['sort' => ['direction' => ['asc', 'desc']]],
            ['sort' => ['direction' => ['asc', 'desc']]]
        ]
    ];

    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/sort', 'sort_id');
        $this->addUniqueField(
            ['field' => 'code', 'title' => Mage::helper('ecomdev_sphinx')->__(
                'Sort order with the same code'
            )]
        );
    }

    /**
     * Returns available sort options
     *
     * @return string[]
     */
    public function getAvailableSortOptions()
    {
        $selects = [];
        $selects[] = $this->_getReadAdapter()->select()
            ->from(
                ['attribute' => $this->getTable('eav/attribute')],
                ['attribute_code', 'frontend_label']
            )
            ->join(
                ['sphinx_attribute' => $this->getTable('ecomdev_sphinx/attribute')],
                'sphinx_attribute.attribute_id = attribute.attribute_id',
                []
            )
            ->where('sphinx_attribute.is_sort = ?', 1)
            ->where('sphinx_attribute.is_active = ?', 1);
        ;

        $selects[] = $this->_getReadAdapter()->select()
            ->from(
                ['field' => $this->getTable('ecomdev_sphinx/field')],
                ['code', 'name']
            )
            ->where('field.is_active = ?', 1)
            ->where('field.is_sort = ?', 1)
        ;

        $options = [];

        foreach ($selects as $select) {
            $options += $this->_getReadAdapter()->fetchPairs($select);
        }

        ksort($options);

        return $options;
    }
}
