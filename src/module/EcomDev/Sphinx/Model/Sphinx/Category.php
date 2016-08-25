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

        return array_slice(
            $this->fetchTree(
                $this->getCategoriesData(
                    $category['path'],
                    $category['level'] + 2,
                    $columns,
                    []
                ),
                $category['path']
            ),
            0,
            $limit
        );
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

        $query = $this->container->queryBuilder();
        $query->select(
            $query->exprFormat('GROUPBY() as %s', $query->quoteIdentifier('category_id')),
            $query->exprFormat('COUNT(*) as %s', $query->quoteIdentifier('count'))
        );

        $query->from($this->container->getIndexNames('product'))
            ->groupBy(sprintf('%s_category_ids', $prefix))
            ->orderBy('count', 'desc')
            ->match(
                's_anchor_category_ids', // Match is always against anchor category ids
                sprintf('"%s"', Mage::helper('ecomdev_sphinx')->getCategoryMatch(
                    (int)$parentCategoryId
                )),
                true
            )
            ->limit($this->treeLimit);

        $result = [];
        foreach ($query->execute() as $row) {
            $result[$row['category_id']] = $row['count'];
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
