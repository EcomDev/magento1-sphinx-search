<?php


class EcomDev_Sphinx_Model_Index_Field_Map_Alias
    extends EcomDev_Sphinx_Model_Index_Field_Map_AbstractField
{
    /**
     * Returns a mapped value for option
     *
     * @param string $optionId
     * @param EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav_Option_Hash $options
     * @return string
     */
    protected function getMappedValue($optionId, $options)
    {
        if ($options !== false && $options->getOption($optionId, $this->source) === false) {
            return false;
        }

        if (isset($this->mapping['option_alias'][$optionId])) {
            return $this->mapping['option_alias'][$optionId];
        }

        return $optionId;
    }


}
