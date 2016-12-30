<?php

use EcomDev_Sphinx_Model_Sphinx_Container as Container;

class EcomDev_Sphinx_Model_Sphinx_Category
{
    /**
     * Sphinx container
     *
     * @var Container
     */
    private $container;

    /**
     * Tree limit
     *
     * @var int
     */
    private $treeLimit = 2000;

    /**
     * Passes container as dependency
     *
     * @param array $config
     * @throws InvalidArgumentException
     */
    public function __construct(array $config)
    {
        if (empty($config['container'])) {
            throw new InvalidArgumentException('Container is missing as dependency');
        }

        $this->container = $config['container'];

        if (!empty($config['tree_limit'])) {
            $this->treeLimit = (int)$config['tree_limit'];
        }
    }

    /**
     * Returns current category indexes
     *
     * @return string[]
     */
    private function getIndexes()
    {
        return $this->container->getIndexNames('category');
    }

    /**
     * Returns category information
     *
     * @param int $categoryId
     * @param string[] $columns
     * @return array
     */
    public function getCategoryInfo($categoryId, $columns)
    {
        $result = $this->selectColumns($this->container->queryBuilder(), $columns)
            ->from($this->getIndexes())
            ->where('id', (int)$categoryId)
            ->limit(1)
            ->execute()
            ->store();

        if ($result->getCount() === 0) {
            return [];
        }

        return $result[0];
    }

    /**
     * Returns top categories
     *
     * @param int $limit
     * @param string[] $columns
     * @return array
     */
    public function getTopCategories($limit, $columns = ['category_id', 'path', 'name', 'request_path'])
    {
        $category = $this->getCategoryInfo(
            (int)Mage::app()->getStore()->getRootCategoryId(),
            ['path', 'level']
        );

        if (!$category) {
            return [];
        }

        $topCategories = $this->fetchTree(
            $this->getCategoriesData(
                $category['path'],
                $category['level'] + 2,
                $columns,
                []
            ),
            $category['path']
        );

        if ($limit > 0) {
            return array_slice(
                $topCategories,
                0,
                $limit
            );
        }

        return $topCategories;
    }

    /**
     * @param int $parentCategoryId
     * @param bool $isDirect
     * @return int
     */
    public function getProductCount($parentCategoryId, $isDirect = false)
    {
        $prefix = 'anchor';
        if ($isDirect) {
            $prefix = 'direct';
        }

        return $this->getTopFacetValuesByItemCount(
            $parentCategoryId, sprintf('%s_category_ids', $prefix), 'COUNT(*)'
        );
    }

    /**
     * @param string $path
     * @param string $countField
     * @return string[][]
     */
    public function getTopAnchorCategoryListByItemCount($path, $countField = null, $values = null, $columns = ['category_id', 'path', 'name', 'request_path'])
    {
        $paths = explode('/', $path);
        $id = end($paths);

        $categoryCount = $this->getTopFacetValuesByItemCount(
            $id,
            'anchor_category_ids',
            $countField ? sprintf('SUM(%s)', $countField) : 'COUNT(*)',
            $values
        );

        if (!$categoryCount) {
            return $categoryCount;
        }

        array_unshift($columns, 'id');

        $childCategories = $this->getCategoriesData(
            $path,
            0,
            $columns,
            ['id' => ['in', array_map('intval', array_keys($categoryCount))]]
        );

        return array_map(function ($category) use ($categoryCount) {
            $category['count'] = $categoryCount[$category['id']];
            return $category;
        }, array_filter(
            iterator_to_array($childCategories),
            function($item) use ($path) {
                return strpos($item['path'], $path . '/') === 0;
            }
        ));
    }

    /**
     * Returns top facet value counts for parent category
     *
     * @param int $parentCategoryId
     * @param string $groupField
     * @param string $countExpression
     * @param int|null $limit
     * @param array|null $values
     *
     * @return int[]
     */
    public function getTopFacetValuesByItemCount(
        $parentCategoryId,
        $groupField,
        $countExpression,
        $limit = null,
        array $values = null,
        $exclude = false
    ) {
        $query = $this->container->queryBuilder();
        $query->select(
            $query->exprFormat('GROUPBY() as %s', $query->quoteIdentifier('facet_value')),
            $query->exprFormat('%s as %s', $countExpression, $query->quoteIdentifier('count'))
        );

        $query->from($this->container->getIndexNames('product'))
            ->groupBy($groupField)
            ->orderBy('count', 'desc')
            ->match(
                's_anchor_category_ids', // Match is always against anchor category ids
                sprintf('"%s"', Mage::helper('ecomdev_sphinx')->getCategoryMatch(
                    (int)$parentCategoryId
                )),
                true
            )
            ->limit($limit ?: $this->treeLimit);

        if ($values) {
            $query->where(
                $groupField,
                ($exclude ? 'not in' : 'in'),
                array_map('intval', $values) // IN works only with int types
            );
        }

        $result = [];
        foreach ($query->execute() as $row) {
            $result[$row['facet_value']] = $row['count'];
        }

        return $result;
    }

    /**
     * Returns category tree
     *
     * @param int $parentId
     * @param int $depth
     * @param string[] $columns
     * @param array[] $conditions
     *
     * @return array|static
     */
    public function getCategoryTree($parentId, $depth, $columns, $conditions = [])
    {
        $category = $this->getCategoryInfo($parentId, ['path', 'level']);

        if (!$category) {
            return [];
        }

        $recursionLevel  = max(0, $depth);

        return $this->fetchTree(
            $this->getCategoriesData(
                $category['path'],
                ($recursionLevel > 0 ? $category['level'] + $recursionLevel : 0),
                $columns,
                $conditions
            ),
            $category['path']
        );
    }

    /**
     * Returns query builder instance that can be fetched
     *
     * @param string $path
     * @param int $maxLevel
     * @param string[] $columns
     * @param string[][]|string[] $conditions
     * @return Traversable|array
     */
    public function getCategoriesData($path, $maxLevel, $columns, $conditions)
    {
        $query = $this->container->queryBuilder();
        $this->selectColumns($query, $columns)
            ->from($this->getIndexes())
            ->where('is_active', '=', 1)
            ->orderBy('level', 'asc')
            ->orderBy('position', 'asc')
            ->match('path', $query->expr('"^' . $query->escapeMatch($path) . '"'))
            ->limit($this->treeLimit);

        $this->applyConditions($query, $conditions);

        if ($maxLevel > 0) {
            $query->where('level', '<', $maxLevel);
        }

        return $query->execute();
    }


    /**
     * Applies conditions to query
     *
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @param array $conditions
     * @return $this
     */
    private function applyConditions($query, array $conditions)
    {
        foreach ($conditions as $column => $condition) {
            $type = '=';
            $value = $condition;

            if (is_array($condition)) {
                list($type, $value) = $condition;
            }

            $query->where($column, $type, $value);
        }

        return $this;
    }

    /**
     * Adds select statement with columns as array to query
     *
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @param array $columns
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Query_Builder
     */
    private function selectColumns($query, array $columns)
    {
        $selectColumns = [];

        foreach ($columns as $alias => $column) {
            if (is_string($alias)) {
                $selectColumns[] = $query->expr(sprintf('%s as %s', $column, $alias));
                continue;
            }

            $selectColumns[] = $column;
        }

        call_user_func_array([$query, 'select'], $selectColumns);
        return $query;
    }

    /**
     * Fetches query as a tree with expected root path as top node
     *
     * @param array|Traversable $data
     * @param string $rootPath
     * @return array
     */
    private function fetchTree($data, $rootPath)
    {
        $result = [];
        $parents = [];

        foreach ($data as $item) {
            if (dirname($item['path']) === $rootPath) {
                $item['children'] = [];
                $index = count($result);
                $result[$index] = $item;
                $parents[$item['path']] = &$result[$index];
            } elseif (isset($parents[dirname($item['path'])])) {
                $item['children'] = [];
                $index = count($parents[dirname($item['path'])]['children']);
                $parents[dirname($item['path'])]['children'][$index] = $item;
                $parents[$item['path']] = &$parents[dirname($item['path'])]['children'][$index];
            }
        }

        return $result;
    }
}
