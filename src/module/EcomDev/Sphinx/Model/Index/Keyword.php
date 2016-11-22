<?php

/**
 * Keyword model
 *
 * @method EcomDev_Sphinx_Model_Resource_Index_Keyword _getResource()
 */
class EcomDev_Sphinx_Model_Index_Keyword
    extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_keyword');
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function startImport($storeId)
    {
        $this->_getResource()->startImport($storeId);
        Mage::dispatchEvent('ecomdev_sphinx_index_keyword_import_start', ['store_id' => $storeId]);
        return $this;
    }

    /**
     * Processes a multi-dimensional array or an iterator that returns each entry as:
     *
     * [keyword, frequency]
     *
     * @param int $storeId
     * @param Traversable|array $data
     * @param int $batchSize
     * @return $this
     */
    public function importData($data, $storeId, $batchSize = 1000)
    {
        $this->startImport($storeId);

        $rows = [];
        foreach ($data as $row) {
            if (count($row) < 3) {
                continue;
            }

            list($keyword, $frequency, $categoryInfo) = $row;

            if (!$this->validateKeyword($keyword, $frequency)) {
                continue;
            }

            $trigram = implode(' ', $this->createTrigram($keyword));

            $rows[] = [
                'keyword' => $keyword,
                'store_id' => $storeId,
                'trigram_list' => $trigram,
                'frequency' => $frequency,
                'category_info' => $categoryInfo
            ];

            if (count($rows) > $batchSize) {
                $this->_getResource()->insertRecords($rows);
                $rows = [];
            }
        }

        if ($rows) {
            $this->_getResource()->insertRecords($rows);
        }

        $this->finishImport($storeId);
        return $this;
    }

    /**
     * Validates a keyword
     *
     * @param string $keyword
     * @param int $frequency
     * @return bool
     */
    public function validateKeyword($keyword, $frequency = 999)
    {
        if (trim($keyword) === '') {
            return false;
        }

        // Remove forms that are smaller 2 chars and unique
        if (mb_strlen($keyword, 'UTF-8') < 2 && $frequency < 4) {
            return false;
        }

        // Pure number, that does not look like a good search phrase
        if (preg_match('/^[0-9]+$/', $keyword)) {
            return false;
        }

        // Just white-speces and signs
        if (preg_match('/^[\s_\\-\\+]+$/', $keyword)) {
            return false;
        }

        // Internal category mapping
        if (preg_match(sprintf('/^%s$/', Mage::helper('ecomdev_sphinx')->getCategoryMatch('[0-9]+')), $keyword)) {
            return false;
        }

        return true;
    }

    /**
     * Creates a trigram from keyword
     *
     * @param string $keyword
     * @return string[]
     */
    public function createTrigram($keyword)
    {
        if (strpos($keyword, ' ') !== false) {
            $trigrams = [];

            foreach (explode(' ', $keyword) as $word) {
                // In case of non trivial word, we just keep it in trigram as full one
                if (!$this->validateKeyword($word)) {
                    $trigrams[] = $word;
                    continue;
                }

                $trigrams = array_merge($trigrams, $this->createTrigram($word));
            }

            return $trigrams;
        }

        if (mb_strlen($keyword, 'UTF-8') <= 3) {
            return [$keyword];
        }

        $trigramBase = $keyword;
        $length = mb_strlen($trigramBase, 'UTF-8');

        $trigrams = [];
        for ($i = 0; $i < ($length - 2); $i ++) {
            $trigrams[] = mb_substr($trigramBase, $i, 3, 'UTF-8');
        }

        return $trigrams;
    }

    /**
     * Suggestions for query string
     *
     * @param string $keywords
     * @param EcomDev_Sphinx_Model_Scope $scope
     * @param int $maximum
     * @param int $categoryId
     * @return string[]
     */
    public function suggestions($keywords, $scope, $maximum, $categoryId = null)
    {
        $keyword = implode(' ', $keywords);
        $suggestedKeyword = $this->findKeywordByTrigram($keyword, $scope, $maximum, $categoryId);
        return $suggestedKeyword;
    }

    /**
     * Find keyword by trigram
     *
     * @param string $keyword
     * @param EcomDev_Sphinx_Model_Scope $scope
     * @param int $categoryId
     * @return string[]
     */
    protected function findKeywordByTrigram($keyword, $scope, $limit, $categoryId)
    {
        $trigrams = implode(' ', $this->createTrigram($keyword));
        $keywordLength = strlen($keyword);
        $query = $scope->getQueryBuilder();
        $query
            ->select('keyword', $query->exprFormat('weight() as rank', $keywordLength))
            ->from($scope->getContainer()->getIndexNames('keyword'))
            ->match('*', $query->expr(sprintf('"%s"/2', $query->escapeMatch($trigrams))))
            ->where('length', 'BETWEEN', [$keywordLength - 3, $keywordLength + 20])
            ->orderBy('rank', 'desc')
            ->orderBy('frequency', 'desc')
            ->option('field_weights', ['trigram_list' => 2])
            ->limit($limit + 20);

        if ($categoryId !== null) {
            $query->select($query->exprFormat(
                'INTEGER(category_info.%s) as category_frequency',
                Mage::helper('ecomdev_sphinx')->getCategoryMatch((int)$categoryId)
            ));

            $query->where(
                'category_frequency',
                '>',
                0
            );

            $query->orderBy('category_frequency', 'asc');
        }

        $result = [];
        $keywordCount = count(explode(' ', $keyword));
        foreach ($query->execute()->store() as $item) {
            $matched = $this->extractKeywords($item['keyword']);

            if (count($matched) > $keywordCount) {
                $matchedStart = array_slice($matched, 0, $keywordCount);
                $matchedEnd = array_slice($matched, -$keywordCount);

                $levenstein = min(
                    levenshtein(implode(' ', $matchedStart), $keyword),
                    levenshtein(implode(' ', $matchedEnd), $keyword)
                );
            } else {
                $levenstein = levenshtein(implode(' ', $matched), $keyword);
            }

            $result[$item['keyword']] = $levenstein;
        }

        asort($result);

        if (count($result) > $limit) {
            $result = array_slice($result, 0, $limit);
        }

        return array_keys($result);
    }

    /**
     * Extracts keywords from phrase
     *
     * @param string $phrase
     *
     * @return array|string
     */
    public function extractKeywords($phrase)
    {
        $keywords = mb_strtolower(trim($phrase), 'UTF-8');
        $keywords = array_unique(array_filter(array_map('trim', preg_split('/[\s\\_#!\.]/', $keywords))));
        return $keywords;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function finishImport($storeId)
    {
        Mage::dispatchEvent('ecomdev_sphinx_index_keyword_import_finish', ['store_id' => $storeId]);
        $this->_getResource()->finishImport($storeId);
        return $this;
    }
}
