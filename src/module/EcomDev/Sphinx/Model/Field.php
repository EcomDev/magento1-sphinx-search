<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;

/**
 * @method EcomDev_Sphinx_Model_Resource_Field_Collection getCollection()
 * @method EcomDev_Sphinx_Model_Resource_Field _getResource()
 * @method EcomDev_Sphinx_Model_Resource_Field getResource()
 * @method $this setConfiguration(array $configuration)
 * @method array getConfiguration()
 */
class EcomDev_Sphinx_Model_Field
    extends EcomDev_Sphinx_Model_AbstractModel
{
    /**
     * Cache tag for cleaning it up on the frontend
     *
     * @var string
     */
    protected $_cacheTag = EcomDev_Sphinx_Model_Scope::CACHE_TAG;
    
    /**
     * Initialization of resource
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/field');
    }

    /**
     * Sets data into model from post array
     *
     * @param array $data
     * @return $this
     */
    public function setDataFromArray(array $data)
    {
        $jsonData = array();

        if (isset($data['configuration']) && is_array($data['configuration'])) {
            $jsonData = $data['configuration'];
        }

        $relatedAttribute = $this->getConfigurationValue('related_attribute');

        if ($relatedAttribute && !isset($jsonData['related_attribute'])) {
            $jsonData['related_attribute'] = $relatedAttribute;
        }

        $this->setConfiguration($jsonData);

        $systemProperties = ['code', 'name', 'position', 'is_active', 'is_sort'];

        foreach ($systemProperties as $property) {
            if (isset($data[$property])) {
                $this->setDataUsingMethod($property, $data[$property]);
            }
        }

        return $this;
    }

    /**
     * Init general validation rules
     *
     * @return $this
     */
    protected function _initValidation()
    {
        $this->_addEmptyValueValidation('code', $this->__('Code'), self::VALIDATE_LIGHT);
        $this->_addEmptyValueValidation('type', $this->__('Type'), self::VALIDATE_LIGHT);
        $this->_addOptionValidation('type', $this->__('Type'), 'ecomdev_sphinx/source_field_type', self::VALIDATE_LIGHT);
        $this->_addValueValidation(
            'configuration',
            $this->__('Configuration should be a valid JSON'),
            function ($value) {
                return is_array($value);
            },
            self::VALIDATE_FULL
        );
        return $this;
    }

    /**
     * Returns available sort options
     *
     * @return EcomDev_Sphinx_Model_Attribute[]
     */
    public function getAvailableAttributes()
    {
        if (!$this->hasData('available_attributes')) {
            $attributes = Mage::getSingleton('ecomdev_sphinx/config')->getActiveAttributes();
            $this->setData('available_attributes', $attributes);
        }

        return $this->_getData('available_attributes');
    }

    /**
     * Returns a configuration value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigurationValue($path)
    {
        return $this->getData('configuration/' . $path);
    }

    /**
     * Returns related attribute
     *
     * @return EcomDev_Sphinx_Model_Attribute|bool
     */
    public function getRelatedAttribute()
    {
        if ($this->hasData('related_attribute')) {
            return $this->_getData('related_attribute');
        }

        if ($code = $this->getConfigurationValue('related_attribute')) {
            $attributes = $this->getAvailableAttributes();
            if (isset($attributes[$code])) {
                $this->setData('related_attribute', $attributes[$code]);
                return $attributes[$code];
            }
        }

        return false;
    }

    /**
     * Returns available options
     *
     * @return bool|string[]
     */
    public function getAvailableOptions()
    {
        $attribute = $this->getRelatedAttribute();

        if ($attribute && $attribute->isOption()) {
            return $this->getResource()->getAvailableOptions($attribute->getId());
        }

        return false;
    }

    /**
     * Returns translated store label
     *
     * @return string
     */
    public function getStoreLabel()
    {
        $storeCode = Mage::app()->getStore()->getCode();
        if ($label = $this->getConfigurationValue('store_name/' . $storeCode)) {
            return $label;
        }

        return $this->getName();
    }
}
