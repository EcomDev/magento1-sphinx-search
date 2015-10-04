<?php

use EcomDev_Sphinx_Contract_ReaderInterface as ReaderInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Writer_Csv
    extends EcomDev_Sphinx_Model_Index_AbstractWriter
{
    /**
     * Delimiter character of csv
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * Enclosure character of csv
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * Escape character of csv
     *
     * @var string
     */
    protected $escape = "\\";

    /**
     * Instance of writer
     *
     * @return League\Csv\Writer
     */
    protected function getCsvWriter()
    {
        $csvWriter = League\Csv\Writer::createFromPath($this->getPath(), 'w');
        $csvWriter->setDelimiter($this->delimiter);
        $csvWriter->setEscape($this->escape);
        $csvWriter->setEnclosure($this->enclosure);
        return $csvWriter;
    }

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
        $writer = $this->getCsvWriter();

        /** @var EcomDev_Sphinx_Contract_DataRowInterface $dataRow */
        foreach ($reader as $dataRow) {
            $row = [$dataRow->getId()];
            foreach ($columns as $column) {
                $value = $column->getValue($dataRow, $scope);

                if ($column->isMultiple() && is_array($value)) {
                    $value = implode(',', $value);
                }

                $row[] = $this->_translateValue($value);
            }

            $writer->insertOne($row);
        }

        if (!isset($row)) {
            // If no rows are added, we need to add an empty one to create index
            $writer->insertOne(array_fill(0, count($columns) + 1, ''));
        }

        return $this;
    }

    /**
     * Returns a value
     *
     * @param string $value
     * @return string
     */
    protected function _translateValue($value)
    {
        return $value;
    }

}
