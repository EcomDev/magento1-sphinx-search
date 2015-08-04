<?php

/**
 * Basic form container
 *
 */
abstract class EcomDev_Sphinx_Block_Adminhtml_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Fieldset
     *
     * @var Varien_Data_Form_Element_Fieldset
     */
    protected $_currentFieldset;

    protected $_fieldNameSuffix = '';

    /**
     * Pattern for field identifier
     *
     * @var string
     */
    protected $_fieldIdPattern = '';

    /**
     * Pattern for field name
     *
     * @var string
     */
    protected $_fieldNamePattern = '';

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $this->_beforePrepareForm();

        if (!$this->getForm()) { // If subclass didn't initialize form
            $this->_addFormContainer();
        }

        $this->getForm()->setDataObject($this->getDataObject());

        $this->_beforeCreateFields();
        $this->_createFields();
        $this->_setFieldValues();
        $this->_afterCreateFields();
        
        return parent::_prepareForm();
    }

    protected function _beforeCreateFields()
    {
        if ($this->_fieldNameSuffix) {
            $this->getForm()->setFieldNameSuffix($this->_fieldNameSuffix);
        }
        return $this;
    }

    protected function _beforePrepareForm()
    {
        return $this;
    }

    abstract protected function _createFields();

    protected function _afterCreateFields()
    {
        return $this;
    }

    /**
     * Sets values for fields from data object
     * 
     * @return $this
     */
    protected function _setFieldValues()
    {
        $this->getForm()->setValues($this->getDataObject()->getData());
        return $this;
    }

    /**
     * Adds a new fieldset to a field
     * 
     * @param string $name
     * @param string $label
     * @return $this
     */
    protected function _addFieldset($name, $label)
    {
        $this->_currentFieldset = $this->getForm()->addFieldset($name, array(
            'legend' => $label,
        ));

        $this->_addElementTypes($this->_currentFieldset);
        return $this;
    }

    /**
     * Adds a field to current fieldset
     *
     * If options contain source_model or options_model
     * it will use it will retrieve values for selects from it
     *
     * @param string $name
     * @param string $type
     * @param string $label
     * @param array $options
     * @return $this
     */
    protected function _addField($name, $type, $label, $options = array())
    {
        if ($this->_currentFieldset === null) {
            $this->_addFieldset(
                'general',
                $this->__('General')
            );
        }

        $options['label'] = $label;
        $options['title'] = $label;

        if (!isset($options['required'])) {
            $options['required'] = true;
        }
        
        if (!isset($options['name'])) {
            if ($this->_fieldNamePattern) {
                $options['name'] = sprintf($this->_fieldNamePattern, $name);
            } else {
                $options['name'] = $name;
            }
        }

        $fieldId = $name;

        if ($this->_fieldIdPattern) {
            $fieldId = sprintf($this->_fieldIdPattern, $fieldId);
        }

        $sourceModel = false;
        if (isset($options['source_model'])) {
            $sourceModel = Mage::getModel('ecomdev_sphinx/source_default')
                ->setSourceModel($options['source_model']);
            unset($options['source_model']);
        } elseif (isset($options['option_model'])) {
            $sourceModel = Mage::getSingleton($options['option_model']);
            unset($options['option_model']);
        }
        
        if ($sourceModel instanceof EcomDev_Sphinx_Model_Source_AbstractOption) {
            if (isset($options['excluded_options'])) {
                $sourceModel->setExcludedOptions($options['excluded_options']);
                unset($options['excluded_options']);
            }
            
            $options['values'] = $sourceModel
                ->toOptionArray($type === 'multiselect');
        }
        
        if ($type == 'multiselect') {
            $options['can_be_empty'] = true;
        }

        $this->_currentFieldset
            ->addField($fieldId, $type, $options);
        
        return $this;
    }

    /**
     * Modifies field properties
     *
     * @param string $name
     * @param array $values
     * @return $this
     */
    protected function _modifyField($name, array $values)
    {
        if (isset($this->_fieldIdPattern)) {
            $name = sprintf($this->_fieldIdPattern, $name);
        }

        if ($this->getForm() && $element = $this->getForm()->getElement($name)) {
            foreach ($values as $key => $value) {
                $element->setDataUsingMethod($key, $value);
            }
        }

        return $this;
    }

    /**
     * Adds field comment
     *
     * @param string $name
     * @param string $comment
     * @return $this
     */
    protected function _fieldComment($name, $comment)
    {
        return $this->_modifyField($name, array('note' => $comment));
    }

    /**
     * Adds field dependency
     * 
     * @param string $field
     * @param string $parent
     * @param string $parentValue
     * @return $this
     */
    protected function _fieldDependence($field, $parent, $parentValue)
    {
        $this->_getDependence()
            ->addFieldMap($field, $field)
            ->addFieldMap($parent, $parent)
            ->addFieldDependence($field, $parent, $parentValue);
        return $this;
    }
    
    /**
     * Retrieve current brand model as data object for form
     *
     * @return EcomDev_ProductAssortment_Model_AbstractModel
     */
    public function getDataObject()
    {
        return Mage::registry('current_object');
    }

    /**
     * Creates form container tag
     *
     * @return $this
     */
    protected function _addFormContainer()
    {
        if (!$this->hasData('id')) {
            $this->setData('id', 'edit_form');
        }
        
        $form = new Varien_Data_Form(array(
            'id' => $this->getId(),
            'action' => $this->getData('action'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $form->setUseContainer(true);
        $this->setForm($form);
        return $this;
    }

    /**
     * Retrieve predefined additional element types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return array(
            'image' => Mage::getConfig()->getBlockClassName('ecomdev_productassortment/adminhtml_form_element_image')
        );
    }

    /**
     * Adds dependency js block, if it is required
     * 
     * @param string $html
     * @return string
     */
    protected function _afterToHtml($html)
    {
        if ($this->getChild('element_dependence')) {
            $html .= $this->getChildHtml('element_dependence');
        }
        
        return parent::_afterToHtml($html);
    }
    
    /**
     * Return dependency block object
     *
     * @return Mage_Adminhtml_Block_Widget_Form_Element_Dependence
     */
    protected function _getDependence()
    {
        if (!$this->getChild('element_dependence')){
            $this->setChild(
                'element_dependence', 
                $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            );
        }
        
        return $this->getChild('element_dependence');
    }
}
