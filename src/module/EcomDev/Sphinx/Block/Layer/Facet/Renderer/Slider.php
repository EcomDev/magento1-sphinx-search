<?php

use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;

/**
 * Renderer option interface
 *
 */
class EcomDev_Sphinx_Block_Layer_Facet_Renderer_Slider
    extends EcomDev_Sphinx_Block_Layer_Facet_AbstractRenderer
{
    /**
     * Maximum available value for a slider
     *
     * @return float|int
     */
    public function getMaximumValue()
    {
        return $this->getLimitPairValue()[1];
    }

    /**
     * Minimum available value for a slider
     *
     * @return float|int
     */
    public function getMinimumValue()
    {
        return $this->getLimitPairValue()[0];
    }

    /**
     * Returns a current start value
     *
     * @return float|int
     */
    public function getCurrentStartValue()
    {
        return $this->getCurrentPairValue()[0];
    }

    /**
     * Returns a current end value
     *
     * @return float|int
     */
    public function getCurrentEndValue()
    {
        return $this->getCurrentPairValue()[1];
    }

    /**
     * Returns a pair of min and max values
     *
     * @return int[]|float[]
     */
    private function getLimitPairValue()
    {
        $option = current($this->getFacet()->getOptions());

        $pair = array(0, 0);
        if ($option && $option->getValue() === 'limit') {
            $pair = $option->getLabel();
        }

        return $pair;
    }

    /**
     * Returns a value url
     *
     * @return string
     */
    public function getValueUrl()
    {
        $baseUrl = $this->getLayer()->getCurrentUrl();
        $activeFilters = $this->getLayer()->getActiveFilters()
            + $this->getLayer()->getAdditionalQuery();

        $activeFilters[$this->getFacet()->getFilterField()] = '^';
        $url = $baseUrl . ($activeFilters ? '?' . http_build_query($activeFilters, '', '&amp;') : '');
        return str_replace('=%5E', '={start}%2B{end}', $url);
    }

    /**
     * Returns currently selected pair value
     *
     * @return float[]|int[]
     */
    private function getCurrentPairValue()
    {
        $currentValue = $this->getFacet()->getFilterValue();

        $pair = array();

        if (strpos($currentValue, '+') !== false) {
            $explodedValue = explode('+', $currentValue, 2);
            if ((float)$explodedValue[0] > 0.001
                && (float)$explodedValue[1] > 0.001) {
                $pair = array((float)$explodedValue[0], (float)$explodedValue[1]);
            }
        }

        if (!$pair) {
            $pair = $this->getLimitPairValue();
        }

        return $pair;
    }
}
