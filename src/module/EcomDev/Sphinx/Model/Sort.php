<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;

/**
 * @method EcomDev_Sphinx_Model_Resource_Sort_Collection getCollection()
 * @method EcomDev_Sphinx_Model_Resource_Sort _getResource()
 * @method EcomDev_Sphinx_Model_Resource_Sort getResource()
 * @method $this setConfiguration(array $configuration)
 * @method array getConfiguration()
 */
class EcomDev_Sphinx_Model_Sort
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
        $this->_init('ecomdev_sphinx/sort');
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

        $this->setConfiguration($jsonData);

        $systemProperties = ['code', 'name', 'position'];

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
        $this->_addValueValidation('configuration', $this->__('Configuration should be a valid JSON'), function ($value) {
            return is_array($value);
        }, self::VALIDATE_FULL);
        return $this;
    }

    /**
     * Returns available sort options
     *
     * @return string[]
     */
    public function getAvailableSortOptions()
    {
        if (!$this->hasData('_available_sort_options')) {
            $options = [
                '@position' => Mage::helper('ecomdev_sphinx')->__('Min Category Position'),
                '@stock_status' => Mage::helper('ecomdev_sphinx')->__('Stock Status'),
                '@relevance' => Mage::helper('ecomdev_sphinx')->__('Search Relevance (Search Only)')
            ];

            $options += $this->_getResource()->getAvailableSortOptions();
            $this->setData('_available_sort_options', $options);
        }

        return $this->_getData('_available_sort_options');
    }

    /**
     * @param string $direction
     * @return array
     */
    public function getSortInfo($direction)
    {
        if ($directions = $this->getConfigurationValue('sort/direction')) {
            if (!in_array($direction, $directions)) {
                $direction = current($directions);
            }
        }

        $options = $this->getConfigurationValue('sort/order');

        usort($options, function ($a, $b) {
            return $a['position'] > $b['position'] ? 1 : ($a['position'] == $b['position'] ? 0 : -1);
        });

        $result = [];

        foreach ($options as $field => $map) {
            $itemDirection = $direction;
            if (!empty($map[$direction])) {
                $itemDirection = $map[$direction];
            }

            $result[$field] = $itemDirection;
        }

        return $result;
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
