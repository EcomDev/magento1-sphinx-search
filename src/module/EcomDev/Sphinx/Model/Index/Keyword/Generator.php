<?php

class EcomDev_Sphinx_Model_Index_Keyword_Generator
{
    /**
     * @var Traversable
     */
    private $productData;

    /**
     * Attribute codes to use for keyword generation
     * 
     * @var string[]
     */
    private $attributeCodes = [];

    /**
     * Keyword model
     *
     * @var EcomDev_Sphinx_Model_Index_Keyword
     */
    private $keywordModel;

    /**
     * Array of keywords
     *
     * @var int[]
     */
    private $keywords;

    /**
     * Keyword index
     *
     * @var string[][]
     */
    private $keywordIndex;

    /**
     * Ignore match search
     *
     * @var boolean[]
     */
    private $ignoredMatches;

    /**
     * Result of generation
     *
     * @var int[]
     */
    private $result;

    /** @var int */
    private $storeId;

    /**
     * EcomDev_Sphinx_Model_Index_Keyword_Generator constructor.
     * @param EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
     * @param EcomDev_Sphinx_Contract_ReaderInterface $reader
     */
    public function __construct(
        $productData,
        $storeId
    ) {
        $this->productData = $productData;
        $this->storeId = $storeId;
        $this->keywordModel = Mage::getSingleton('ecomdev_sphinx/index_keyword');
        mb_internal_encoding('UTF-8');
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function setKeywords($keywords)
    {
        $this->keywords = [];
        $this->keywordIndex = [];

        foreach ($keywords as $row) {
            $this->keywords[$row[0]] = $row[1];
            $keywordIndexBase = $this->getKeywordIndexBase($row[0]);

            if ($keywordIndexBase) {
                $this->keywordIndex[$keywordIndexBase][] = $row[0];
            }
        }

        return $this;
    }

    /**
     * Retrieves keywords from data row
     *
     * @param EcomDev_Sphinx_Contract_DataRowInterface $row
     *
     * @return string[]
     */
    public function getPhrase($row)
    {
        $phrase = '';

        foreach ($this->attributeCodes as $code) {
            $phrase .= ' ' . $this->getValue($code, $row);
        }

        return $phrase;
    }

    public function getValue($code, $row)
    {
        $possibleLabelKey = sprintf('s_%s_label', $code);
        $possibleStringKey = sprintf('s_%s', $code);

        return $row[$possibleLabelKey] ?? $row[$possibleStringKey] ?? $row[$code] ?? '';
    }

    public function getIntValueAsArray($code, $row)
    {
        $value = $this->getValue($code, $row);

        return array_filter(explode(',', $value));
    }

    public function getTextValueAsArray($code, $row)
    {
        $value = $this->getValue($code, $row);

        return array_filter(explode(EcomDev_Sphinx_Model_Sphinx_Config::TEXT_SEPARATOR, $value));
    }

    /**
     * Returns keywords
     *
     * @param string $phrase
     *
     * @return array
     */
    public function getKeywords($phrase)
    {
        $keywords = $this->keywordModel->extractKeywords($phrase);

        $result = [];
        foreach ($keywords as $keyword) {
            $keyword = $this->findKeyword($keyword);
            if ($keyword) {
                $result[] = $keyword;
            }
        }

        return $result;
    }

    /**
     * Return keyword matches
     *
     * @param string $keyword
     *
     * @return bool|string
     */
    private function findKeyword($keyword)
    {
        if (isset($this->keywords[$keyword])) {
            return $keyword;
        }

        if (isset($this->ignoredMatches[$keyword])) {
            return false;
        }

        $keywordBase = $this->getKeywordIndexBase($keyword);

        if (!isset($this->keywordIndex[$keywordBase])) {
            return false;
        }

        foreach ($this->keywordIndex[$keywordBase] as $match) {
            if (strpos($keyword, $match) !== false) {
                $this->keywords[$keyword] = $this->keywords[$match];
                return $keyword;
            }
        }

        $this->ignoredMatches[$keyword] = true;

        return false;
    }

    /**
     * Attribute codes to retrieve data from
     *
     * @param $codes
     */
    public function setAttributeCodes($codes)
    {
        $this->attributeCodes = $codes;
    }

    /**
     * Generates keywords and returns them back
     *
     * @param int $minWordCount
     * @param int $maxWordCount
     * @param boolean $addSku
     * @return int[]
     */
    public function generate($minWordCount, $maxWordCount, $addSku)
    {
        $this->result = [];
        
        foreach ($this->productData as $row) {
            $generatedKeywords = $this->getKeywords($this->getPhrase($row));

            $this->generateVariations(
                $generatedKeywords,
                $this->getIntValueAsArray('anchor_category_ids', $row),
                $minWordCount,
                $maxWordCount
            );

            if ($addSku) {
                $this->result[$this->getValue('sku', $row)] = [
                    'count' => 1,
                    'category_ids' => []
                ];
            }
        }

        return $this->result;
    }

    protected function generateVariations($keywords, $categoryIds, $minWordCount, $maxWordCount)
    {
        foreach (array_keys($keywords) as $index) {
            foreach (range($minWordCount - 1, $maxWordCount) as $variant) {
                if (!isset($keywords[$index - $variant])) {
                    continue;
                }

                $phrases = $this->generateKeywordVariationList($keywords, $index, $variant);
                $this->storeKeywordsWithStatistics($categoryIds, $phrases);
            }
        }

        return $this;
    }

    /**
     * Keyword index base string generation
     *
     * @param string $keyword
     * @return string
     */
    private function getKeywordIndexBase($keyword)
    {
        if (mb_strlen($keyword) < 3) {
            return false;
        }
        
        return mb_substr($keyword, 0, 3);
    }

    protected function generateKeywordVariationList($keywords, $index, $variant)
    {
        $matches = array_slice($keywords, $index - $variant, $index);
        $matchesShuffled = $matches;
        shuffle($matchesShuffled);

        $phrases = [
            implode(' ', $matches),
            implode(' ', array_reverse($matches)),
            implode(' ', $matchesShuffled),
        ];

        return $phrases;
    }

    /**
     * @param $categoryIds
     * @param $phrases
     *
     */
    protected function storeKeywordsWithStatistics($categoryIds, $phrases)
    {
        foreach ($phrases as $phrase) {
            if (!isset($this->result[$phrase])) {
                $this->result[$phrase] = [
                    'count' => 0,
                    'category_ids' => []
                ];
            }

            $this->result[$phrase]['count'] += 1;
            foreach ($categoryIds as $categoryId) {
                if (!isset($this->result[$phrase]['category_ids'][$categoryId])) {
                    $this->result[$phrase]['category_ids'][$categoryId] = 0;
                }
                $this->result[$phrase]['category_ids'][$categoryId] += 1;
            }
        }
    }
}
