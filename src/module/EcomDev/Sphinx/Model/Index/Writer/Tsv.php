<?php

use EcomDev_Sphinx_Contract_ReaderInterface as ReaderInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Writer_Tsv
    extends EcomDev_Sphinx_Model_Index_AbstractWriter
{
    /**
     * Processes reader within specified scope
     *
     * @param ReaderInterface $reader
     * @param ScopeInterface $scope
     * @return $this
     */
    public function process(ReaderInterface $reader, ScopeInterface $scope)
    {
        $columns = $scope->getConfiguration()->getFields();
        $reader->setScope($scope);
        /** @var EcomDev_Sphinx_Contract_DataRowInterface $dataRow */
        foreach ($reader as $dataRow) {
            $row = [$dataRow->getId()];
            foreach ($columns as $column) {
                $value = $column->getValue($dataRow, $scope);

                if ($column->isMultiple() && is_array($value)) {
                    $value = implode(',', $value);
                }

                $row[] = strtr($value, "\t", "    ");
            }
            fputcsv($this->getStream(), $row, "\t");
        }
    }

}
