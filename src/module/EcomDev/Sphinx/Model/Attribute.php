<?php

/**
 * @method EcomDev_Sphinx_Model_Resource_Attribute_Collection getCollection()
 * @method $this setIsSystem(int $flag)
 * @method $this setIsLayered(int $flag)
 * @method $this setIsCustomValueAllowed(int $flag)
 * @method $this setFilterType(string $type)
 * @method $this setIsFulltext(int $flag)
 * @method $this setIsSort(int $flag)
 * @method $this setIsActive(int $flag)
 * @method int getIsSystem()
 * @method int getIsLayered()
 * @method int getIsCustomValueAllowed()
 * @method string getFilterType()
 * @method int getIsFulltext()
 * @method int getIsSort()
 * @method int getIsActive()
 *
 */
class EcomDev_Sphinx_Model_Attribute
    extends EcomDev_Sphinx_Model_AbstractModel
{
    const ENTITY = 'sphinx_attribute';

    const CACHE_TAG = 'SPHINX_ATTRIBUTE';

    /**
     * Cache tag for cleaning it up on the frontend
     *
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Entity used to invoke indexation process
     *
     * @var string
     */
    protected $_indexerEntity = self::ENTITY;
    
    /**
     * @var Mage_Catalog_Model_Resource_Eav_Attribute|bool
     */
    protected $_attribute = null;
    
    /**
     * Initialization of resource
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/attribute');
    }

    /**
     * Returns a real attribute instance
     * 
     * @return Mage_Catalog_Model_Resource_Eav_Attribute|bool
     */
    public function getAttribute()
    {
        if (!$this->getAttributeCode()) {
            $this->_attribute = false;
        } elseif ($this->_attribute === null) {
            $this->_attribute = $this->getEavConfig()->getAttribute(
                Mage_Catalog_Model_Product::ENTITY, $this->getAttributeCode()
            );
        } 

        return $this->_attribute;
    }

    /**
     * Returns an attribute code
     * 
     * @return bool|mixed
     */
    public function getAttributeCode()
    {
        if (!$this->getId()) {
            return false;
        } elseif (!$this->hasData('attribute_code')) {
            // Pre-loads all attribute codes
            $this->getEavConfig()->getEntityAttributeCodes(Mage_Catalog_Model_Product::ENTITY);
            $attribute = $this->getEavConfig()->getAttribute(Mage_Catalog_Model_Product::ENTITY, $this->getId());
            if ($attribute) {
                $this->setData('attribute_code', $attribute->getAttributeCode());
            } else {
                $this->setData('attribute_code', false);
            }
        }
        
        return $this->_getData('attribute_code');
    }

    /**
     * Returns backend type
     * 
     * @return string
     */
    public function getBackendType()
    {
        $this->_setFromAttribute('backend_type');
        return $this->_getData('backend_type');
    }

    /**
     * Returns frontend input
     *
     * @return string
     */
    public function getFrontendInput()
    {
        $this->_setFromAttribute('frontend_input');
        return $this->_getData('frontend_input');
    }

    /**
     * Returns a resource model class
     * 
     * @return string 
     */
    public function getSourceModel()
    {
        $this->_setFromAttribute('source_model');
        return $this->_getData('source_model');
    }

    /**
     * Returns true if attribute is considered as an option
     * 
     * @param Mage_Catalog_Model_Resource_Eav_Attribute|null 
     * @return bool
     */
    public function isOption($attribute = null)
    {
        if ($attribute === null) {
            $attribute = $this;
        }
        
        return in_array($attribute->getBackendType(), array('varchar', 'int'), true) 
                && in_array($attribute->getFrontendInput(), array('select', 'multiselect'), true)
                && ($attribute->getSourceModel() === 'eav/entity_attribute_source_table' 
                    || $attribute->getSourceModel() === null);
    }
    
    /**
     * Sets a data from an attribute
     * 
     * @param string $field
     * @return $this
     */
    protected function _setFromAttribute($field)
    {
        if (!$this->hasData($field) && $this->getAttribute()) {
            $this->setData($field, $this->getAttribute()->getDataUsingMethod($field));
        }
        
        return $this;
    }

    /**
     * Returns an instance of eav config model
     * 
     * @return Mage_Eav_Model_Config
     */
    public function getEavConfig()
    {
        return Mage::getSingleton('eav/config');
    }

    /**
     * Sets data into model from post array
     *
     * @param array $data
     * @return $this
     */
    public function setDataFromArray(array $data)
    {
        $modifiableFields = array(
            'is_fulltext',
            'is_layered',
            'is_custom_value_allowed',
            'filter_type',
            'position',
            'is_sort'
        );
        
        if (!$this->getIsSystem()) {
            $modifiableFields[] = 'is_active';
        }
        
        $this->importData(
            $data, $modifiableFields
            
        );
        return $this;
    }

    protected function _initValidation()
    {
        $this->_addEmptyValueValidation('attribute_code', $this->__('Attribute'), self::VALIDATE_LIGHT);
        if ($this->getIsLayered()) {
            $this->_addOptionValidation(
                'filter_type', 
                $this->__('Filter Type value is not matching available options'),
                'ecomdev_sphinx/source_attribute_filter_type',
                self::VALIDATE_FULL
            );
        } else {
            $this->setFilterType(null);
            $this->setIsCustomValueAllowed(0);
        }
        
        if (!$this->getIsSystem()) {
            $this->_addValueValidation(
                'attribute', 
                $this->__('Attribute cannot be used for sphinx'), 
                function ($value) {
                    $isNotValid = $value->getData('backend_table')
                    || (!$value->getIsUserDefined() && !in_array($value->getFrontendInput(), array('media_image', 'select')))
                    || $value->getBackendType() === 'static'
                    || trim($value->getFrontendLabel()) === '';
                    return !$isNotValid;
                }, 
                self::VALIDATE_LIGHT
            );
        }
        
        return $this;
    }

    /**
     * Returns index table
     * 
     * @return string 
     */
    public function getIndexTable()
    {
        return $this->_getResource()->getIndexTable($this);
    }

    /**
     * Return a sphinx type string
     * 
     * @return string
     */
    public function getSphinxType()
    {
        if ($this->getFrontendInput() === 'select' 
            && $this->getSourceModel() === 'eav/entity_attribute_source_boolean') {
            return 'attr_bool';
        } elseif ($this->getBackendType() === 'int') {
            return 'attr_uint';
        } elseif ($this->getBackendType() === 'text') {
            return ($this->getIsFulltext() && $this->getIsActive() ? 'field_string' : 'attr_string');
        } elseif ($this->getBackendType() === 'datetime') {
            return 'attr_timestamp';
        } elseif ($this->getBackendType() === 'decimal') {
            return 'attr_float';
        }
        
        return ($this->getIsFulltext() && $this->getIsActive() ? 'field_string' : 'attr_string');
    }

    /**
     * Returns a facet model for an attribute if it is available
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_FacetInterface|bool
     */
    public function getFacetModel()
    {
        if (!$this->getIsLayered()) {
            return false;
        }

        $classByType = array(
            'price' => Mage::getConfig()->getModelClassName('ecomdev_sphinx/sphinx_facet_attribute_price'),
            'option' => Mage::getConfig()->getModelClassName('ecomdev_sphinx/sphinx_facet_attribute_option')
        );
        
        if ($this->getAttributeCode() === 'price') {
            return new $classByType['price']($this, $this->getRangeStep(), $this->getRangeCount(), $this->getCustomerGroupId());
        } elseif ($this->isOption()) {
            return new $classByType['option']($this);
        }
        
        return false;
    }
}
