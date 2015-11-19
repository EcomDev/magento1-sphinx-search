<?php
use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Field_Json
    extends EcomDev_Sphinx_Model_Index_AbstractField
{

    const JSON_ARRAY = 'array';
    const JSON_OBJECT = 'object';

    /**
     * Type of json data
     *
     * @var string
     */
    private $jsonType;


    /**
     * Source of data
     *
     * @var string
     */
    private $source;

    public function __construct($name, $source = null, $jsonType = self::JSON_OBJECT)
    {
        $this->jsonType = $jsonType;

        if ($source === null) {
            $source = $name;
        }

        $this->source = $source;

        parent::__construct(self::TYPE_ATTRIBUTE_JSON, $name);
    }

    /**
     * Returns JSON row value data
     *
     * @param EcomDev_Sphinx_Contract_DataRowInterface $row
     * @param EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
     * @return string
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        $value = $row->getValue($this->source, []);

        if ($this->jsonType === self::JSON_ARRAY && $value && key($value) !== 0) {
            $value = array_values($value);
        }

        $options = $this->jsonType === self::JSON_OBJECT ? JSON_FORCE_OBJECT : 0;

        return json_encode($value, $options);
    }


}
