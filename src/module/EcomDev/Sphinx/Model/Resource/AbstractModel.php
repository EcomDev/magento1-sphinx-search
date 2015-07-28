<?php

abstract class EcomDev_Sphinx_Model_Resource_AbstractModel
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Serialize specified field in an object
     *
     * @param Varien_Object $object
     * @param string $field
     * @param mixed $defaultValue
     * @param bool $unsetEmpty
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _serializeField(Varien_Object $object, $field, $defaultValue = null, $unsetEmpty = false)
    {
        $value = $object->getData($field);
        if (empty($value)) {
            if ($unsetEmpty) {
                $object->unsetData($field);
            } else {
                if (is_object($defaultValue) || is_array($defaultValue)) {
                    $defaultValue = json_encode($defaultValue);
                }
                $object->setData($field, $defaultValue);
            }
        } elseif (is_array($value) || is_object($value)) {
            $object->setData($field, json_encode($value));
        }

        return $this;
    }

    /**
     * Unserialize Varien_Object field in an object
     *
     * @param Mage_Core_Model_Abstract $object
     * @param string $field
     * @param mixed $defaultValue
     */
    protected function _unserializeField(Varien_Object $object, $field, $defaultValue = null)
    {
        $value = $object->getData($field);
        if (empty($value)) {
            $object->setData($field, $defaultValue);
        } elseif (!is_array($value) && !is_object($value)) {
            $object->setData($field, json_decode($value, true));
        }
    }
}
