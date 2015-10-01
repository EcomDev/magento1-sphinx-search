<?php

use EcomDev_Sphinx_Model_LayerInterface as LayerInterface;

/**
 * @method EcomDev_Sphinx_Model_Resource_Scope_Collection getCollection()
 * @method $this setName(string $name)
 * @method $this setConfiguration(array $configuration)
 * @method $this setParentId(int $parentId)
 * @method int getParentId()
 * @method string getName()
 * @method array getConfiguration()
 *
 */
class EcomDev_Sphinx_Model_Scope
    extends EcomDev_Sphinx_Model_AbstractModel
{
    const ENTITY = 'sphinx_scope';

    const CACHE_TAG = 'SPHINX_SCOPE';
    
    const CACHE_KEY_FACETS = 'sphinx_scope_facet_%s_%s_%s';
    const CACHE_KEY_SEARCH = 'sphinx_scope_search_%s_%s_%s';

    /**
     * Layer instance
     * 
     * @var LayerInterface
     */
    protected $_layer;

    /**
     * Cache tag for cleaning it up on the frontend
     *
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Entity used to invoke indexation process
     *
     * @var string
     */
    protected $_indexerEntity = self::ENTITY;

    /**
     * Instance of main query builder
     * 
     * @var EcomDev_Sphinx_Model_Sphinx_Query_Builder
     */
    protected $_baseQuery;

    /**
     * List of available facets
     * 
     * @var EcomDev_Sphinx_Model_Sphinx_FacetInterface[]
     */
    protected $_facets;

    /**
     * Returns list of searchable attributes
     *
     * @var string[]
     */
    protected $_searchableAttributes;

    /**
     * List of available sort options
     * 
     * @var array()
     */
    protected $_sortOptions;
    
    /**
     * Initializes a scope
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/scope');
    }

    /**
     * Sets data into model from post array
     *
     * @param array $data
     * @return $this
     */
    public function setDataFromArray(array $data)
    {
        $jsonData = array();

        if (isset($data['configuration']) && is_array($data['configuration'])) {
            $jsonData = $data['configuration'];
        }
        
        $this->setConfiguration($jsonData);
        return $this;
    }

    /**
     * Init general validation rules
     * 
     * @return $this
     */
    protected function _initValidation()
    {
        $this->_addEmptyValueValidation('name', $this->__('Name'), self::VALIDATE_LIGHT);
        $this->_addEmptyValueValidation('parent_id', $this->__('Parent Scope'), self::VALIDATE_LIGHT, true);
        $this->_addValueValidation('configuration', $this->__('Configuration should be a valid JSON'), function ($value) {
            return is_array($value);
        }, self::VALIDATE_FULL);
        return $this;
    }

    /**
     * Returns category filter name
     * 
     * @return $this
     */
    protected function _getCategoryFilterLabel()
    {
        if ($label = $this->getConfigurationValue('category_filter/label')) {
            return $label;
        }
        
        return Mage::helper('ecomdev_sphinx')->__('Categories');
    }

    /**
     * Returns a configuration value
     *
     * @param string $path
     * @return bool
     */
    public function getConfigurationValue($path)
    {
        return $this->getData('configuration/' . $path);
    }

    /**
     * Returns category filter name
     *
     * @return $this
     */
    protected function _getPriceOptions()
    {
        $result = array();
        $result['range_step'] = 0;
        $result['range_count'] = 0;
        
        if ($value = $this->getConfigurationValue('price_filter/range_step')) {
            $result['range_step'] = (float)$value;
        }
        if ($value = $this->getConfigurationValue('price_filter/range_count')) {
            $result['range_count'] = (int)$value;
        }
        
        if ($result['range_step'] <= 0) {
            $result['range_step'] = 200;
        }
        
        if ($result['range_count'] <= 0) {
            $result['range_count'] = 4;
        }

        return $result;
    }

    /**
     * @return EcomDev_Sphinx_Model_Sphinx_Facet_Category
     */
    protected function _getCategoryFacet()
    {
        $class = Mage::getConfig()->getModelClassName('ecomdev_sphinx/sphinx_facet_category');
        return new $class(
            $this->_getCategoryFilterLabel(),
            $this->_getCategoryData(),
            array($this->getLayer()->getCurrentCategory()->getId()),
            $this->getLayer()->getCurrentCategory()->getData(),
            $this->getConfigurationValue('category_filter/renderer')
        );
    }
    
    /**
     * Return list of all available facets
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_FacetInterface[]
     */
    public function getFacets()
    {
        if ($this->_facets === null) {
            $this->_initFacets();
        }
        
        return $this->_facets;
    }

    /**
     * Return list of order fields for search
     * 
     * @return array
     */
    public function getSortOrders()
    {
        if ($this->_sortOptions === null) {
            $this->_sortOptions = array();

            if ($this->getLayer() instanceof EcomDev_Sphinx_Model_Search_Layer) {
                $this->_sortOptions['relevance'] = Mage::helper('ecomdev_sphinx')->__('Relevance');
            } else {
                $this->_sortOptions['position'] = Mage::helper('ecomdev_sphinx')->__('Best Value');
            }
            
            foreach ($this->_getConfig()->getActiveAttributes() as $attribute) {
                if ($attribute->getIsSort()) {
                    $this->_sortOptions[$attribute->getAttributeCode()] = $attribute->getAttribute()->getFrontendLabel();
                }
            }
            
        }
        
        return $this->_sortOptions;
    }

    /**
     * Initializes facets values
     * 
     * @return $this
     */
    protected function _initFacets()
    {
        $cacheKey = sprintf(
            self::CACHE_KEY_FACETS,
            $this->getId(),
            Mage::app()->getStore()->getId(),
            Mage::app()->getStore()->getRootCategoryId()
        );

        if (Mage::app()->useCache('sphinx') && $data = Mage::app()->loadCache($cacheKey)) {
            $facets = unserialize($data);
        } else {
            $excludedFacets = $this->getConfigurationValue('general/limit_facet');
            $facets = array();
            foreach ($this->_getConfig()->getActiveAttributes() as $attribute) {
                if (is_array($excludedFacets) && in_array($attribute->getId(), $excludedFacets)) {
                    continue;
                }
                if ($attribute->getIsLayered()
                    && ($facet = $attribute->getFacetModel())
                    && $facet->isAvailable()) {
                    $facets[$facet->getFilterField()] = $facet;
                }
            }
        }

        $this->_facets = array();
        $categoryFacet = $this->_getCategoryFacet();
        $this->_facets[$categoryFacet->getFilterField()] = $categoryFacet;
        $this->_facets += $facets;

        if (Mage::app()->useCache('sphinx')) {
            Mage::app()->saveCache(
                serialize($facets),
                $cacheKey,
                array(
                    self::CACHE_TAG,
                    EcomDev_Sphinx_Model_Attribute::CACHE_TAG
                )
            );
        }

        return $this;
    }

    /**
     * Initializes searchable attributes
     *
     * @return $this
     */
    protected function _initSearchableAttributes()
    {
        $cacheKey = sprintf(
            self::CACHE_KEY_SEARCH,
            $this->getId(),
            Mage::app()->getStore()->getId(),
            Mage::app()->getStore()->getRootCategoryId()
        );

        if (Mage::app()->useCache('sphinx') && $data = Mage::app()->loadCache($cacheKey)) {
            $this->_searchableAttributes = unserialize($data);
            return $this;
        }

        $this->_searchableAttributes = array();
        $this->_searchableAttributes[] = 's_anchor_category_names';
        $this->_searchableAttributes[] = 'request_path';

        foreach ($this->_getConfig()->getActiveAttributes() as $attribute) {
            if ($attribute->getIsFulltext() && !$attribute->isOption()) {
                $this->_searchableAttributes[] = $attribute->getAttributeCode();
            } elseif ($attribute->getIsFulltext()) {
                $this->_searchableAttributes[] = sprintf(
                    's_%s_label', $attribute->getAttributeCode()
                );
            }
        }

        if (Mage::app()->useCache('sphinx')) {
            Mage::app()->saveCache(
                serialize($this->_searchableAttributes),
                $cacheKey,
                array(
                    self::CACHE_TAG,
                    EcomDev_Sphinx_Model_Attribute::CACHE_TAG
                )
            );
        }

        return $this;
    }

    /**
     * Returns list of searchable attributes
     *
     * @return string[]
     */
    public function getSearchableAttributes()
    {
        if ($this->_searchableAttributes === null) {
            $this->_initSearchableAttributes();
        }

        return $this->_searchableAttributes;
    }


    /**
     * Applies request values
     * 
     * @param Mage_Core_Controller_Request_Http $request
     * @return $this
     */
    public function applyRequest(Mage_Core_Controller_Request_Http $request)
    {
        Varien_Profiler::start(__METHOD__);
        Varien_Profiler::start(__METHOD__ . '::queryBuilderInit');
        $this->_baseQuery = $this->_getConfig()
            ->getContainer()
            ->queryBuilder();
        $this->getLayer()
            ->getProductCollection()
            ->initQuery(
                $this->_baseQuery,
                $this->_getConfig()->getContainer()->getIndexNames('product')
            );
        Varien_Profiler::stop(__METHOD__ . '::queryBuilderInit');

        Varien_Profiler::start(__METHOD__ . '::priceOptions');

        $this->_getConfig()->getAttributeByCode('price')
            ->setCustomerGroupId(
                $this->getLayer()
                    ->getProductCollection()
                    ->getCustomerGroupId()
            )
            ->addData($this->_getPriceOptions());

        Varien_Profiler::stop(__METHOD__ . '::priceOptions');

        Varien_Profiler::start(__METHOD__ . '::facets');
        $facets = $this->getFacets();
        Varien_Profiler::stop(__METHOD__ . '::facets');

        Varien_Profiler::start(__METHOD__ . '::applyFilters');
        foreach ($facets as  $code => $facet) {
            if ($request->getQuery($code) !== null) {
                $facet->apply($request->getQuery($code));
            }
        }
        Varien_Profiler::stop(__METHOD__ . '::applyFilters');

        Varien_Profiler::start(__METHOD__ . '::getSortOrders');
        $sortOptions = $this->getSortOrders();
        $currentOrder = $request->getQuery('order');
        $this->setCurrentDirection($request->getQuery('dir'));
        
        if ($currentOrder && isset($sortOptions[$currentOrder])) {
            $this->setCurrentOrder($currentOrder);
        } else {
            $this->setCurrentOrder(current(array_keys($sortOptions)));
        }

        Varien_Profiler::stop(__METHOD__ . '::getSortOrders');

        $page = (int)$request->getParam('p');
        if (!$page) {
            $page = 1;
        }

        $this->setCurrentPage($page);

        Varien_Profiler::stop(__METHOD__);
        return $this;
    }
    
    protected function _getCategoryData()
    {
        Varien_Profiler::start(__METHOD__);
        $parentPath = $this->getLayer()->getCurrentCategory()->getPath();
        $maxLevel = (int)$this->getConfigurationValue('category_filter/max_level_deep');

        $minLevel = $this->getLayer()->getCurrentCategory()->getLevel();

        if ($maxLevel <= 0) {
            $maxLevel = 2;
        }

        $maxLevel = $this->getLayer()->getCurrentCategory()->getLevel() + $maxLevel;

        if ((int)$this->getConfigurationValue('category_filter/include_same_level') > 0) {
            $parentPath = dirname($parentPath);
            $minLevel -= 1;
        }

        $query = $this->_getConfig()->getContainer()->queryBuilder();
        $query
            ->select('category_id', 'name', 'path', 'request_path', 'include_in_menu')
            ->from($this->_getConfig()->getContainer()->getIndexNames('category'))
            ->where('is_active', 1)
            ->where('level', '<=', (int)$maxLevel)
            ->where('level', '>', (int)$minLevel)
            ->orderBy('level', 'asc')
            ->orderBy('position', 'asc')
            ->match('path', $query->expr(
                '"^' . $query->escapeMatch($parentPath) . '"'
            ))
            ->limit(1000);

        $result = array();
        foreach ($query->execute() as $row) {
            $result[(int)$row['category_id']] = $row;
        }

        Varien_Profiler::stop(__METHOD__);
        return $result;
    }
    
    

    /**
     * Fetches data from sphinx
     * 
     * @return $this
     */
    public function fetchData()
    {
        Varien_Profiler::start(__METHOD__);
        $selectQuery = clone $this->_baseQuery;
        $this->getLayer()->getProductCollection()->addFieldsToQuery(
            $selectQuery, 
            $this->_getConfig()->getContainer()->getIndexColumns('product'),
            $this
        );
        
        /** @var EcomDev_Sphinx_Model_Sphinx_Facet_Filter_ConditionInterface[] $facetConditions */
        $facetConditions = array();
        foreach ($this->getFacets() as $filterName => $facet) {
            if ($filterCondition = $facet->getFilterCondition()) {
                $facetConditions[$filterName] = $filterCondition;
                $filterCondition->apply($selectQuery);
            }
        }
        
        $facetQueries = array();
        foreach ($this->getFacets() as $filterName => $facet) {
            $facetQueries[$filterName] = $facet->getFacetSphinxQL($this->_baseQuery);
            foreach ($facetConditions as $conditionName => $condition) {
                if ($filterName === $conditionName && !$facet->isSelfFilterable()) {
                    continue;
                }
                
                $condition->apply($facetQueries[$filterName]);
            }
        }

        $prevQuery = $selectQuery;

        $prevQuery = $prevQuery
            ->enqueue($this->_getConfig()->getContainer()->getHelper()->showMeta());

        foreach ($facetQueries as $filterName => $query) {
            $prevQuery = $prevQuery->enqueue($query);
        }
        
        $results = $prevQuery->executeBatch();
        $results->store();
        $results = $results->getStored();

        $collectionResult = array_shift($results);
        $collectionResultMeta = $this->_getConfig()->getContainer()->getHelper()->pairsToAssoc(
            array_shift($results)
        );

        
        $facets = $this->getFacets();
        $optionIds = array();
        $setOptionNames = array();
        
        foreach (array_keys($facetQueries) as $filterName) {
            $result = array_shift($results);

            /** @var $result \Foolz\SphinxQL\Drivers\Pdo\ResultSet */
            $facet = $facets[$filterName];
            $facet->setSphinxResponse($result->fetchAllAssoc());
            if ($facet instanceof EcomDev_Sphinx_Model_Sphinx_Facet_OptionAwareInterface) {
                $optionIds = array_merge($optionIds, $facet->getOptionIds());
                $setOptionNames[] = $facet;
            }
        }
        
        
        if ($optionIds) {
            $optionNames = $this->getResource()->getOptionNames(
                $optionIds, 
                $this->getLayer()->getCurrentStore()->getId()
            );

            $sortOrder = array_combine(array_keys($optionNames), array_keys(array_values($optionNames)));
            
            foreach ($setOptionNames as $facet) {
                $facet->setOptionLabel($optionNames, $sortOrder);
            }
        }
        
        $this->getLayer()->getProductCollection()
            ->loadFromSphinx($collectionResult, $collectionResultMeta);

        Varien_Profiler::stop(__METHOD__);
        return $this;
    }

    /**
     * Sets layer model for scope
     * 
     * @param EcomDev_Sphinx_Model_LayerInterface $layer
     * @return $this
     */
    public function setLayer(LayerInterface $layer)
    {
        $this->_layer = $layer;
        return $this;
    }

    /**
     * Returns layer model for scope
     * 
     * @return EcomDev_Sphinx_Model_LayerInterface
     */
    public function getLayer()
    {
        return $this->_layer;
    }

    /**
     * Returns sphinx configuration
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }
}
