<?php

use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_ConditionInterface as ConditionInterface;

abstract class EcomDev_Sphinx_Model_Sphinx_AbstractFacet
    implements EcomDev_Sphinx_Model_Sphinx_FacetInterface, Serializable
{
    /**
     * Column name for a filter
     * 
     * @var string
     */
    protected $_columnName;

    /**
     * Filter label
     * 
     * @var string
     */
    protected $_label;

    /**
     * Filter field for a request
     * 
     * @var string
     */
    protected $_filterField;

    /**
     * Current filter condition
     * 
     * @var ConditionInterface|false
     */
    protected $_filterCondition;
    
    /**
     * Sphinx response
     * 
     * @var array
     */
    protected $_sphinxResponse;
    
    /**
     * Available options
     * 
     * @var OptionInterface[]
     */
    protected $_options;
    
    /**
     * Returns selected value
     * 
     * @var string|string[]
     */
    protected $_value;

    /**
     * Flag for self filterable check
     * 
     * @var bool
     */
    protected $_isSelfFilterable = true;

    /**
     * Render type for a facet,
     *
     * Default is option render type
     *
     * @var string
     */
    protected $_renderType = self::RENDER_TYPE_OPTION;

    /**
     * Position of the facet
     *
     * @var int
     */
    protected $_position = 0;

    /**
     * Configures basic facet data
     * 
     * @param string $columnName
     * @param string $filterField
     * @param string $label
     * @param int $position
     */
    public function __construct($columnName, $filterField, $label, $position = 0)
    {
        $this->_columnName = $columnName;
        $this->_filterField = $filterField;
        $this->_label = $label;
        $this->_position = $position;
    }
    
    /**
     * Returns column name for a filter
     *
     * @return string
     */
    public function getColumnName()
    {
        return $this->_columnName;
    }

    /**
     * This string returns a filter name used to match
     *
     * @return string
     */
    public function getFilterField()
    {
        return $this->_filterField;
    }

    /**
     * This method returns a filter label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns position of facet
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->_position;
    }
    
    /**
     * This is used to notify facet about sphinx result set
     *
     * @param array $data
     * @return mixed
     */
    public function setSphinxResponse(array $data)
    {
        $this->_sphinxResponse = $this->_processSphinxResponse($data);
        return $this;
    }

    /**
     * Should process sphinx response for current facet
     * 
     * @param array $data
     * @return array
     */
    protected function _processSphinxResponse(array $data)
    {
        if (empty($data)) {
            $data = null;
        }
        return $data;
    }

    /**
     * Returns true if current option is active in facet
     *
     * @param OptionInterface $option
     * @return bool
     */
    public function isOptionActive(OptionInterface $option)
    {
        if ($this->_value === null) {
            return false;
        }
        
        if (is_array($this->_value)) {
            return in_array($option->getValue(), $this->_value);
        }
        
        return $this->_value == $option->getValue();
    }

    /**
     * Returns a filter value for url
     *
     * @return string
     */
    public function getFilterValue(OptionInterface $option = null)
    {
        if ($option === null) {
            if (is_array($this->_value)) {
                return implode(',', $this->_value);
            } elseif ($this->_value !== null) {
                return $this->_value;
            }
            
            return false;
        }
        
        if ($this->_value === null) {
            return (string)$option->getValue();
        }
        
        $isActive = $this->isOptionActive($option);
        $value = $this->_value;
        
        if (is_array($value)) {
            if ($isActive) {
                $index = array_search($option->getValue(), $value);
                array_splice($value, $index, 1);
            } else {
                $value[] = $option->getValue();
            }
            
            return implode(',', $value);
        }
        
        if ($isActive) {
            return false;
        }
        
        return (string)$option->getValue();
    }


    /**
     * Returns filter value object
     *
     * @return ConditionInterface|bool
     */
    public function getFilterCondition()
    {
        if ($this->_filterCondition === null) {
            try {
                $this->_initFilterCondition();
            } catch (RuntimeException $e) {
                $this->_filterCondition = false;
                $this->_value = null;
            }
        }
        
        return $this->_filterCondition;
    }

    /**
     * Initializes filter condition
     * 
     * @return $this
     */
    abstract protected function _initFilterCondition(); 

    /**
     * Apply filter parameter from request
     *
     * @param string|string[] $value
     * @return $this
     */
    public function apply($value)
    {
        $this->_value = $this->_processValue($value);
        return $this;
    }

    /**
     * Processes filter value
     * 
     * @param string $value
     * @return string[]|int[]|int|string
     */
    abstract protected function _processValue($value);

    /**
     * Returns available options
     *
     * @return OptionInterface[]
     */
    public function getOptions()
    {
        if ($this->_options === null) {
            $this->_options = $this->_createOptions();
        }
        
        return $this->_options;
    }

    /**
     * Should create list of options based on sphinx response
     *
     * @return OptionInterface[]
     */
    protected function _createOptions()
    {
        if ($this->_sphinxResponse === null) {
            return array();
        }

        $result = array();
        $optionClass = $this->_getOptionClass();
        $totalItems = count($this->_sphinxResponse);
        foreach ($this->_sphinxResponse as $index => $row) {
            $row['label'] = $this->_prepareOptionLabel($row);
            /* @var $option OptionInterface */
            $option = new $optionClass($this, $row);
            $result[$option->getValue()] = $option;
        }

        return $result;
    }

    /**
     * Should return back a label value for facet
     *
     * @param string[] $row
     * @return string
     */
    protected function _prepareOptionLabel($row)
    {
        return $row['label'];
    }

    /**
     * Returns true if facet is visible
     *
     * @return bool
     */
    public function isVisible()
    {   
        return $this->_sphinxResponse !== null;
    }

    /**
     * Returns an option class
     * 
     * @return string
     */
    protected function _getOptionClass()
    {
        return Mage::getConfig()
            ->getModelClassName('ecomdev_sphinx/sphinx_facet_filter_option');
    }

    /**
     * Returns index names from which data should be retrieved
     * 
     * @return array
     */
    protected function _getIndexNames()
    {
        return $this->getContainer()->getIndexNames('product');
    }

    /**
     * Indicates if facet is self filterable
     *
     * @return bool
     */
    public function isSelfFilterable()
    {
        return $this->_isSelfFilterable;
    }

    /**
     * Sphinx data container
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_Container
     */
    public function getContainer()
    {
        return Mage::getSingleton('ecomdev_sphinx/sphinx_container');
    }

    /**
     * Returns true if this facet is available for search in current index
     * 
     * @return bool
     */
    public function isAvailable()
    {
        return in_array(
            $this->getColumnName(), 
            $this->getContainer()->getIndexColumns('product')
        );
    }

    /**
     * Returns a renderer type for a facet
     *
     * @return string
     */
    public function getRenderType()
    {
        return $this->_renderType;
    }


    protected function _serializableData()
    {
        return array(
            '_columnName' => $this->_columnName,
            '_filterField' => $this->_filterField,
            '_label' => $this->_label,
            '_isSelfFilterable' => $this->_isSelfFilterable,
            '_renderType' => $this->_renderType,
            '_position' => $this->_position
        );
    }

    /**
     * Processes serialized data
     *
     * @param array $data
     * @return $this
     */
    protected function _processSerializedData(array $data)
    {
        foreach ($data as $property => $value) {
            $this->{$property} = $value;
        }
        
        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->_serializableData());
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->_processSerializedData(unserialize($serialized));
    }

}
