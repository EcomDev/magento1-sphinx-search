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
    const DEFAULT_MAX_MATCHES = 1000;

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
    protected function _setDataFromArray(array $data)
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
            [$this->getLayer()->getCurrentCategory()->getId()],
            $this->getLayer()->getCurrentCategory()->getData() + [
                'category_filter' => $this->getConfigurationValue('category_filter')
            ],
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
            $this->_sortOptions = [];

            if ($this->getConfigurationValue('sort_order/is_active')) {
                $include = $this->getConfigurationValue('sort_order/include_order');
                $exclude = $this->getConfigurationValue('sort_order/exclude_order');

                foreach ($this->_getConfig()->getSortOrders() as $sortOrder) {
                    if (is_array($include) && !in_array($sortOrder->getCode(), $include)) {
                        continue;
                    } elseif (is_array($exclude) && in_array($sortOrder->getCode(), $exclude)) {
                        continue;
                    }

                    $this->_sortOptions[$sortOrder->getCode()] = $sortOrder->getStoreLabel();
                }
            } else {
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

        }

        return $this->_sortOptions;
    }

    public function getDefaultSortOrder()
    {
        $sortOrders = $this->getSortOrders();

        if ($this->getConfigurationValue('sort_order/is_active')
            && $this->getConfigurationValue('default_sort_order')) {
            return $this->getConfigurationValue('default_sort_order');
        }

        return key($sortOrders);
    }

    /**
     * Return list of order fields for search
     *
     * @return EcomDev_Sphinx_Model_Sort[]
     */
    public function getComplexSortOrder()
    {
        if (!$this->getConfigurationValue('sort_order/is_active')) {
            return false;
        }

        $orders = $this->_getConfig()->getSortOrders();
        $complexSortOrders = [];
        foreach ($this->getSortOrders() as $code => $label) {
            if (!isset($orders[$code])) {
                continue;
            }

            $complexSortOrders[$code] = $orders[$code];
        }

        return $complexSortOrders;
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

            if ($this->getConfigurationValue('category_filter/is_active')) {
                $categoryFacet = $this->_getCategoryFacet();

                if (isset($facets[$categoryFacet->getFilterField()])) {
                    $facets[$categoryFacet->getFilterField()] = $categoryFacet;
                }
            }

            $this->_facets = array_filter($facets);
            return $this;
        }

        $excludedFacets = $this->getConfigurationValue('general/limit_facet');
        $includeFacets = $this->getConfigurationValue('general/include_facet');
        $includeVirtual = $this->getConfigurationValue('general/virtual_field');

        $facets = array();
        $facetOrder = [];
        foreach ($this->_getConfig()->getActiveAttributes() as $attribute) {
            if (is_array($includeFacets) && !in_array($attribute->getId(), $includeFacets)) {
                continue;
            }

            if (is_array($excludedFacets) && in_array($attribute->getId(), $excludedFacets)) {
                continue;
            }

            if ($attribute->getIsLayered()
                && ($facet = $attribute->getFacetModel())
                && $facet->isAvailable()) {
                $facets[$facet->getFilterField()] = $facet;
                $facetOrder[$facet->getFilterField()] = $attribute->getPosition();
                if ($this->getConfigurationValue('facet_sort_order/active')
                    && $this->getConfigurationValue(
                        sprintf('facet_sort_order/%s_override', $attribute->getId()))) {
                    $facetOrder[$facet->getFilterField()] = $this->getConfigurationValue(
                        sprintf('facet_sort_order/%s_value', $attribute->getId())
                    );
                }
            }
        }

        foreach ($this->_getConfig()->getVirtualFields() as $virtualField) {
            if (!is_array($includeVirtual) || !in_array($virtualField->getCode(), $includeVirtual)) {
                continue;
            }

            if (($facet = $virtualField->getFacet())
                && $facet->isAvailable()) {
                $facets[$facet->getFilterField()] = $facet;
                $facetOrder[$facet->getFilterField()] = $virtualField->getPosition();
                if ($this->getConfigurationValue('facet_sort_order/active')
                    && $this->getConfigurationValue(
                        sprintf('facet_sort_order/%s_override', $virtualField->getCode()))) {
                    $facetOrder[$facet->getFilterField()] = $this->getConfigurationValue(
                        sprintf('facet_sort_order/%s_value', $virtualField->getCode())
                    );
                }
            }
        }

        $this->_facets = array();

        if ($this->getConfigurationValue('category_filter/is_active')) {
            $categoryFacet = $this->_getCategoryFacet();

            if (is_numeric($this->getConfigurationValue('category_filter/position'))) {
                $facets[$categoryFacet->getFilterField()] = $categoryFacet;
                $facetOrder[$categoryFacet->getFilterField()] = (int)$this->getConfigurationValue(
                    'category_filter/position'
                );
            } else {
                $this->_facets[$categoryFacet->getFilterField()] = $categoryFacet;
            }
        }

        asort($facetOrder);

        foreach ($facetOrder as $key => $order) {
            $this->_facets[$key] = $facets[$key];
        }

        if (Mage::app()->useCache('sphinx')) {
            $toSave = $this->_facets;

            if (isset($categoryFacet) && isset($toSave[$categoryFacet->getFilterField()])) {
                $toSave[$categoryFacet->getFilterField()] = false;
            }

            Mage::app()->saveCache(
                serialize($toSave),
                $cacheKey,
                array(
                    self::CACHE_TAG,
                    EcomDev_Sphinx_Model_Attribute::CACHE_TAG,
                    EcomDev_Sphinx_Model_Field::CACHE_TAG
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
        $this->_searchableAttributes = array();

        if (!$this->getConfigurationValue('search_attribute/active')) {
            $this->_searchableAttributes['s_anchor_category_names'] = 5;
            $this->_searchableAttributes['request_path'] = 5;

            foreach ($this->_getConfig()->getSearchableAttributes() as $attribute) {
                if ($attribute->isOption()) {
                    $this->_searchableAttributes[sprintf('s_%s_label', $attribute->getAttributeCode())] = 10;
                } else {
                    $this->_searchableAttributes[$attribute->getAttributeCode()] = 10;
                }
            }
        } else {
            $options = ['s_anchor_category_names', 's_direct_category_names', 'request_path'];
            $options = array_merge($options, array_keys($this->_getConfig()->getSearchableAttributes()));

            $searchableAttributes = $this->_getConfig()->getSearchableAttributes();
            foreach ($options as $attributeCode) {
                $fieldCode = $attributeCode;
                if (isset($searchableAttributes[$attributeCode])
                    && $searchableAttributes[$attributeCode]->isOption()) {
                    $fieldCode = sprintf('s_%s_label', $attributeCode);
                }

                if ($this->getConfigurationValue(sprintf('search_attribute/%s_active', $attributeCode))) {
                    $this->_searchableAttributes[$fieldCode] = (int)$this->getConfigurationValue(
                        sprintf('search_attribute/%s_weight', $attributeCode)
                    );
                }
            }
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
     * Returns query builder instance
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Query_Builder
     */
    public function getQueryBuilder()
    {
        return $this->_getConfig()
            ->getContainer()
            ->queryBuilder();
    }

    /**
     * Returns container instance
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Container
     */
    public function getContainer()
    {
        return $this->_getConfig()->getContainer();
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
        $this->_baseQuery = $this->getQueryBuilder();
        $this->getLayer()
            ->getProductCollection()
            ->initQuery(
                $this->_baseQuery,
                $this->_getConfig()->getContainer()->getIndexNames('product'),
                $this->_getConfig()->getContainer()->getIndexColumns('product'),
                $this->_getConfig()->getContainer()->getIndexFields('product')
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
            $this->setCurrentOrder($this->getDefaultSortOrder());
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
        $currentCategory = $this->getLayer()->getCurrentCategory();

        $minLevel = $currentCategory->getLevel();

        switch ($this->getConfigurationValue('category_filter/include_same_level')) {
            case EcomDev_Sphinx_Model_Source_Level::LEVEL_SAME:
                $parentPath = dirname($parentPath);
                $minLevel -= 1;
                $maxLevel = $currentCategory->getLevel() + $maxLevel + 1;
                break;
            case EcomDev_Sphinx_Model_Source_Level::LEVEL_CUSTOM:
                $minLevel = (int)$this->getConfigurationValue('category_filter/top_category_level');
                if ($minLevel === 0) {
                    $minLevel = 4;
                }
                $parents = explode('/', $parentPath);
                if (count($parents) > $minLevel) {
                    $parents = array_slice($parents, 0, $minLevel + 1);
                }
                $parentPath = implode('/', $parents);
                break;
            default:
                if ($maxLevel <= 0) {
                    $maxLevel = 2;
                }
                $maxLevel = $currentCategory->getLevel() + $maxLevel + 1;
                break;
        }

        $parentIds = explode('/', $parentPath);

        $proxy = (object)[
            'columns' => [
                'category_id',
                'name',
                'path',
                'request_path',
                'include_in_menu'
            ],
            'conditions' => [
                'level' => ['>', (int)$minLevel]
            ]
        ];

        // Allow to modify select attributes
        Mage::dispatchEvent('ecomdev_sphinx_scope_category_data_columns', [
            'proxy' => $proxy,
            'max_level' => $maxLevel,
            'min_level' => $minLevel,
            'parent_path' => $parentPath,
            'scope' => $this
        ]);

        $result = [];
        /* @var $source EcomDev_Sphinx_Model_Sphinx_Category */
        $source = Mage::getModel('ecomdev_sphinx/sphinx_category', [
            'container' => $this->_getConfig()->getContainer()
        ]);


        $productCount = [];
        $rootId = end($parentIds);

        if ($this->getConfigurationValue('category_filter/include_product_count')) {
            $productCount = $source->getProductCount($rootId);
        }

        foreach ($source->getCategoriesData($parentPath, $maxLevel, $proxy->columns, $proxy->conditions) as $row) {
            $row['count'] = 0;
            if (isset($productCount[$row['category_id']])) {
                $row['count'] = $productCount[$row['category_id']];
            }

            $result[(int)$row['category_id']] = $row;
        }

        if (isset($productCount[$rootId])) {
            $currentCategory->setRootProductCount($productCount[$rootId]);
        }

        Varien_Profiler::stop(__METHOD__);
        return $result;
    }

    /**
     * Activates search mode in container
     *
     * @return $this
     */
    public function activateSearchMode()
    {
        $this->_getConfig()->getContainer()->activateSearchMode();
        return $this;
    }

    /**
     * Fetches collection data only
     *
     * @param EcomDev_Sphinx_Model_Resource_Product_Collection $collection
     * @param Closure $customCallback
     * @return $this
     * @throws \Foolz\SphinxQL\Exception\SphinxQLException
     */
    public function fetchCollection(EcomDev_Sphinx_Model_Resource_Product_Collection $collection, $customCallback = null)
    {
        Varien_Profiler::start(__METHOD__);
        $selectQuery = $this->getQueryBuilder();

        $collection->initQuery(
            $selectQuery,
            $this->_getConfig()->getContainer()->getIndexNames('product'),
            $this->_getConfig()->getContainer()->getIndexColumns('product'),
            $this->_getConfig()->getContainer()->getIndexFields('product')
        );

        if (is_callable($customCallback)) {
            call_user_func($customCallback, $collection, $selectQuery);
        }

        $collection->addFieldsToQuery(
            $selectQuery,
            $this->_getConfig()->getContainer()->getIndexColumns('product'),
            $this
        );

        $results = $selectQuery->enqueue($this->_getConfig()->getContainer()->getHelper()->showMeta())
            ->executeBatch()->store()->getStored();

        $collectionResult = array_shift($results);
        $collectionResultMeta = $this->_getConfig()->getContainer()->getHelper()->pairsToAssoc(
            array_shift($results)
        );

        $collection->loadFromSphinx($collectionResult, $collectionResultMeta);

        Varien_Profiler::stop(__METHOD__);
        return $this;
    }

    /**
     * Returns max matches
     *
     * @return int
     */
    public function getMaxMatches()
    {
        $matches = (int)$this->getConfigurationValue('general/max_matches');

        if ($matches > 0) {
            return $matches;
        }

        $matches = (int)$this->_getConfig()->getConfig('max_matches', 'general');

        if ($matches > 0) {
            return $matches;
        }

        return self::DEFAULT_MAX_MATCHES;
    }


    /**
     * Returns query for match expression
     *
     * @param $text
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @return string
     */
    public function prepareMatchString($text, $query)
    {
        $text = strtr(
            $text,
            [
                "\n" => ' ',
                "\r" => ' ',
                "\t" => ' '
            ]
        );

        $keywords = array_filter(explode(' ', $text), function ($v) { return $v !== ''; });

        if (empty($keywords)) {
            $keywords[] = '';
        }

        $formatWord = function ($word) use ($query) {
            return sprintf('%s', addcslashes($word, '"*'));
        };

        return implode(' ', array_map($formatWord, $keywords));
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
        $selectQuery->option('max_matches', $this->getMaxMatches());

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
     * Returns current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return Mage::app()->getStore()->getId();
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
