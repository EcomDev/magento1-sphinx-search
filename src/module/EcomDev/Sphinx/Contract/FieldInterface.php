<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

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
    const TYPE_ATTRIBUTE_INT = 'attr_uint';

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
     * Regular text attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_FLOAT = 'attr_float';

    /**
     * Timestamp attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_TIMESTAMP = 'attr_timestamp';

    /**
     * JSON type of attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_JSON = 'attr_json';

    /**
     * Boolean attribute
     *
     * @var string
     */
    const TYPE_ATTRIBUTE_BOOL = 'attr_bool';

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
     * @param ScopeInterface $scope
     * @return string[]|string
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope);

    /**
     * @return bool
     */
    public function isText();

    /**
     * @return bool
     */
    public function isMultiple();

    /**
     * @return bool
     */
    public function isInt();
}
