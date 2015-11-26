<?php

/**
 *
 * @method EcomDev_Sphinx_Model_Sort getDataObject()
 */
class EcomDev_Sphinx_Block_Adminhtml_Sort_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{
    protected $_fieldNameSuffix = 'sphinx_sort';

    /**
     * Sets up main fieldset
     *
     * @param string $code
     * @param string $label
     * @return $this
     */
    protected function _setUpFieldset($code, $label)
    {
        $this->_addFieldset($code, $label);
        $this->_fieldIdPattern = $code . '_%s';
        $this->_fieldNamePattern = 'configuration[' . $code . '][%s]';
        return $this;
    }

    protected function _createFields()
    {
        $this
            ->_addFieldset('general', $this->__('General Options'))
                ->_addField(
                    'code', 'text',
                    $this->__('Code'),
                    [
                        'class' => 'validate-code',
                        'note' => $this->__('The value is a part of url on the frontend')
                    ]
                )
                ->_addField(
                    'name', 'text',
                    $this->__('Name')
                )
                ->_addField(
                    'position', 'text',
                    $this->__('Position'),
                    [
                        'required' => false
                    ]
                )
            ->_setUpFieldset('sort', $this->__('Sort Options'))
            ->_addField('direction', 'multiselect', $this->__('Available Directions'), [
                'option_model' => 'ecomdev_sphinx/source_sort_direction',
                'required' => true
            ])
            ->_addField('order', 'js', $this->__('Orders'), [
                'js_class' => 'EcomDev.Sphinx.SortOrder',
                'js_template' => $this->getChildHtml('container'),
                'js_options' => [
                    'orders' => $this->getDataObject()->getAvailableSortOptions(),
                    'row_template' => $this->getChildHtml('row')
                ]
            ])
            ->_setUpFieldset('store_name', $this->__('Name in Store View'));

        foreach (Mage::app()->getStores(false) as $store) {
            $this->_addField($store->getCode(), 'text', $store->getName(), [
                'note' => $this->__('Leave empty to use default name'),
                'required' => false
            ]);
        }


        $this->getForm()->getElement('sort_direction')->setSize(2);




        return $this;
    }

    /**
     * Sets values for fields from data object
     *
     * @return $this
     */
    protected function _setFieldValues()
    {
        $configuration = $this->getDataObject()->getConfiguration();

        if (!is_array($configuration)) {
            return $this;
        }

        $options = array(
            'name' => $this->getDataObject()->getName(),
            'position' => $this->getDataObject()->getPosition(),
            'code' => $this->getDataObject()->getCode()
        );

        foreach ($configuration as $fieldSet => $values) {
            foreach ($values as $name => $value) {
                $options[$fieldSet . '_' . $name] = $value;
            }
        }

        $this->getForm()->setValues($options);
        return $this;
    }
}
