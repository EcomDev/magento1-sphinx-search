<?php

class EcomDev_Sphinx_Model_Index_Data_Row_Factory
    implements EcomDev_Sphinx_Contract_DataRowFactoryInterface
{
    /**
     * Return a new data row instance
     *
     * @param array $main
     * @param array $additional
     * @return \EcomDev_Sphinx_Contract_DataRowInterface
     */
    public function createDataRow(array $main, array $additional)
    {
        return new EcomDev_Sphinx_Model_Index_Data_Row(
            $main, $additional
        );
    }
}
