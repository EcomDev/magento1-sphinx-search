<?php

class EcomDev_Sphinx_Model_Index_Data_Row
    implements EcomDev_Sphinx_Contract_DataRowInterface
{
    /**
     * Main row
     *
     * @var string[][]
     */
    private $main;

    /**
     * Additional data array
     *
     * @var mixed[][]
     */
    private $additional;

    /**
     * Identifiers of the data
     *
     * @var int[]
     */
    private $identifiers;

    /**
     * Current position in identifiers array
     *
     * @var int
     */
    private $currentIndex;

    /**
     * Constructs a new data row instance
     *
     * @param string[][] $main
     * @param mixed[][] $additional
     */
    public function __construct(array $main, array $additional)
    {
        $this->main = $main;
        $this->additional = $additional;
        $this->identifiers = array_keys($main);
        $this->currentIndex = key($this->identifiers);
    }


    /**
     * Sets identifier of a row
     *
     * @return int
     */
    public function getId()
    {
        return $this->identifiers[$this->currentIndex];
    }

    /**
     * Returns value of from supplied sources
     *
     * @param string $field
     * @param null $default
     * @return mixed
     */
    public function getValue($field, $default = null)
    {
        if (isset($this->main[$this->getId()][$field])) {
            return $this->main[$this->getId()][$field];
        } elseif (isset($this->additional[$this->getId()][$field])) {
            return $this->additional[$this->getId()][$field];
        }

        return $default;
    }

    /**
     * Moves cursor to the next identifier
     *
     * @return bool
     */
    public function next()
    {
        if (isset($this->identifiers[$this->currentIndex + 1])) {
            $this->currentIndex += 1;
            return true;
        }

        return false;
    }
}
