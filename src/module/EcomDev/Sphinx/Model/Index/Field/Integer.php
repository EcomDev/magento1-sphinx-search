<?php

class EcomDev_Sphinx_Model_Index_Field_Integer
    extends EcomDev_Sphinx_Model_Index_Field
    implements EcomDev_Sphinx_Contract_Field_LengthAwareInterface
{
    /**
     * Length of the field integer
     *
     * @var int
     */
    private $length;

    /**
     * Creates an integer attribute
     * @param string $name
     * @param int $length
     * @param string|null $default
     */
    public function __construct($name, $length, $default = null)
    {
        parent::__construct(self::TYPE_ATTRIBUTE_INT, $name, $default);
        $this->length = $length;
    }

    /**
     * Returns length of the field
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }
}
