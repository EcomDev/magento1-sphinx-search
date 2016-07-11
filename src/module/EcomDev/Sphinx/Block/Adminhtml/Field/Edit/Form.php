<?php

/**
 *
 * @method EcomDev_Sphinx_Model_Field getDataObject()
 */
class EcomDev_Sphinx_Block_Adminhtml_Field_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{
    protected $_fieldNameSuffix = 'sphinx_field';

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
                        'note' => $this->__('The value is a part of url on the frontend'),
                        'disabled' => true
                    ]
                )
                ->_addField(
                    'name', 'text',
                    $this->__('Name')
                )
                ->_addField(
                    'type', 'select',
                    $this->__('Type'),
                    [
                        'disabled' => true,
                        'option_model' => 'ecomdev_sphinx/source_field_type'
                    ]
                )
                ->_addField(
                    'related_attribute',
                    'select',
                    $this->__('Attribute'),
                    [
                        'option_model' => 'ecomdev_sphinx/source_attribute_active',
                        'disabled' => true
                    ]
                )
                ->_addField(
                    'is_active', 'select',
                    $this->__('Is Active?'),
                    [
                        'required' => true,
                        'option_model' => 'ecomdev_sphinx/source_yesno'
                    ]
                )
                ->_addField(
                    'is_sort', 'select',
                    $this->__('Is Sortable?'),
                    [
                        'required' => true,
                        'option_model' => 'ecomdev_sphinx/source_yesno'
                    ]
                )
                ->_addField(
                    'position', 'text',
                    $this->__('Position'),
                    [
                        'required' => false
                    ]
                )



        ;

        if ($this->getChild('container')) {
            $this->getChild('container')->setField($this->getDataObject());
        }


        if ($this->getChild('row')) {
            $this->getChild('row')->setField($this->getDataObject());
        }

        $this->_setUpFieldset('field', $this->__('Field Options'))
            ->_addField('map', 'js', $this->__('Mapping'), [
                'js_class' => 'EcomDev.Sphinx.Field' . ucfirst($this->getDataObject()->getType()),
                'js_template' => $this->getChildHtml('container'),
                'js_options' => [
                    'options' => $this->getDataObject()->getAvailableOptions(),
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

        Mage::dispatchEvent('ecomdev_sphinx_attribute_form_create_fields', ['form' => $this->getForm(), 'block' => $this]);

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

        $options = $this->getDataObject()->toArray(
            ['name', 'position', 'code', 'is_sort', 'is_active', 'type']
        );

        foreach ($configuration as $fieldSet => $values) {
            if (is_array($values)) {
                foreach ($values as $name => $value) {
                    $options[$fieldSet . '_' . $name] = $value;
                }
            }
        }

        $this->getForm()->setValues($options);
        return $this;
    }
}
