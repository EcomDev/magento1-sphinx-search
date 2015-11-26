<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

abstract class EcomDev_Sphinx_Model_Index_Field_Map_AbstractField
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Source attributes
     *
     * @var string[]
     */
    protected $source;

    /**
     * Mapping of the alias values
     *
     * @var string[]
     */
    protected $mapping;

    /**
     * Flag for allowing only option based functionality
     *
     * @var bool
     */
    protected $onlyOptions = false;

    public function __construct($name, $source, $mapping)
    {
        parent::__construct(self::TYPE_ATTRIBUTE_MULTI, $name);
        $this->source = $source;
        $this->mapping = $mapping;
    }

    /**
     * Returns a mapped row value
     *
     * @param EcomDev_Sphinx_Contract_DataRowInterface $row
     * @param EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
     * @return array|mixed|string
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        if ($this->source === 'price') {
            $index = $row->getValue('price_index', false);
            if (isset($index['0']['minimal_price'])) {
                $value = $index['0']['minimal_price'];
            } else {
                $value = null;
            }
        } else {
            $value = $row->getValue($this->source, false);
        }



        /** @var EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav_Option_Hash $options */
        $options = $row->getValue('_' . $this->source . '_label', false);

        if ($this->onlyOptions && $options === false) {
            return '';
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $optionId) {
                $mappedValue = $this->getMappedValue($optionId, $options);
                if ($mappedValue !== false) {
                    $result[$mappedValue] = $mappedValue;
                }
            }

            return $result;
        }

        $value = $this->getMappedValue($value, $options);

        if ($value === false) {
            return '';
        }

        return $value;
    }

    /**
     * Returns a mapped value for option
     *
     * @param string $optionId
     * @param EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav_Option_Hash $options
     * @return string
     */
    abstract protected function getMappedValue($optionId, $options);
}
