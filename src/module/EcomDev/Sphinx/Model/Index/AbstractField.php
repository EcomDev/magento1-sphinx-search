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
     * Sets field properties
     *
     * @param string $type
     * @param string $name
     */
    public function __construct($type, $name)
    {
        $this->type = $type;
        $this->name = $name;
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
        return in_array(
            $this->getType(),
            [self::TYPE_ATTRIBUTE_INT, self::TYPE_ATTRIBUTE_BIGINT],
            true
        );
    }

    /**
     * Is it multiple?
     *
     * @return bool
     */
    public function isMultiple()
    {
        return in_array(
            $this->getType(),
            [self::TYPE_ATTRIBUTE_MULTI, self::TYPE_ATTRIBUTE_MULTI64],
            true
        );
    }

    /**
     * Is it a text field
     *
     * @return bool
     */
    public function isText()
    {
        return in_array(
            $this->getType(),
            [self::TYPE_FIELD, self::TYPE_FIELD_STRING, self::TYPE_ATTRIBUTE_STRING],
            true
        );
    }
}
