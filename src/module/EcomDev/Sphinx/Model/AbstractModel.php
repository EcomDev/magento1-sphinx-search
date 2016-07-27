<?php

abstract class EcomDev_Sphinx_Model_AbstractModel
    extends Mage_Core_Model_Abstract
{
    const VALIDATE_FULL = 'full';
    const VALIDATE_LIGHT = 'light';

    protected $_imageFields = array();

    protected $_dataSetErrors = array();

    protected $_fieldValidation = array();

    /**
     * Specify this property to invoke indexer processors on modifying data
     * 
     * @var string|null
     */
    protected $_indexerEntity;

    protected $_validationMode = self::VALIDATE_FULL;

    /**
     * Sets data into model from post array
     *
     * @param array $data
     */
    abstract protected function _setDataFromArray(array $data);

    /**
     * @param array $data
     */
    public function setDataFromArray(array $data)
    {
        if ($this->_eventObject && $this->_eventPrefix) {
            $proxy = (object)['data' => $data];
            Mage::dispatchEvent(
                $this->_eventPrefix . '_set_data_from_array_before',
                ['proxy' => $proxy, 'data' => $data] + $this->_getEventData()
            );
            $data = $proxy->data;
        }

        $this->_setDataFromArray($data);

        if ($this->_eventObject && $this->_eventPrefix) {
            Mage::dispatchEvent(
                $this->_eventPrefix . '_set_data_from_array_after',
                ['data' => $data] + $this->_getEventData()
            );
        }

        return $this;
    }

    /**
     * Returns list of errors that happened during setDataFromArray
     *
     * @return string[]
     */
    public function getDataSetErrors()
    {
        return $this->_dataSetErrors;
    }

    /**
     * Returns a translated string
     * 
     * @param string $string
     * @return string
     */
    public function __($string)
    {
        $args = func_get_args();
        return call_user_func_array(
            array(Mage::helper('ecomdev_sphinx'), '__'),
            $args
        );
    }
    
    abstract protected function _initValidation();
    
    /**
     * Validates data in the model according to the defined rules
     *
     * @return string[]|bool
     */
    public function validate()
    {
        if (!$this->_fieldValidation) {
            $this->_initValidation();
        }
        
        $errors = array();
        
        foreach ($this->_fieldValidation as $field => $validations) {
            $value = $this->getDataUsingMethod($field);
            foreach ($validations as $validator) {
                if ($validator['mode'] == self::VALIDATE_FULL && $this->_validationMode === self::VALIDATE_LIGHT) {
                    continue;
                }
                
                if (!$validator['callback']($value)) {
                    $errors[] = $validator['message'];
                }
            }
        }


        if ($this->_eventPrefix && $this->_eventObject) {
            $proxy = (object)['result' => true, 'errors' => array()];
            Mage::dispatchEvent($this->_eventPrefix . '_validate', ['proxy' => $proxy] + $this->_getEventData());
            if ($proxy->result === false) {
                $errors += $proxy->errors;
            }
        }


        return !empty($errors) ? $errors : true;
    }

    /**
     * Set a validation mode for model,
     * if it different than default
     *
     * @param string $validationMode
     * @return $this
     */
    public function setValidationMode($validationMode)
    {
        $this->_validationMode = $validationMode;
        return $this;
    }

    /**
     * @param string $field
     * @param string $label
     * @param string $mode
     * @param bool $allowNull
     * @return $this
     */
    protected function _addEmptyValueValidation($field, $label, $mode = self::VALIDATE_FULL, $allowNull = false)
    {
        $this->_addValueValidation(
            $field, 
            $this->__('Field "%s" requires a value', $label), 
            function ($value) use ($allowNull) { 
                return ($allowNull && $value === null) || trim((string)$value) !== ''; 
            }, 
            $mode
        );
        
        return $this;
    }

    /**
     * @param string $field
     * @param string $message
     * @param callable $callback
     * @param string $mode
     * @return $this
     */
    protected function _addValueValidation($field, $message, Closure $callback, $mode = self::VALIDATE_FULL)
    {
        $this->_fieldValidation[$field][] = array(
            'message' => $message,
            'callback' => $callback,
            'mode' => $mode
        );
        
        return $this;
    }

    protected function _addOptionValidation($field, $message, $optionModel, $mode = self::VALIDATE_FULL)
    {
        return $this->_addValueValidation(
            $field, 
            $message, 
            function ($value) use ($optionModel) {
                if ($value === null) {
                    return true;
                }
                
                if (strpos($optionModel, 'ecomdev_sphinx/') === 0) {
                    $object = Mage::getSingleton($optionModel);
                } else {
                    $object = Mage::getModel('ecomdev_sphinx/source_default')
                        ->setSourceModel($optionModel);
                }
                $options = $object->getOptions();
                return isset($options[$value]);             
            }, 
            $mode
        );
    }

    /**
     * Imports data array
     * 
     * @param array $data
     * @param array $keys
     * @return $this
     */
    public function importData(array $data, array $keys)
    {
        foreach ($keys as $field) {
            if (array_key_exists($field, $data)) {
                $this->setDataUsingMethod($field, $data[$field]);
            }
        }
        
        return $this;
    }

    public function afterCommitCallback()
    {
        if ($this->_indexerEntity) {
            Mage::getSingleton('index/indexer')->processEntityAction(
                $this,
                $this->_indexerEntity,
                Mage_Index_Model_Event::TYPE_SAVE
            );
        }

        return parent::afterCommitCallback();
    }

    protected function _afterDeleteCommit()
    {
        if ($this->_indexerEntity) {
            Mage::getSingleton('index/indexer')->processEntityAction(
                $this,
                $this->_indexerEntity,
                Mage_Index_Model_Event::TYPE_DELETE
            );
        }

        return parent::_afterDeleteCommit();
    }
}
