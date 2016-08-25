<?php

class EcomDev_Sphinx_Model_Resource_Index_Reader_Select
    extends Varien_Db_Select
{
    private $indexHint = [];

    public function indexHint($correlationName, $indexHint)
    {
        if ($indexHint) {
            $this->indexHint[$correlationName] = $indexHint;
        }
        return $this;
    }

    public function reset($part = null)
    {
        if ($part === self::FROM || $part == null) {
            $this->indexHint = [];
        }

        return parent::reset($part);
    }

    protected function _renderFrom($sql)
    {
        /*
         * If no table specified, use RDBMS-dependent solution
         * for table-less query.  e.g. DUAL in Oracle.
         */
        if (empty($this->_parts[self::FROM])) {
            $this->_parts[self::FROM] = $this->_getDummyTable();
        }

        $from = array();

        foreach ($this->_parts[self::FROM] as $correlationName => $table) {
            $tmp = '';

            $joinType = ($table['joinType'] == self::FROM) ? self::INNER_JOIN : $table['joinType'];

            // Add join clause (if applicable)
            if (! empty($from)) {
                $tmp .= ' ' . strtoupper($joinType) . ' ';
            }

            $tmp .= $this->_getQuotedSchema($table['schema']);
            $tmp .= $this->_getQuotedTable($table['tableName'], $correlationName);

            if (!empty($this->indexHint[$correlationName])) {
                $tmp .= sprintf(
                    ' FORCE INDEX (%s) ',
                    $this->getAdapter()->quoteIdentifier($this->indexHint[$correlationName])
                );
            }

            // Add join conditions (if applicable)
            if (!empty($from) && ! empty($table['joinCondition'])) {
                $tmp .= ' ' . self::SQL_ON . ' ' . $table['joinCondition'];

            }

            // Add the table name and condition add to the list
            $from[] = $tmp;
        }

        // Add the list of all joins
        if (!empty($from)) {
            $sql .= ' ' . self::SQL_FROM . ' ' . implode("\n", $from);
        }

        return $sql;
    }


}
