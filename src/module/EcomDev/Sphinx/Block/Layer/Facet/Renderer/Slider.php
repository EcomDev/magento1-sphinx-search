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
    public function getValueUrl($separator = '&amp;')
    {
        $baseUrl = $this->getLayer()->getCurrentUrl();
        $activeFilters = $this->getLayer()->getActiveFilters()
            + $this->getLayer()->getAdditionalQuery();

        $activeFilters[$this->getFacet()->getFilterField()] = '^';
        $url = $baseUrl . ($activeFilters ? '?' . http_build_query($activeFilters, '', $separator) : '');
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
            if ($explodedValue[0] !== '' && is_numeric($explodedValue[0])
                && $explodedValue[1] !== '' && is_numeric($explodedValue[1])
                && (float)$explodedValue[0] < (float)$explodedValue[1]) {
                $pair = array((float)$explodedValue[0], (float)$explodedValue[1]);
            }
        }

        if (!$pair) {
            $pair = $this->getLimitPairValue();
        }

        return $pair;
    }

    /**
     * Returns json options for sliders
     *
     * @return string
     */
    public function getOptionJson()
    {
        $facet = $this->getFacet();

        $currentValue = [
            'min' => floor($this->getMinimumValue()),
            'max' => ceil($this->getMaximumValue())
        ];

        if ($this->isSelected($this->getFacet())) {
            $currentValue['min'] = floor($this->getCurrentStartValue());
            $currentValue['max'] = ceil($this->getCurrentEndValue());
        }

        $options = [
            'url' => $this->getValueUrl('&'),
            'currency' => false,
            'available' => [
                'min' => floor($this->getMinimumValue()),
                'max' => ceil($this->getMaximumValue())
            ],
            'step' => 1,
            'current' => $currentValue
        ];

        if ($facet instanceof EcomDev_Sphinx_Model_Sphinx_Facet_Attribute_Price) {
            $options['currency'] = $facet->getCurrencyFormat();
            $zeroPriceFormatted = $facet->getCurrency()->formatTxt(
                0.00, ['display' => Zend_Currency::NO_SYMBOL]
            );
            // Terrible hack, that I am not proud of, but it does a thing
            $options['decimal_separator'] = trim(strtr($zeroPriceFormatted, ['0' => '']));
        }

        return json_encode($options);
    }
}
