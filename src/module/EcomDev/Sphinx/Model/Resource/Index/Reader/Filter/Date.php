<?php

class EcomDev_Sphinx_Model_Resource_Index_Reader_Filter_Date
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Filter
{
    /**
     * Applies filter value to select
     *
     * @param string $tableAlias
     * @param Varien_Db_Select $select
     * @return $this
     */
    public function render($tableAlias, Varien_Db_Select $select)
    {
        list($fromValue, $toValue) = $this->getValue();

        if ($fromValue instanceof DateTime) {
            $fromValue = $fromValue->format('Y-m-d H:i:s');
        }

        if ($toValue instanceof DateTime) {
            $toValue = $toValue->format('Y-m-d H:i:s');
        }

        $field = $tableAlias . '.' . $this->getField();
        if (strpos($tableAlias, '.') !== false) {
            $field = $tableAlias;
        }

        $field = $select->getAdapter()->quoteIdentifier($field);

        if ($toValue) {
            $select->where($field . ' <= ?', $toValue);
        }

        if ($fromValue) {
            $select->where($field . ' >= ?', $fromValue);
        }

        return $this;
    }

}
