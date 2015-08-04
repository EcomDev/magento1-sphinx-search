<?php

class EcomDev_Sphinx_Block_Adminhtml_Scope_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{

    protected $_fieldNameSuffix = 'sphinx_scope';

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
        $nonRequired = array('required' => false);
        $this

            ->_setUpFieldset('general', $this->__('General Filters Configuration'))
                ->_addField('limit_facet', 'multiselect', $this->__('Exclude Specified Facets'), array(
                        'option_model' => 'ecomdev_sphinx/source_attribute_layered'
                    ) + $nonRequired)
                ->_fieldComment('limit_facet', $this->__('Leave empty to use all facets for layered navigation'))

            ->_setUpFieldset('category_filter', $this->__('Category Filter Options'))
                ->_addField('label', 'text', $this->__('Category Filter Label'), $nonRequired)
                ->_addField('include_same_level', 'select', $this->__('Use Same Level Categories'), array(
                    'option_model' => 'ecomdev_sphinx/source_yesno'
                ) + $nonRequired)
                ->_addField('renderer', 'select', $this->__('Filter Renderer'), array(
                    'option_model' => 'ecomdev_sphinx/source_category_filter_type'
                ) + $nonRequired)
                ->_fieldComment('label', $this->__('Override to change a text next to the categories tree'))
                ->_fieldComment('renderer', $this->__('Specifies which renderer should be used on category filter'))
            ->_setUpFieldset('price_filter', $this->__('Price Filter Options'))
                ->_addField('range_step', 'text', $this->__('Step Size'), $nonRequired)
                ->_addField('range_count', 'text', $this->__('Number of Steps'), $nonRequired)
                ->_fieldComment('range_step', $this->__('Incremental value for a price filter'))
                ->_fieldComment('range_count', $this->__('Number of price entries'))
        ;
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

        $options = array();
        foreach ($configuration as $fieldSet => $values) {
            foreach ($values as $name => $value) {
                $options[$fieldSet . '_' . $name] = $value;
            }
        }

        $this->getForm()->setValues($options);
        return $this;
    }
}
