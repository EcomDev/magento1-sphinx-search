<?php

/**
 * Field type provider
 *
 *
 */
class EcomDev_Sphinx_Model_Source_Field_Type
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const TYPE_GROUPED = 'grouped';
    const TYPE_ALIAS = 'alias';
    const TYPE_RANGE = 'range';

    /**
     * Type model registry
     *
     * @var EcomDev_Sphinx_Contract_Field_TypeInterface[]
     */
    protected $_types;

    /**
     * Initializes default options
     *
     * @return $this
     */
    protected function _initOptions()
    {
        $this->_options = [];
        $this->_types = [];

        $this->addTypeOption(
            self::TYPE_GROUPED,
            Mage::helper('ecomdev_sphinx')->__('Grouped'),
            'ecomdev_sphinx/field_type_grouped'
        );

        $this->addTypeOption(
            self::TYPE_RANGE,
            Mage::helper('ecomdev_sphinx')->__('Range'),
            'ecomdev_sphinx/field_type_range'
        );


        $this->addTypeOption(
            self::TYPE_ALIAS,
            Mage::helper('ecomdev_sphinx')->__('Alias'),
            'ecomdev_sphinx/field_type_alias'
        );

        Mage::dispatchEvent('ecomdev_sphinx_source_field_type_init_options', ['type_model' => $this]);
        return $this;
    }

    /**
     * @param string $type
     * @param string $label
     * @param string $typeClass
     * @return $this
     */
    public function addTypeOption($type, $label, $typeClass)
    {
        if ($this->_options === null) {
            $this->_initOptions();
        }

        $typeModel = Mage::getModel($typeClass);
        if (!$typeModel instanceof EcomDev_Sphinx_Contract_Field_TypeInterface) {
            throw new InvalidArgumentException(
                'Type model should implement EcomDev_Sphinx_Contract_Field_TypeInterface'
            );
        }

        $this->_options[$type] = $label;
        $this->_types[$type] = $typeModel;

        return $this;
    }

    /**
     * Returns type instance
     *
     * @param string $type
     * @return EcomDev_Sphinx_Contract_Field_TypeInterface|bool
     */
    public function getType($type)
    {
        if ($this->_types === null) {
            $this->_initOptions();
        }

        if (isset($this->_types[$type])) {
            return $this->_types[$type];
        }

        return false;
    }

}
