<?php
use EcomDev_Sphinx_Model_Source_Level as LevelSource;

class EcomDev_Sphinx_Block_Adminhtml_Scope_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{

    protected $_fieldNameSuffix = 'sphinx_scope';

    protected $_defaultValues = [];

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

            ->_setUpFieldset('general', $this->__('General Results Configuration'))
                ->_addField('max_matches', 'text', $this->__('Max Matches'), $nonRequired)
                ->_fieldComment('max_matches', $this->__('Maximum number of results returned by Sphinx. Overrides default value in System / Configuration'))
                ->_addField('include_facet', 'multiselect', $this->__('Include Specified Facets'), array(
                        'option_model' => 'ecomdev_sphinx/source_attribute_layered',
                        'note' => $this->__('Specifing value in Including rule, overrides excluding rule')
                    ) + $nonRequired)
                ->_addField('limit_facet', 'multiselect', $this->__('Exclude Specified Facets'), array(
                        'option_model' => 'ecomdev_sphinx/source_attribute_layered'
                    ) + $nonRequired)
                ->_addField('virtual_field', 'multiselect', $this->__('Include Virtual Facets'), array(
                        'option_model' => 'ecomdev_sphinx/source_field'
                    ) + $nonRequired)
                ->_fieldComment('limit_facet', $this->__('Leave empty to use all facets for layered navigation'))

            ->_setUpFieldset('sort_order', $this->__('Custom Sort Options'))
                ->_addField('is_active', 'select', $this->__('Is Active'), array(
                        'option_model' => 'ecomdev_sphinx/source_yesno'
                    ) + $nonRequired)
                ->_addField('include_order', 'multiselect', $this->__('Include Sort Order'), array(
                        'option_model' => 'ecomdev_sphinx/source_sort_order'
                    ) + $nonRequired)
                ->_addField('exclude_order', 'multiselect', $this->__('Exclude Sort Order'), array(
                        'option_model' => 'ecomdev_sphinx/source_sort_order'
                    ) + $nonRequired)
                ->_fieldComment('is_active', $this->__('Enabling this feature, allows to use custom sort orders'))
                ->_fieldComment('include_order', $this->__('Specifing value in Including rule, overrides excluding rule'))
                ->_fieldComment('exclude_order', $this->__('Leave empty to use all sort order for layered navigation'))
                ->_fieldDependence('exclude_order', 'is_active', '1')
                ->_fieldDependence('include_order', 'is_active', '1')
            ->_setUpFieldset('category_filter', $this->__('Category Filter Options'))
                ->_addField('is_active', 'select', $this->__('Is Active'), array(
                        'option_model' => 'ecomdev_sphinx/source_yesno'
                    ) + $nonRequired)
                ->_addField('label', 'text', $this->__('Category Filter Label'), $nonRequired)
                ->_addField('max_level_deep', 'text', $this->__('Category Fetch Level'), $nonRequired)
                ->_addField('include_same_level', 'select', $this->__('Custom Category Level'), array(
                    'option_model' => 'ecomdev_sphinx/source_level'
                ) + $nonRequired)
                ->_addField('top_category_level', 'text', $this->__('Top Category Level'), $nonRequired)
                ->_fieldComment('top_category_level', $this->__('Specify level from which tree of categories will be rendered'))
                ->_fieldDependence('top_category_level', 'include_same_level', LevelSource::LEVEL_CUSTOM)
                ->_addField('only_direct_products', 'select', $this->__('Show only direct products'), array(
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
            ->_setUpFieldset('facet_sort_order', $this->__('Facet Position Order'))
        ;

        $options = Mage::getSingleton('ecomdev_sphinx/source_field')->getOptions();
        $options += Mage::getSingleton('ecomdev_sphinx/source_attribute_layered')->getOptions();

        $this->_addField('active', 'select', $this->__('Enable Custom Sort Orders'), [
                'option_model' => 'ecomdev_sphinx/source_yesno'
            ] + $nonRequired
        );

        $this->_defaultValues['facet_sort_order_active'] = 0;

        foreach ($options as $attributeCode => $label) {
            $this->_addField(sprintf('%s_override', $attributeCode), 'select', $this->__('Override for %s', $label), [
                    'option_model' => 'ecomdev_sphinx/source_yesno'
                ] + $nonRequired);
            $this->_addField(sprintf('%s_value', $attributeCode), 'text', $this->__('Position for %s', $label));
            $this->_fieldDependence(sprintf('%s_override', $attributeCode), 'active', 1);
            $this->_fieldDependence(sprintf('%s_value', $attributeCode), sprintf('%s_override', $attributeCode), 1);
            $this->_defaultValues[sprintf('facet_sort_order_%s_override', $attributeCode)] = 0;
        }


        $this
            ->_setUpFieldset('search_attribute', $this->__('Search Options'))
                ->_addField('active', 'select', $this->__('Enable Custom Fields'), [
                        'option_model' => 'ecomdev_sphinx/source_yesno'
                    ] + $nonRequired
                );
        ;

        $options = [
            's_anchor_category_names' => $this->__('Category Names (Anchor)'),
            's_direct_category_names' => $this->__('Category Names (Direct)'),
            'request_path' => $this->__('Product Url')
        ];

        foreach (Mage::getSingleton('ecomdev_sphinx/config')
                     ->getSearchableAttributes() as $attributeCode => $attribute) {
            $options[$attributeCode] = $attribute->getAttribute()->getStoreLabel();
        }

        foreach ($options as $attributeCode => $label) {
            $this->_addField(sprintf('%s_active', $attributeCode), 'select', $this->__('Enable search by %s', $label),
                ['option_model' => 'ecomdev_sphinx/source_yesno'] + $nonRequired);
            $this->_addField(sprintf('%s_weight', $attributeCode), 'text', $this->__('Weight for %s', $label));
            $this->_fieldDependence(sprintf('%s_weight', $attributeCode), sprintf('%s_active', $attributeCode), 1);
            $this->_fieldDependence(sprintf('%s_active', $attributeCode), 'active', 1);

            $this->_defaultValues[sprintf('search_attribute_%s_active', $attributeCode)] = 0;
            $this->_defaultValues[sprintf('search_attribute_%s_weight', $attributeCode)] = 10;
        }


        Mage::dispatchEvent('ecomdev_sphinx_scope_form_create_fields', ['form' => $this->getForm(), 'block' => $this]);

        $this->getDataObject();

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

        foreach ($this->_defaultValues as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        $this->getForm()->setValues($options);
        return $this;
    }
}
