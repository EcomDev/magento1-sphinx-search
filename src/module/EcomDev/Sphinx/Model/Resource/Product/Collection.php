<?php

use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;

class EcomDev_Sphinx_Model_Resource_Product_Collection
    extends Mage_Catalog_Model_Resource_Product_Collection
{
    const FLAG_ONLY_DIRECT_CATEGORY = 'only_direct_category';

    /**
     * @var EcomDev_Sphinx_Model_LayerInterface
     */
    protected $_layer;
    
    protected $_fieldsToSelect;

    /**
     * List of all available columns
     *
     * @var string[]|null
     */
    protected $_availableColumns;

    /**
     * List of all available fields
     *
     * @var string[]|null
     */
    protected $_availableFields;

    /**
     * @var QueryBuilder
     */
    protected $_currentQuery;
    
    public function setLayer(EcomDev_Sphinx_Model_LayerInterface $layer)
    {
        $this->_layer = $layer;
        return $this;
    }

    /**
     * Returns an instance of scope
     * 
     * @return EcomDev_Sphinx_Model_Scope
     */
    public function getScope()
    {
        return Mage::getSingleton('ecomdev_sphinx/config')->getScope();
    }

    /**
     * Initializes base query
     *
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @param string[] $indexes
     * @param string[] $availableColumns
     * @param string[] $availableFields
     * @return $this
     */
    public function initQuery(QueryBuilder $query, $indexes, $availableColumns = null, $availableFields = null)
    {
        $query->select()
            ->from($indexes);

        $this->_availableColumns = $availableColumns;
        $this->_availableFields = $availableFields;
        
        if (isset($this->_productLimitationFilters['category_id'])) {
            $filterName = 'anchor_category_ids';
            if (!empty($this->_productLimitationFilters['category_is_anchor'])
                || $this->getFlag(self::FLAG_ONLY_DIRECT_CATEGORY)) {
                $filterName = 'direct_category_ids';
            }

            $query->match(
                's_' . $filterName,
                sprintf('"%s"', Mage::helper('ecomdev_sphinx')->getCategoryMatch(
                    (int)$this->_productLimitationFilters['category_id']
                )),
                true
            );
        }
        
        if (isset($this->_productLimitationFilters['search_query'])) {
            $fields = $this->getScope()->getSearchableAttributes();

            foreach (array_keys($fields) as $field) {
                if (!isset($this->_availableFields[$field])) {
                    unset($fields[$field]);
                }
            }

            $query->match(
                array_keys($fields),
                $this->getScope()->prepareMatchString($this->_productLimitationFilters['search_query'], $query),
                true
            );

            $query->option('field_weights', $fields);
        }

        $this->_currentQuery = $query;

        return $this;
    }

    /**
     * Returns current customer group id
     * 
     * @return int
     */
    public function getCustomerGroupId()
    {
        $customerGroupId = 0;
        if (!empty($this->_productLimitationFilters['use_price_index'])) {
            $customerGroupId = $this->_productLimitationFilters['customer_group_id'];
        }
        
        return $customerGroupId;
    }
    
    /**
     * Adds fields to query
     * 
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @param array $indexFields
     * @return $this
     */
    public function addFieldsToQuery(QueryBuilder $query,
                                     array $indexFields,
                                     EcomDev_Sphinx_Model_Scope $scope)
    {
        $this->_fieldsToSelect['entity_id'] = 'product_id';
        $this->_fieldsToSelect['type_id'] = 'type_id';
        $this->_fieldsToSelect['sku'] = 'sku';
        $this->_fieldsToSelect['is_salable'] = 'stock_status';
        $this->_fieldsToSelect['is_in_stock'] = 'stock_status';

        if (isset($this->_productLimitationFilters['category_id'])) {
            $this->_fieldsToSelect['request_path'] = $query->exprFormat(
                'IF(s_category_url.%1$s <> NULL, s_category_url.%1$s, request_path)',
                Mage::helper('ecomdev_sphinx')->getCategoryMatch(
                    (int)$this->_productLimitationFilters['category_id']
                )
            );
        } else {
            $this->_fieldsToSelect['request_path'] = 'request_path';
        }


        Mage::dispatchEvent(
            'ecomdev_sphinx_product_collection_add_fields_to_query', ['query' => $query, 'collection' => $this]
        );
        
        if (!empty($this->_productLimitationFilters['use_price_index'])) {
            $customerGroupId = $this->_productLimitationFilters['customer_group_id'];
            $this->_addPriceIndexFields($customerGroupId);
        }
        
        foreach ($this->_fieldsToSelect as $field => $source) {
            if (!in_array($source, $indexFields)) {
                continue;
            }
            
            if ($field === $source) {
                $query->select($source);
            } elseif ($source instanceof \Foolz\SphinxQL\Expression) {
                $query->select($query->exprFormat('%s as %s', $source, $field));
            } else {
                $query->select($query->exprFormat(
                    '%s as %s',
                    $query->quoteIdentifier($source),
                    $query->quoteIdentifier($field)
                ));
            }
        }
        $order = $scope->getCurrentOrder();
        $direction = (strtolower($scope->getCurrentDirection()) == 'desc' ? 'desc' : 'asc');

        $complexOrders = $scope->getComplexSortOrder();

        if ($complexOrders !== false) {
            if ($complexOrders && !isset($complexOrders[$order])) {
                $order = key($complexOrders);
            }

            if (isset($complexOrders[$order])) {

                foreach ($complexOrders[$order]->getSortInfo($direction) as $column => $direction) {
                    if ($column === 'price') {
                        $column = 'price_index_min_price_' . $this->getCustomerGroupId();
                    }

                    if ($column === '@position'
                        && in_array('j_category_position', $indexFields)
                        && isset($this->_productLimitationFilters['category_id'])) {
                        $query->orderBy(
                            $query->exprFormat(
                                'INTEGER(j_category_position.%s)',
                                Mage::helper('ecomdev_sphinx')->getCategoryMatch(
                                    (int)$this->_productLimitationFilters['category_id']
                                )
                            ),
                            $direction
                        );
                    } elseif ($column === '@relevance') {
                        $query->orderBy($query->expr('weight()'), $direction);
                    } elseif (strpos($column, '@') === 0 && in_array(substr($column, 1), $indexFields)) {
                        $query->orderBy(substr($column, 1), $direction);
                    } elseif ($column && isset($indexFields[sprintf('s_%s_sort', $column)])) {
                        $query->orderBy(sprintf('s_%s_sort', $column), $direction);
                    } elseif ($column && isset($indexFields[$column])) {
                        $query->orderBy($column, $direction);
                    }

                }
            }
        } else {
            if ($order === 'price') {
                $order = 'price_index_min_price_' . $this->getCustomerGroupId();
            }

            if ($order === 'position'
                && isset($indexFields['j_category_position'])
                && isset($this->_productLimitationFilters['category_id'])) {
                $query->orderBy(
                    $query->exprFormat(
                        'INTEGER(j_category_position.%s)',
                        Mage::helper('ecomdev_sphinx')->getCategoryMatch(
                            (int)$this->_productLimitationFilters['category_id']
                        )
                    ),
                    $direction
                );
            } elseif ($order && isset($indexFields[sprintf('s_%s_sort', $order)])) {
                $query->orderBy(sprintf('s_%s_sort', $order), $direction);
            } elseif ($order && isset($indexFields[$order])) {
                $query->orderBy($order, $direction);
            } elseif ($order === 'relevance') {
                $query->orderBy($query->expr('weight()'), $direction);
            }
        }


        
        if ($scope->getPageSize() && $scope->getCurrentPage()) {

            $lastItem = $scope->getPageSize() * $scope->getCurrentPage();

            if ($lastItem > $scope->getMaxMatches()) {
                $scope->setCurrentPage(floor($scope->getMaxMatches() / $scope->getPageSize()));
            }

            $this->setPageSize((int)$scope->getPageSize());
            $this->setCurPage((int)$scope->getCurrentPage());
            
            $query->limit(
                ($this->getCurPage() - 1) * $this->getPageSize(), $this->getPageSize()
            );
        }

        return $this;
    }

    /**
     * Hard code last page number, when we don't know it yet
     *
     * @return int
     */
    public function getLastPageNumber()
    {
        if ($this->_totalRecords === null) {
            return 10000;
        }
        
        return parent::getLastPageNumber();
    }

    /**
     * Loads collection data from sphinx
     * 
     * @param $items
     * @param $meta
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function loadFromSphinx($items, $meta)
    {
        Varien_Profiler::start(__METHOD__);
        $this->_beforeLoad();
        
        $this->_isCollectionLoaded = true;
        $this->_totalRecords = (int)$meta['total_found'];
        
        foreach ($items as $v) {
            $object = $this->getNewEmptyItem()
                ->setData($v);
            $this->addItem($object);
            if (isset($this->_itemsById[$object->getId()])) {
                $this->_itemsById[$object->getId()][] = $object;
            } else {
                $this->_itemsById[$object->getId()] = array($object);
            }
        }
        
        $this->_afterLoad();
        Varien_Profiler::stop(__METHOD__);
        return $this;
    }
    
    /**
     * Add search query filter
     *
     * @param string $query
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function addSearchFilter($query)
    {
        $this->_productLimitationFilters['search_query'] = $query;
        return $this;
    }

    /**
     * Collects info which attributes was selected
     * 
     * @param array|int|Mage_Core_Model_Config_Element|string $attribute
     * @param bool $joinType
     * @return $this
     */
    public function addAttributeToSelect($attribute, $joinType = false)
    {
        if (is_array($attribute)) {
            foreach ($attribute as $v) {
                $this->addAttributeToSelect($v);
            }
        } elseif ($attribute !== '*') {
            $this->_fieldsToSelect[$attribute] = $attribute;
        }
        
        return $this;
    }

    protected function _addPriceIndexFields($customerGroupId)
    {
        $this->_fieldsToSelect['tax_class_id'] = 'tax_class_id';
        $this->_fieldsToSelect['price'] = sprintf(
            'price_index_%s_%d',
            'price',
            $customerGroupId
        );
        $this->_fieldsToSelect['final_price'] = sprintf(
            'price_index_%s_%d',
            'final_price',
            $customerGroupId
        );
        $this->_fieldsToSelect['min_price'] = sprintf(
            'price_index_%s_%d',
            'min_price',
            $customerGroupId
        );
        $this->_fieldsToSelect['minimal_price'] = sprintf(
            'price_index_%s_%d',
            'minimal_price',
            $customerGroupId
        );
        $this->_fieldsToSelect['max_price'] = sprintf(
            'price_index_%s_%d',
            'max_price',
            $customerGroupId
        );

        $this->_fieldsToSelect['tier_price'] = sprintf(
            'price_index_%s_%d',
            'tier_price',
            $customerGroupId
        );

        return $this;
    }

    /**
     * Do not allow invocation of load operation
     * 
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function load($printQuery = false, $logQuery = false)
    {
        return $this;
    }

    /**
     * Do not allow invocation of load operation
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        return $this;
    }

    /**
     * No load trigger for size check
     * 
     * @return int
     */
    public function getSize()
    {
        return $this->_totalRecords;
    }


}
