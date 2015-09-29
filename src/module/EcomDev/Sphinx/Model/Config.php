<?php

class EcomDev_Sphinx_Model_Config
{
    const XML_PATH_TRIGGERS = 'global/index/sphinx/%s/trigger';
    const INTERFACE_PRODUCT_INDEXER = 'EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_IndexerInterface';
    const INTERFACE_CONFIGURATION_RENDERER = '';
    const PRICE_PREFIX = 'price_index_';
    const PRICE_PATTERN = 'price_index_%s_%s';
    const XML_PATH_CONFIG = 'ecomdev_sphinx/%s';
    
    const CACHE_KEY_ATTRIBUTES = 'sphinx_attributes_%s';

    /**
     * Cached value for is enabled call
     * 
     * @var bool
     */
    protected $_isEnabled;
    
    /**
     * Returns list of attribute collections
     * 
     * @var EcomDev_Sphinx_Model_Resource_Attribute_Collection|EcomDev_Sphinx_Model_Attribute[]
     */
    protected $_attributes;

    /**
     * List of active attributes by code
     * 
     * @var EcomDev_Sphinx_Model_Attribute[]
     */
    protected $_activeAttributes;
    
    /**
     * List of all non system attributes
     * 
     * @var EcomDev_Sphinx_Model_Attribute[]
     */
    protected $_plainAttributes;

    /**
     * List of all attributes by code
     *
     * @var EcomDev_Sphinx_Model_Attribute[]
     */
    protected $_attributeByCode;

    /**
     * List of all attributes by type and code
     * 
     * @var EcomDev_Sphinx_Model_Attribute[][]
     */
    protected $_attributeByType;

    /**
     * List of option attributes
     * 
     * @var EcomDev_Sphinx_Model_Attribute[]
     */
    protected $_optionAttributes;
    
    /**
     * Eav configuration model
     * 
     * @var Mage_Eav_Model_Config
     */
    protected $_eavConfig;

    /**
     * List of used attribute codes
     * 
     * @var string[]
     */
    protected $_usedAttributeCodes;

    /**
     * Classes for product indexer
     * 
     * @var string[]
     */
    protected $_productIndexerClasses;


    /**
     * Classes for rendering configuration for attributes
     * 
     * @var string[]
     */
    protected $_configurationRendererClasses;

    /**
     * List of index attributes
     * 
     * @var string[]
     */
    protected $_indexAttributes;

    /**
     * List of index price attributes
     * 
     * @var string[]
     */
    protected $_indexPriceAttributes;

    /**
     * Configuration options for sphinx
     * 
     * @var string[][]
     */
    protected $_config = array();

    /**
     * Scope instance for layered navigation
     * 
     * @var EcomDev_Sphinx_Model_Scope
     */
    protected $_scope;
    
    /**
     * Returns an instance of EAV config model
     * 
     * @return Mage_Eav_Model_Config
     */
    public function getEavConfig()
    {
        if ($this->_eavConfig === null) {
            $this->_eavConfig = Mage::getSingleton('eav/config');
        }
        
        return $this->_eavConfig;
    }

    /**
     * Returns list of attributes
     * 
     * @return EcomDev_Sphinx_Model_Attribute[]|EcomDev_Sphinx_Model_Resource_Attribute_Collection
     */
    public function getAttributes()
    {
        if ($this->_attributes === null) {
            $this->_initAttributes();
        }

        return $this->_attributes;
    }

    /**
     * Initializes attributes
     * 
     * @return $this
     */
    protected function _initAttributes()
    {
        $this->_optionAttributes = array();
        $this->_attributeByType = array();
        $this->_attributeByCode = array();
        $this->_activeAttributes = array();
        $this->_plainAttributes = array();
        
        if ($this->_initAttributesFromCache()) {
            return $this;
        }
        
        Varien_Profiler::start(__FUNCTION__);
        $this->_attributes = Mage::getModel('ecomdev_sphinx/attribute')
            ->getCollection()
            ->addOrder('position', 'asc');

        Varien_Profiler::start(__FUNCTION__ . '::preloadAttributes');
        $codes = $this->_attributes->getColumnValues('attribute_code');
        
        if ($codes) {
            Mage::getSingleton('eav/config')
                ->preloadAttributes(Mage_Catalog_Model_Product::ENTITY, $codes);
        }
        Varien_Profiler::stop(__FUNCTION__ . '::preloadAttributes');
        
        
        
        $systemAttributes = $this->getResource()
            ->getSystemAttributes();

        foreach ($this->_attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            
            if ($attribute->getIsActive()) {
                $this->_activeAttributes[$code] = $attribute;
            }
            
            $this->_attributeByCode[$code] = $attribute;
            
            if ($attribute->isOption()) {
                $this->_optionAttributes[$code] = $attribute;     
            } elseif (!in_array($code, $systemAttributes, true)) {
                $this->_plainAttributes[$code] = $attribute;
                $this->_attributeByType[$attribute->getBackendType()][$code] = $attribute;
            }
        }
        
        $this->_saveAttributesToCache();
        Varien_Profiler::stop(__FUNCTION__);
        return $this;
    }

    /**
     * Initializes attributes from cache
     */
    protected function _initAttributesFromCache()
    {
        $cacheKey = sprintf(self::CACHE_KEY_ATTRIBUTES, Mage::app()->getStore()->getId());
        if (Mage::app()->useCache('sphinx') && $data = Mage::app()->loadCache($cacheKey)) {
            $data = json_decode($data, true);
            $attributeModel = Mage::getModel('ecomdev_sphinx/attribute');
            foreach ($data['attributes'] as $code => $attributeData) {
                $attribute = clone $attributeModel;
                $attribute->setData($attributeData);
                $this->_attributeByCode[$code] = $attribute;
                if (isset($data['active'][$code])) {
                    $this->_activeAttributes[$code] = $attribute; 
                }
                
                if (isset($data['option'][$code])) {
                    $this->_optionAttributes[$code] = $attribute;
                }
                
                if (isset($data['plain'][$code])) {
                    $this->_plainAttributes[$code] = $attribute;
                }

                if (isset($data['by_type'][$code])) {
                    $this->_attributeByType[$data['by_type'][$code]][$code] = $attribute;
                }
            }
                
            if ($this->_attributeByCode) {
                Mage::getSingleton('eav/config')->preloadAttributes(
                    Mage_Catalog_Model_Product::ENTITY, 
                    array_keys($this->_attributeByCode)
                );
            }
            
            return true;
        }
        
        return false; 
    }
    
    protected function _saveAttributesToCache()
    {
        if (Mage::app()->useCache('sphinx')) {
            $cacheKey = sprintf(self::CACHE_KEY_ATTRIBUTES, Mage::app()->getStore()->getId());
            $data = array();
            foreach ($this->_attributeByCode as $code => $attribute) {
                $data['attributes'][$code] = $attribute->getData();
                
                if (isset($this->_optionAttributes[$code])) {
                    $data['option'][$code] = true;
                } 
                
                if (isset($this->_activeAttributes[$code])) {
                    $data['active'][$code] = true;
                }
                
                if (isset($this->_plainAttributes[$code])) {
                    $data['plain'][$code] = true;
                }
                
                if (isset($this->_attributeByType[$attribute->getBackendType()][$code])) {
                    $data['by_type'][$code] = $attribute->getBackendType();
                }
            }
            
            Mage::app()->saveCache(json_encode($data), $cacheKey, array(
                EcomDev_Sphinx_Model_Attribute::CACHE_TAG
            ));
        }
        return $this;
    }

    /**
     * Returns an active attributes
     * 
     * @return EcomDev_Sphinx_Model_Attribute[]
     */
    public function getActiveAttributes()
    {
        if ($this->_activeAttributes === null) {
            $this->_initAttributes();
        }
        
        return $this->_activeAttributes;
    }

    /**
     * Returns all non system attributes
     *
     * @return EcomDev_Sphinx_Model_Attribute[]
     */
    public function getPlainAttributes()
    {
        if ($this->_plainAttributes === null) {
            $this->_initAttributes();
        }
        
        return $this->_plainAttributes;
    }
    
    /**
     * Returns an attribute by code
     * 
     * @param string $code
     * @return bool|EcomDev_Sphinx_Model_Attribute
     */
    public function getAttributeByCode($code)
    {
        if ($this->_attributeByCode === null) {
            $this->_initAttributes();
        }
        
        if (!isset($this->_attributeByCode[$code])) {
            return false;
        }
        
        return $this->_attributeByCode[$code];
    }

    /**
     * Returns an attribute by code
     *
     * @param string $type
     * @return EcomDev_Sphinx_Model_Attribute[]
     */
    public function getAttributesByType($type)
    {
        if ($this->_attributeByType === null) {
            $this->_initAttributes();
        }

        if ($type === 'option') {
            return $this->_optionAttributes;
        } elseif (!isset($this->_attributeByType[$type])) {
            return array();
        }

        return $this->_attributeByType[$type];
    }

    /**
     * Return list of used attribute codes in sphinx index
     * 
     * @return string[]
     */
    public function getUsedAttributeCodes()
    {
        if ($this->_usedAttributeCodes === null) {
            $this->_usedAttributeCodes = $this->getResource()
                ->getUsedAttributeCodes();
        }
        
        return $this->_usedAttributeCodes;
    }

    /**
     * Returns an attribute instance
     * 
     * @param string $attributeCode
     * @return false|Mage_Catalog_Model_Resource_Eav_Attribute
     */
    public function getProductAttribute($attributeCode)
    {
        return $this->getEavConfig()->getAttribute(
            Mage_Catalog_Model_Product::ENTITY, 
            $attributeCode
        );
    }

    /**
     * Returns price columns by group
     * 
     * @param bool $byGroup
     * @return string[]
     */
    public function getPriceColumns($byGroup = false)
    {
        $priceIndexColumn = $this->getResource()->getPriceIndexColumns();
        $customerGroupIds = $this->getResource()->getAllCustomerGroupIds();
        $result = array();

        foreach ($priceIndexColumn as $column) {
            foreach ($customerGroupIds as $customerGroupId) {
                $field = sprintf(self::PRICE_PATTERN, $column, $customerGroupId);
                
                if ($byGroup) {
                    $result[$customerGroupId][$field] = $column;
                } else {
                    $result[] = $field;
                }
            }
        }
        
        return $result;
    }
    
    protected function _initIndexAttributes()
    {
        $this->_indexAttributes = array();
        $this->_indexPriceAttributes = array();
        
        foreach ($this->getResource()->getIndexColumns() as $column) {
            if (strpos($column, self::PRICE_PREFIX) === 0) {
                $this->_indexPriceAttributes[] = $column;
            } else {
                $this->_indexAttributes[] = $column;
            }
        }
    }
    
    public function getIndexAttributes()
    {
        if ($this->_indexAttributes === null) {
            $this->_initIndexAttributes();
        }
        
        return $this->_indexAttributes;
    }

    public function getIndexPriceAttributes()
    {
        if ($this->_indexPriceAttributes === null) {
            $this->_initIndexAttributes();
        }
        
        return $this->_indexPriceAttributes;
    }

    /**
     * Returns an index path directory
     * 
     * @return string
     */
    public function getIndexPath()
    {
        return $this->getConfig('index_path');
    }

    /**
     * Returns sphinx configuration value
     * 
     * @param string $field
     * @param string $group
     * @return null|string
     */
    public function getConfig($field, $group = 'daemon')
    {
        if (!isset($this->_config[$group])) {
            $this->_config[$group] = Mage::getStoreConfig(sprintf(self::XML_PATH_CONFIG, $group));
        }
        
        if (!isset($this->_config[$group][$field])) {
            return null;
        }
        
        return $this->_config[$group][$field];
    }
    
    /**
     * Returns a resource  for a model
     * 
     * @return EcomDev_Sphinx_Model_Resource_Config
     */
    public function getResource()
    {
        return Mage::getResourceSingleton('ecomdev_sphinx/config');
    }

    /**
     * Returns an instance of sphinx container
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_Container
     */
    public function getContainer()
    {
        return Mage::getSingleton('ecomdev_sphinx/sphinx_container');
    }

    /**
     * Checks if sphinx is actually available
     * 
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->_isEnabled === null) {
            $this->_isEnabled = $this->getConfig('active', 'general') === '1'
                && $this->getContainer()->isAvailable();
        }
        
        return $this->_isEnabled;
    }

    /**
     * Checks if sphinx is actually available
     *
     * @return bool
     */
    public function isSearchEnabled()
    {
        if ($this->isEnabled() && $this->getConfig('search_active', 'general') === '1') {
           return true;
        }

        return false;
    }

    /**
     * Returns a scope model
     *
     * @param int $scopeId
     * @return EcomDev_Sphinx_Model_Scope
     */
    public function getScope($scopeId = null)
    {
        if ($this->_scope === null) {
            $this->_scope = Mage::getModel('ecomdev_sphinx/scope');
            if ($scopeId === null) {
                $scopeId = $this->getConfig('scope', 'general');
            }
            if ($scopeId) {
                $this->_scope->load($scopeId);
            }
        }
        
        return $this->_scope;
    }

    /**
     * Returns triggers for specified entity type
     *
     * @param string $entityType
     * @return string[][][]
     */
    public function getTriggers($entityType)
    {
        $triggers = [];
        foreach (Mage::getConfig()
                     ->getNode(sprintf(self::XML_PATH_TRIGGERS, $entityType))->children() as $name => $node) {
            $types = ['insert', 'update', 'delete'];
            if (!isset($node->table)) {
                continue;
            }

            if (!isset($node->table->prefix)) {
                $tableName = (string) $node->table;
            } else {
                $tableName = [(string)$node->table->prefix, (string)$node->table->suffix];
            }

            try {
                $tableName = Mage::getSingleton('core/resource')->getTableName($tableName);
            } catch (Exception $e) {
                Mage::logException($e);
                continue;
            }

            // To allow deletion of triggers
            $triggers[$tableName] = [];

            foreach ($types as $type) {
                if (isset($node->types)
                    && (!isset($node->types->{$type}) || (string)$node->types->{$type} === '0')) {
                    continue;
                }

                if (!isset($node->field) || (string)$node->field == '') {
                    continue;
                }

                $check = [];
                if ($type === 'update' && isset($node->update)) {
                    foreach ($node->update->children() as $name => $flag) {
                        if ((string)$flag === '0') {
                            continue;
                        }
                        $check[] = $name;
                    }
                }

                $triggers[$tableName][$type] = [
                    'field' => (string)$node->field,
                    'check' => $check,
                    'type' => $type,
                    'table' => $tableName
                ];
            }
        }

        return $triggers;
    }
}
