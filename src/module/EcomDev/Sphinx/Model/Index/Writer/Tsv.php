<?php

use EcomDev_Sphinx_Contract_ReaderInterface as ReaderInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * TSV Writer
 *
 */
class EcomDev_Sphinx_Model_Index_Writer_Tsv
    extends EcomDev_Sphinx_Model_Index_Writer_Csv
{
    /**
     * Delimiter character of csv
     *
     * @var string
     */
    protected $delimiter = "\t";

    /**
     * @var string
     */
    protected $escape = ' ';

    protected $enclosure = ' ';

    /**
     * Returns a value
     *
     * @param string $value
     * @return string
     */
    protected function _translateValue($value)
    {
        // We cannot use special characters like multi line in tsv format
        return strtr(parent::_translateValue($value), [
            "\t" => ' ',
            "\n" => ' ',
            "\r" => ''
        ]);
    }
}
