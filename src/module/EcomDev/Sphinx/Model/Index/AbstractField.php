<?php

abstract class EcomDev_Sphinx_Model_Index_AbstractField
    implements EcomDev_Sphinx_Contract_FieldInterface
{
    /**
     * Type of the field
     *
     * @var string
     */
    private $type;

    /**
     * Name of the field
     *
     * @var string
     */
    private $name;

    /**
     * @var bool[]
     */
    private $multipleTypes = [];

    /**
     * @var bool[]
     */
    private $intTypes = [];

    /**
     * @var bool[]
     */
    private $textTypes = [];

    /**
     * Cached scope value
     *
     * @var int
     */
    protected $cachedScopeValue;

    /**
     * @var EcomDev_Sphinx_Contract_Reader_ScopeInterface
     */
    protected $cachedScope;

    /**
     * Sets field properties
     *
     * @param string $type
     * @param string $name
     */
    public function __construct($type, $name)
    {
        $this->type = $type;
        $this->name = $name;
        $this->multipleTypes[self::TYPE_ATTRIBUTE_MULTI] = true;
        $this->multipleTypes[self::TYPE_ATTRIBUTE_MULTI64] = true;
        $this->intTypes[self::TYPE_ATTRIBUTE_INT] = true;
        $this->intTypes[self::TYPE_ATTRIBUTE_BIGINT] = true;
        $this->textTypes[self::TYPE_FIELD] = true;
        $this->textTypes[self::TYPE_FIELD_STRING] = true;
        $this->textTypes[self::TYPE_ATTRIBUTE_STRING] = true;
    }


    /**
     * Returns type of the field that should be used by Sphinx
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns name of the field
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Is it integer?
     *
     * @return bool
     */
    public function isInt()
    {
        return isset($this->intTypes[$this->type]);
    }

    /**
     * Is it multiple?
     *
     * @return bool
     */
    public function isMultiple()
    {
        return isset($this->multipleTypes[$this->type]);
    }

    /**
     * Is it a text field
     *
     * @return bool
     */
    public function isText()
    {
        return isset($this->textTypes[$this->type]);
    }

    /**
     * Is it a text field
     *
     * @return bool
     */
    public function isTimestamp()
    {
        return $this->type === self::TYPE_ATTRIBUTE_TIMESTAMP;
    }
}
