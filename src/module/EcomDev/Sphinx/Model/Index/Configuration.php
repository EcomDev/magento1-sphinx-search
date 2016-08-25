<?php

use EcomDev_Sphinx_Contract_FieldProviderInterface as FieldProviderInterface;
use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;

/**
 * Configuration object for reader scope
 *
 */
class EcomDev_Sphinx_Model_Index_Configuration
    implements EcomDev_Sphinx_Contract_ConfigurationInterface
{
    /**
     * Field provider list
     *
     * @var FieldProviderInterface[]
     */
    private $fieldProviders = [];

    /**
     * Fields interface
     *
     * @var FieldInterface[]
     */
    private $fields;

    /**
     * Attribute codes
     *
     * @var string[]
     */
    private $attributeCodes;

    /**
     * Attribute codes grouped by type
     *
     * @var string[][]
     */
    private $attributeCodesByType;

    /**
     * Attribute identifiers grouped by type
     *
     * @var int[][]
     */
    private $attributeIdsByType;

    /**
     * Entity type code for fields attribute code retrieval
     *
     * @var string
     */
    private $entityType;

    /**
     * Entity type related configuration constructor
     *
     * @param string $entityType
     */
    public function __construct($entityType = '')
    {
        $this->entityType = $entityType;
    }


    /**
     * Returns fields from field providers grouped by name
     *
     * @return FieldInterface[]
     */
    public function getFields()
    {
        if ($this->fields === null) {
            $this->initializeFields();
        }

        return $this->fields;
    }

    /**
     * Initializes field values
     *
     * @return $this
     */
    private function initializeFields()
    {
        $this->fields = [];

        foreach ($this->fieldProviders as $provider) {
            $fields = $provider->getFields();
            foreach ($fields as $field) {
                $this->fields[$field->getName()] = $field;
            }
        }

        return $this;
    }

    /**
     * Adds field provider to configuration object
     *
     * @param FieldProviderInterface $provider
     * @return $this
     */
    public function addFieldProvider(FieldProviderInterface $provider)
    {
        $this->fieldProviders[spl_object_hash($provider)] = $provider;
        $this->reset();
        return $this;
    }

    /**
     * Resets cached properties
     *
     * @return $this
     */
    private function reset()
    {
        $this->fields = null;
        $this->attributeCodes = null;
        $this->attributeCodesByType = null;
        return $this;
    }

    /**
     * Returns attribute code that are configured to be used
     *
     * @param string|null filters attribute code by type
     * @return string[]
     */
    public function getAttributeCodes($type = null)
    {
        if ($this->attributeCodesByType === null) {
            $this->initializeAttributes();
        }

        if ($type === null) {
            return $this->attributeCodes;
        } elseif (isset($this->attributeCodesByType[$type])) {
            return $this->attributeCodesByType[$type];
        }

        return [];
    }

    /**
     * Returns attributes grouped by code and type
     *
     * @return string[][]
     */
    public function getAttributeCodesGroupedByType()
    {
        if ($this->attributeCodesByType === null) {
            $this->initializeAttributes();
        }

        return $this->attributeCodesByType;
    }

    /**
     * Returns attributes grouped by code and type
     *
     * @return string[][]
     */
    public function getAttributeIdsGroupedByType()
    {
        if ($this->attributeIdsByType === null) {
            $this->initializeAttributeIds();
        }

        return $this->attributeIdsByType;
    }


    /**
     * Initializes attributes
     *
     * @return $this
     */
    private function initializeAttributes()
    {
        $this->attributeCodes = [];
        $this->attributeCodesByType = [];

        foreach ($this->fieldProviders as $provider) {
            $attributeCodesByType = $provider->getAttributeCodeByType();
            foreach ($attributeCodesByType as $type => $codes) {
                if (!isset($this->attributeCodesByType[$type])) {
                    $this->attributeCodesByType[$type] = [];
                }

                $this->attributeCodes = array_unique(array_merge(
                    $this->attributeCodes,
                    $codes
                ));

                $this->attributeCodesByType[$type] = array_unique(array_merge(
                    $this->attributeCodesByType[$type],
                    $codes
                ));
            }
        }

        return $this;
    }

    /**
     * Initializes attribute identifiers
     *
     * @return $this
     */
    private function initializeAttributeIds()
    {
        $this->attributeIdsByType = [];
        $attributeIds = Mage::getResourceSingleton('ecomdev_sphinx/attribute')
            ->fetchAttributeIdsByCodes(
                $this->getAttributeCodes(),
                $this->entityType
            );
        
        foreach ($this->getAttributeCodesGroupedByType() as $groupType => $attributeCodes) {
            $this->attributeIdsByType[$groupType] = [];
            foreach ($attributeCodes as $code) {
                if (isset($attributeIds[$code])) {
                    $this->attributeIdsByType[$groupType][] = $attributeIds[$code];
                }
            }

            sort($this->attributeIdsByType[$groupType]);
        }

        return $this;
    }
}
