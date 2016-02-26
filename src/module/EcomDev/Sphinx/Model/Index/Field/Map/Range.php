<?php

class EcomDev_Sphinx_Model_Index_Field_Map_Range
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
        if ($options !== false
            && $options->getOption($optionId, $this->source) === false) {
            return false;
        }

        if ($optionId === false) {
            return false;
        }


        if ($options === false) {
            $optionId = floatval($optionId);
        } else {
            $optionId = floatval($options->getOption($optionId, $this->source));
        }


        foreach ($this->mapping['sorted_ranges'] as $rangeId => $limits) {
            if (isset($limits['from']) && $optionId < $limits['from']) {
                break;
            }

            if (isset($limits['to']) && $optionId > $limits['to']) {
                continue;
            }

            return $rangeId;
        }

        return false;
    }
}
