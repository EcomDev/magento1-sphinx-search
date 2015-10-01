<?php

class EcomDev_Sphinx_Model_Resource_Index_Reader_Filter
    implements EcomDev_Sphinx_Contract_Reader_FilterInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var mixed[]|mixed
     */
    private $value;

    /**
     * Creates a filter instance
     *
     * @param string $field
     * @param mixed|mixed[] $value
     */
    public function __construct($field, $value)
    {
        $this->value = $value;
        $this->field = $field;
    }


    /**
     * Returns filter field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Returns value for which this filter is applied
     *
     * @return mixed|mixed[]
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Applies filter value to select
     *
     * @param string $tableAlias
     * @param Varien_Db_Select $select
     * @return $this
     */
    public function render($tableAlias, Varien_Db_Select $select)
    {
        $field = $select->getAdapter()->quoteIdentifier(
            $tableAlias . '.' . $this->field
        );

        if (is_array($this->value)) {
            $select->where($field . ' IN(?)', $this->value);
        } else {
            $select->where($field . ' = ?', $this->value);
        }

        return $this;
    }

}
