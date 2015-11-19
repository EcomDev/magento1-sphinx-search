<?php

use EcomDev_Sphinx_Contract_ReaderInterface as ReaderInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Writer_Csv
    extends EcomDev_Sphinx_Model_Index_AbstractWriter
    implements EcomDev_Sphinx_Contract_Writer_HeaderAwareInterface
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
     * Output headers flag
     *
     * @var bool
     */
    protected $outputHeaders = false;

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

        $columnCalls = [
            '<?php $lambda = function ($dataRow, $scope, $columns) {',
            '$row = [$dataRow->getId()];'
        ];

        foreach ($columns as $index => $column) {
            $columnCalls[] = '$value = $columns[' . var_export($index, true) . ']->getValue($dataRow, $scope);';
            if ($column->isMultiple()) {
                $columnCalls[] = 'if (is_array($value)) {';
                $columnCalls[] = '  $value = implode(\',\', $value);';
                $columnCalls[] = '}';
            } elseif (method_exists($this, '_translateValue')) {
                $columnCalls[] = '$value = $this->_translateValue($value);';
            }

            $columnCalls[] = '$row[] = $value;';
        }


        $columnCalls[] = 'return $row;';
        $columnCalls[] = '}; return $lambda;';

        $tmpDirectory = Mage::getConfig()->getVarDir('ecomdev_sphinx/lambda');

        $lambdaName = uniqid('lambda') . '.php';

        file_put_contents($tmpDirectory . DS . $lambdaName, implode("\n", $columnCalls));
        $rowLambda = (include $tmpDirectory . DS . $lambdaName);
        unlink($tmpDirectory . DS . $lambdaName);


        if ($this->outputHeaders) {
            $header = array_keys($columns);
            array_unshift($header, 'id');
            $writer->insertOne($header);
        }

        /** @var EcomDev_Sphinx_Contract_DataRowInterface $dataRow */
        foreach ($reader as $dataRow) {
            $row = $rowLambda($dataRow, $scope, $columns);
            $writer->insertOne($row);
        }

        if (!isset($row)) {
            // If no rows are added, we need to add an empty one to create index
            $writer->insertOne([0 => 0] + array_fill(1, count($columns), ''));
        }

        return $this;
    }

    /**
     * Flag for output headers for exported file
     *
     * @param bool $flag
     * @return $this
     */
    public function setOutputHeaders($flag)
    {
        $this->outputHeaders = (bool)$flag;
        return $this;
    }
}
