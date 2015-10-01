<?php

/**
 * Field that knows about its data length
 *
 */
interface EcomDev_Sphinx_Contract_Field_LengthAwareInterface
{
    /**
     * Returns length of the field
     *
     * @return int
     */
    public function getLength();
}
