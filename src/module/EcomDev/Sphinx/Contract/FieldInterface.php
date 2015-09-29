<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;

/**
 * Field that should be used to retrieve values for indexer
 *
 *
 */
interface EcomDev_Sphinx_Contract_FieldInterface
{
    /**
     * Regular field for full text search
     *
     * @var string
     */
    const TYPE_FIELD = 'field';

    /**
     * Regular field string
     *
     * @var string
     */
    const TYPE_FIELD_STRING = 'field_string';

    /**
     * Integer attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_INT = 'attr_int';

    /**
     * Long integer type
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_BIGINT = 'attr_bigint';

    /**
     * Multiple value attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_MULTI = 'attr_multi';

    /**
     * Extended multiple value attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_MULTI64 = 'attr_multi64';

    /**
     * Regular text attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_STRING = 'attr_string';

    /**
     * JSON type of attribute
     *
     * @return string
     */
    const TYPE_ATTRIBUTE_JSON = 'attr_json';

    /**
     * Returns type of the field that should be used by Sphinx
     *
     * @return string
     */
    public function getType();

    /**
     * Returns name of the field
     *
     * @return string
     */
    public function getName();

    /**
     * Returns data for sphinx
     *
     * @param DataRowInterface $row
     * @return string
     */
    public function getValue(DataRowInterface $row);
}
