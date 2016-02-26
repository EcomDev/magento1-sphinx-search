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
            if (count($row) < 2) {
                continue;
            }

            list($keyword, $frequency) = $row;

            if (!$this->validateKeyword($keyword, $frequency)) {
                continue;
            }

            $trigram = implode(' ', $this->createTrigram($keyword));

            $rows[] = [
                'keyword' => $keyword,
                'store_id' => $storeId,
                'trigram_list' => $trigram,
                'frequency' => $frequency
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
    private function validateKeyword($keyword, $frequency = 999)
    {
        if (trim($keyword) === '') {
            return false;
        }

        // Remove forms that are bigger smaller 3 chars and non unique
        if (strlen($keyword) < 3 && $frequency > 1) {
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
        if (preg_match('/^cat_[0-9]+$/', $keyword)) {
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
        if (strlen($keyword) < 3) {
            return [$keyword];
        }

        $trigramBase = '__' . $keyword . '__';
        $length = strlen($trigramBase);

        $trigrams = [];
        for ($i = 0; $i < ($length - 2); $i ++) {
            $trigrams[] = substr($trigramBase, $i, 3);
        }

        return $trigrams;
    }

    /**
     * Suggestions for query string
     *
     * @param string $keywords
     * @param EcomDev_Sphinx_Model_Scope $scope
     * @return string[]
     */
    public function suggestions($keywords, $scope)
    {
        $keyword = array_pop($keywords);

        $suggestedKeyword = $this->completeKeyword($keyword, $scope);
        $suggestedKeyword = $this->findKeywordByTrigram($suggestedKeyword, $scope);

        $result = [];

        foreach ($suggestedKeyword as $keyword) {
            $result[] = implode(' ', array_merge($keywords, [$keyword]));
        }

        return $result;
    }

    /**
     * Find keyword by starting letters
     *
     * @param string $keyword
     * @param EcomDev_Sphinx_Model_Scope $scope
     * @return string[]
     */
    protected function completeKeyword($keyword, $scope)
    {
        if (strlen($keyword) > 5) {
            return $keyword;
        }

        $query = $scope->getQueryBuilder();

        $query->select('keyword')
            ->from($scope->getContainer()->getIndexNames('keyword'))
            ->match([], $query->expr(sprintf('%s*', $query->escapeMatch($keyword))))
            ->where('length', 'BETWEEN', [strlen($keyword), strlen($keyword) + 5])
            ->orderBy($query->expr('weight()'), 'desc')
            ->orderBy('frequency', 'desc')
            ->limit(1);

        $result = [];
        foreach ($query->execute()->store() as $item) {
            return $item['keyword'];
        }

        return $keyword;
    }

    /**
     * Find keyword by trigram
     *
     * @param string $keyword
     * @param EcomDev_Sphinx_Model_Scope $scope
     * @return string[]
     */
    protected function findKeywordByTrigram($keyword, $scope)
    {
        $trigrams = implode(' ', $this->createTrigram($keyword));
        $keywordLength = strlen($keyword);
        $query = $scope->getQueryBuilder();
        $query
            ->select('keyword', $query->exprFormat('weight()+2-abs(length-%d) as rank', $keywordLength))
            ->from($scope->getContainer()->getIndexNames('keyword'))
            ->match('trigram_list', $query->expr(sprintf('"%s"/2', $query->escapeMatch($trigrams))))
            ->where('length', 'BETWEEN', [$keywordLength - 2, $keywordLength + 5])
            ->orderBy('rank', 'desc')
            ->orderBy('frequency', 'desc')
            ->option('ranker', 'wordcount')
            ->option('field_weights', ['trigram_list' => 2])
            ->limit(30);

        $result = [];
        foreach ($query->execute()->store() as $item) {
            $levenstein = levenshtein($item['keyword'], $keyword);
            $result[$item['keyword']] = $levenstein;
        }

        asort($result);

        if ($result > 10) {
            array_slice($result, 0, 10);
        }

        return array_keys($result);
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
