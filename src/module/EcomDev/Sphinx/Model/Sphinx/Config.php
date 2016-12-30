<?php

use Ssh\Authentication\PublicKeyFile as PublicKeyAuthentication;
use Ssh\Authentication;
use Ssh\Session as SshSession;
use Ssh\Configuration as SshConfiguration;

/**
 * Manage sphinx configuration
 * 
 */
class EcomDev_Sphinx_Model_Sphinx_Config
{
    const TYPE_PRODUCT_CATALOG = 'product_catalog';
    const TYPE_PRODUCT_SEARCH = 'product_search';
    const TYPE_CATEGORY = 'category';
    const TYPE_KEYWORD = 'keyword';
    const XML_PATH_KEYWORD_SIZE = 'ecomdev_sphinx/export/keyword_size';

    /**
     * Contains an instance of index config model
     * 
     * @var EcomDev_Sphinx_Model_Sphinx_Config_Index
     */
    protected $_indexConfig;

    /**
     * Contains an instance of daemon configuration
     * 
     * @var EcomDev_Sphinx_Model_Sphinx_Config_Daemon
     */
    protected $_daemonConfig;

    /**
     * Authentication instance
     * 
     * @var Authentication
     */
    protected $_sshAuthentication;

    /**
     * SSH session instance
     * 
     * @var SshSession
     */
    protected $_sshSession;

    /**
     * Path to a private key file
     * 
     * @var string
     */
    protected $_privateKeyFile;

    /**
     * Path to a public key file
     * 
     * @var string 
     */
    protected $_publicKeyFile;

    /**
     * Available types
     *
     * @var string[]
     */
    protected $_types = array(
        self::TYPE_PRODUCT_CATALOG => ['product', EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT],
        self::TYPE_PRODUCT_SEARCH => ['product_search', EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT],
        self::TYPE_CATEGORY => ['category', EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY],
        self::TYPE_KEYWORD => ['keyword', EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_KEYWORD],
    );

    /**
     * Return an instance of index configuration
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_Config_Index
     */
    protected function _getIndexConfig()
    {
        if ($this->_indexConfig === null) {
            $this->_indexConfig = Mage::getModel('ecomdev_sphinx/sphinx_config_index');
        }
        
        return $this->_indexConfig;
    }

    /**
     * Return an instance of daemon configuration
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_Config_Daemon
     */
    protected function _getDaemonConfig()
    {
        if ($this->_daemonConfig === null) {
            $this->_daemonConfig = Mage::getModel('ecomdev_sphinx/sphinx_config_daemon');
        }
        
        return $this->_daemonConfig;
    }
    
    /**
     * Returns instance of configuration object
     * 
     * @return EcomDev_Sphinx_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    /**
     * Returns an authentication object instance
     * 
     * @return Authentication|PublicKeyAuthentication
     */
    protected function _getSshAuthentication()
    {
        if ($this->_sshAuthentication === null) {
            $tmpDir = Mage::getBaseDir('tmp');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir);
            }
            
            $this->_publicKeyFile = tempnam(Mage::getBaseDir('tmp'), 'public');
            $this->_privateKeyFile = tempnam(Mage::getBaseDir('tmp'), 'private');
            
            $publicKey =  $this->_getConfig()->getConfig('ssh_publickey');
            $privateKey =  $this->_getConfig()->getConfig('ssh_privatekey');
            
            if (empty($publicKey) || empty($privateKey)) {
                throw new RuntimeException('Private and public key are not specified');
            }
            
            file_put_contents($this->_publicKeyFile, $publicKey);
            file_put_contents($this->_privateKeyFile, $privateKey);
            
            $this->_sshAuthentication = new PublicKeyAuthentication(
                $this->_getConfig()->getConfig('ssh_user'),
                $this->_publicKeyFile,
                $this->_privateKeyFile
            );
        }
        
        return $this->_sshAuthentication;
    }

    /**
     * Returns an instance of ssh session
     * 
     * @return SshSession
     */
    protected function _getSshSession()
    {
        if ($this->_sshSession === null) {
            $this->_sshSession = new SshSession($this->_getSshConfiguration(), $this->_getSshAuthentication());
        }
        
        return $this->_sshSession;
    }

    /**
     * Returns instance of ssh configuration 
     * 
     * @return SshConfiguration
     */
    protected function _getSshConfiguration()
    {
        return new SshConfiguration(
            $this->_getConfig()->getConfig('ssh_host'),
            $this->_getConfig()->getConfig('ssh_port')
        );
    }

    /**
     * Returns true if configuration files are the same
     * 
     * @return bool
     */
    public function isDaemonConfigDifferent()
    {
        return !$this->_compareChecksum(
            $this->_getConfig()->getConfig('daemon_config_path'),
            md5($this->_getDaemonConfig()->render())
        );
    }

    /**
     * Returns true if configuration files are the same
     *
     * @return bool
     */
    public function isIndexConfigDifferent()
    {
        return !$this->_compareChecksum(
            $this->_getConfig()->getConfig('index_config_path'),
            md5($this->_getIndexConfig()->render())
        );
    }

    /**
     * Updates daemon config
     * 
     * @return $this
     */
    public function updateDaemonConfig()
    {
        if ($this->isDaemonConfigDifferent()) {
            $this->_updateFileContent(
                $this->_getConfig()->getConfig('daemon_config_path'),
                $this->_getDaemonConfig()->render()
            );
        }
        
        return $this;
    }

    /**
     * Updates daemon config
     *
     * @return $this
     */
    public function updateIndexConfig()
    {
        if ($this->isIndexConfigDifferent()) {
            $this->_updateFileContent(
                $this->_getConfig()->getConfig('index_config_path'),
                $this->_getIndexConfig()->render()
            );
        }

        return $this;
    }

    /**
     * Updates a file content
     * 
     * @param string $fileName
     * @param string $content
     * @return $this
     */
    protected function _updateFileContent($fileName, $content)
    {
        if ($this->_getConfig()->getConfig('is_remote')) {
            $file = '/tmp/' . uniqid('sphinx-file');
            $this->_getSshSession()->getSftp()->write($file, $content);
            $this->_getSshSession()->getExec()->run(
                sprintf(
                    'cat %s | sudo tee %s > /dev/null',
                    $file,
                    $fileName
                )
            );
        } else {
            if ((file_exists($fileName) && is_writeable($fileName)) 
                || (is_dir(dirname($fileName)) && is_writable(dirname($fileName))) ) {
                file_put_contents($fileName, $content);
            } else {
                $file = '/tmp/' . uniqid('sphinx-file');
                file_put_contents($file, $content);
                $this->_exec(sprintf('cat %s | sudo tee %s > /dev/null', $file, $fileName));
            }
        }
        
        return $this;
    }

    /**
     * Controls the availability of the daemon
     * 
     * @return $this
     */
    public function controlDaemon()
    {
        $serviceOperation = false;
        if ($this->isManage()) {
            if ($this->isDaemonConfigDifferent()) {
                $this->updateDaemonConfig();
                $serviceOperation = 'restart';
            }

            if (!$this->isRunning()) {
                $serviceOperation = 'start';
            }
        }

        $forceReindex = false;
        if ($serviceOperation === 'start') {
            $forceReindex = true;
        }
        
        $this->controlIndex($forceReindex);
        
        if ($serviceOperation !== false) {
            $this->executeServiceCommand($serviceOperation);
        }
    }

    /**
     * Indicates if index needs to be rotated
     * 
     * @param bool $reindex
     * @return $this
     */
    public function controlIndex($reindex = false)
    {
        if ($this->isIndexConfigDifferent()) {
            $this->updateIndexConfig();
            $reindex = true;
        }

        if ($reindex) {
            $this->controlIndexData(true);
        }
        
        return $this;
    }

    /**
     * @return EcomDev_Sphinx_Model_Sphinx_Config
     */
    public function reindexAll()
    {
        return $this->controlIndexData(true);
    }

    /**
     * Check if index data is up to date
     * If it is not, it will automatically reindex delta or main index
     *
     * @param bool $forceReindex
     * @param null|resource $output
     * @param bool $indexKeywords
     * @param string $prefixFilter
     * @return $this
     */
    public function controlIndexData($forceReindex = false,
                                     $output = null,
                                     $indexKeywords = true,
                                     $prefixFilter = ''
    ) {
        $additionalArgs = '';
        if ($this->isRunning()) {
            $additionalArgs .= '--rotate';
        }

        $collection = Mage::getResourceModel('ecomdev_sphinx/sphinx_config_index_collection');

        // Keywords are enabled only when sphinx search is used
        if ($forceReindex && $indexKeywords) {
            $forceReindexList = $this->collectKeywordIndexerCodesAndImportKeywordData($collection);
        }

        list($forceReindexList, $deltaList) = $this->collectDeltaAndFullReindexBasedOnIndexSyncStatus(
            $forceReindex,
            $collection,
            $forceReindexList
        );

        $forceReindexList = $this->filterIndexList($forceReindexList, $prefixFilter);
        $deltaList = $this->filterIndexList($deltaList, $prefixFilter);

        if ($deltaList) {
            $additionalFullReindex = $this->mergeIndexes(
                $deltaList,
                $additionalArgs,
                $output
            );

            if ($additionalFullReindex) {
                $forceReindexList = array_merge($forceReindexList, $additionalFullReindex);
            }
        }

        if ($forceReindexList) {
            $this->reindexIndexes($forceReindexList, $additionalArgs, $output);
        }

        return $this;
    }

    /**
     * @param string[][] $deltaList
     * @param string $additionalArgs
     * @param null|resource $output
     * @return string[]
     */
    public function mergeIndexes($deltaList, $additionalArgs, $output = null)
    {
        $toFullReindex = [];

        foreach ($deltaList as $info) {
            list($deltaIndex, $mainIndex, $storeId) = $info;
            if (!$this->checkIndex($mainIndex, $storeId, $output)) {
                $toFullReindex[] = [$mainIndex, $storeId];
                continue;
            }

            $result = $this->reindexIndex($deltaIndex, $storeId, $additionalArgs, $output);
            if (!$result || !$this->checkIndex($deltaIndex, $storeId, $output)) {
                $toFullReindex[] = [$mainIndex, $storeId];
                continue;
            }

            if (!$this->mergeIndex($deltaIndex, $mainIndex, $storeId, $additionalArgs, $output)) {
                $toFullReindex[] = [$mainIndex, $storeId];
            }
        }

        return $toFullReindex;
    }

    /**
     * Re-index data in index
     *
     * @param string $indexName
     * @param string $additionalArguments
     * @param int $storeId
     * @param null|resource $output
     * @return bool
     */
    protected function reindexIndex($indexName, $storeId, $additionalArguments, $output = null)
    {
        $result = $this->executeIndexerCommand(sprintf(
            '%s_%s %s', $indexName, $storeId, $additionalArguments
        ), true);

        if (is_resource($output)) {
            fwrite($output, $result[0]);
        }

        if ($result[1] === 0 && strpos($additionalArguments, '--rotate') !== false) {
            $this->waitForRotate($indexName, $storeId, $output);
        }

        return $result[1] == 0;
    }

    /**
     * Re-index data in index
     *
     * @param string[] $indexNames
     * @param string $additionalArguments
     * @param null|resource $output
     * @return $this
     */
    public function reindexIndexes($indexNames, $additionalArguments, $output = null)
    {
        if (strpos($additionalArguments, '--rotate') !== false) {
            $additionalArguments .= ' --sighup-each';
        }

        $renderedIndexNames = [];

        foreach ($indexNames as $info) {
            list($name, $storeId) = $info;
            $renderedIndexNames[] = sprintf('%s_%s', $name, $storeId);
        }

        $response = $this->executeIndexerCommand(sprintf(
            '%s %s', implode(' ', $renderedIndexNames), $additionalArguments
        ));


        if (strpos($additionalArguments, '--rotate') !== false) {
            foreach ($indexNames as $info) {
                list($name, $storeId) = $info;
                $this->waitForRotate($name, $storeId, $output);
            }
        }

        if (is_resource($output)) {
            fwrite($output, $response);
        }

        return $this;
    }

    /**
     * Dump keywords for index
     *
     * @param string $type
     * @param int $storeId
     * @param string|resource $filePath
     * @return bool
     */
    public function keywordDump($storeId, $filePath, $limit = 100000)
    {
        $indexName = sprintf('product_%s', $storeId);

        $tmpConfiguration = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'index-config');
        $originalKeywordsFile = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'keyword');

        file_put_contents(
            $tmpConfiguration,
            Mage::getSingleton('ecomdev_sphinx/sphinx_config_keyword_index')->render()
        );

        $outputFile = $filePath;
        if (is_resource($filePath)) {
            $outputFile = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'result_keyword');
        }

        $this->executeIndexerCommand(
            sprintf('--buildstops %s %d --buildfreqs %s', $originalKeywordsFile, $limit, $indexName),
            false,
            $tmpConfiguration
        );

        unlink($tmpConfiguration);

        if (!file_exists($originalKeywordsFile)) {
            new RuntimeException('There was an issue with keyword dump');
        }

        $keywordModel = Mage::getSingleton('ecomdev_sphinx/index_keyword');

        $reader = \League\Csv\Reader::createFromPath($originalKeywordsFile);
        $reader->setDelimiter(' ');
        $reader->addFilter(function ($row) use ($keywordModel) {
            return $keywordModel->validateKeyword($row[0], $row[1]);
        });
        
        $generator = Mage::getSingleton('ecomdev_sphinx/index_service')->getKeywordGenerator($storeId);
        $generator->setKeywords($reader);
        unset($reader);
        unlink($originalKeywordsFile);
        $generator->setAttributeCodes(['category_names', 'name', 'manufacturer']);

        \Ajgl\Csv\Rfc\CsvRfcWriteStreamFilter::register();
        $writer = \League\Csv\Writer::createFromPath($outputFile, 'w');
        $writer->appendStreamFilter($writer);

        foreach ($generator->generate(2, 4, true) as $keyword => $info) {
            $writer->insertOne([$keyword, $info['count'], json_encode((object)$info['category_ids'])]);
        }

        if (is_resource($filePath)) {
            $tmpFileHandle = fopen($outputFile, 'r');
            stream_copy_to_stream($tmpFileHandle, $filePath);
            fclose($tmpFileHandle);
            unlink($outputFile);
        }

        return $this;
    }

    /**
     * Import keywords into database
     *
     * @param int $storeId
     * @param int $limit
     * @return bool
     */
    public function keywordImport($storeId, $limit = null)
    {
        if ($limit === null) {
            $limit = (int)Mage::getStoreConfig(self::XML_PATH_KEYWORD_SIZE);
        }

        if ($limit <= 0) {
            $limit = 5000;
        }

        $outputFile = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'keyword_import');

        $this->keywordDump($storeId, $outputFile, $limit);
        
        $csv = \League\Csv\Reader::createFromPath($outputFile);

        /** @var EcomDev_Sphinx_Model_Index_Keyword $keyword */
        $keyword = Mage::getModel('ecomdev_sphinx/index_keyword');
        $keyword->importData($csv, $storeId);

        if (file_exists($outputFile)) {
            unlink($outputFile);
        }

        return $this;
    }


    /**
     * Merge delta index
     *
     * @param string $sourceIndex
     * @param string $targetIndex
     * @param int $storeId
     * @param string $additionalArguments
     * @param null|resource $output
     * @return bool
     */
    protected function mergeIndex($sourceIndex, $targetIndex, $storeId, $additionalArguments, $output = null)
    {
        $result = $this->executeIndexerCommand(sprintf(
            '--merge %1$s_%3$s %2$s_%3$s %4$s',
            $targetIndex, $sourceIndex, $storeId, $additionalArguments
        ), true);

        if (is_resource($output)) {
            fwrite($output, $result[0]);
        }

        if ($result[1] === 0 && strpos($additionalArguments, '--rotate') !== false) {
            $this->waitForRotate($targetIndex, $storeId, $output);
        }

        return $result[1] == 0;
    }

    /**
     * Checks index data consistency
     *
     * @param string $indexName
     * @param int $storeId
     * @param null|resource $output
     * @return bool
     */
    protected function checkIndex($indexName, $storeId, $output = null)
    {
        $result = $this->executeIndexToolCommand(
            sprintf(
                '--check %1$s_%2$s',
                $indexName, $storeId
            ),
            true
        );

        if (is_resource($output)) {
            fwrite($output, $result[0]);
        }

        return $result[1] == 0;
    }

    /**
     * Validates that everything is fine with index data
     *
     * @return $this
     */
    public function validateData()
    {
        $update = Mage::getSingleton('ecomdev_sphinx/update');
        $update->notify('category');
        $update->notify('product');
        return $this;
    }

    /**
     * Check if daemon is running
     * 
     * @return bool
     */
    public function isRunning()
    {
        if ($this->isManage()) {
            return $this->executeServiceCommand('status > /dev/null 2>&1 && echo "ok" || echo "not"') === 'ok';
        }
        
        return true;
    }

    /**
     * Execute service command
     * 
     * @param string $command
     * @return string
     */
    protected function executeServiceCommand($command)
    {
        $prefix = $this->_getConfig()->getConfig('daemon_command');
        return trim($this->_exec(sprintf('%s %s', $prefix, $command)));
    }

    /**
     * Executes a command
     * 
     * @param string $command
     * @return string
     */
    public function _exec($command) 
    {
        if ($this->_getConfig()->getConfig('is_remote')) {
            $exec = $this->_getSshSession()->getExec();
            try {
                $result = $exec->run(
                    $command
                );
            } catch (RuntimeException $e) {
                $result = $e->getMessage();
            }
        } else {
            $result = shell_exec($command);
        }
        
        return $result;
    }

    /**
     * Executes a command
     *
     * @param string $command
     * @return string
     */
    public function _execWithExitCode($command)
    {
        if ($this->_getConfig()->getConfig('is_remote')) {
            $exec = $this->_getSshSession()->getExec();
            try {
                $result = $exec->run(
                    $command
                );

                return [$result, 0];
            } catch (RuntimeException $e) {
                return [$e->getMesssage(), $e->getCode()];
            }
        }

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        return [implode(PHP_EOL, $output), $exitCode];
    }


    /**
     * Execute service command
     * 
     * @param string $command
     * @param bool $returnExitCode
     * @param string $customConfigFile
     * @return string|string[]
     */
    protected function executeIndexerCommand($command, $returnExitCode = false, $customConfigFile = null)
    {
        $prefix = $this->_getConfig()->getConfig('indexer_command');

        if ($customConfigFile !== null) {
            $prefix = str_replace(
                $this->_getConfig()->getConfig('daemon_config_path'),
                $customConfigFile,
                $prefix
            );
        }

        if ($returnExitCode) {
            return $this->_execWithExitCode(sprintf('%s %s', $prefix, $command));
        }

        return $this->_exec(sprintf('%s %s', $prefix, $command));
    }

    /**
     * Execute service command
     *
     * @param string $command
     * @param bool $returnExitCode
     * @return string|string[]
     */
    protected function executeIndexToolCommand($command, $returnExitCode = false)
    {
        $prefix = $this->_getConfig()->getConfig('indextool_command');

        if ($returnExitCode) {
            return $this->_execWithExitCode(sprintf('%s %s', $prefix, $command));
        }

        return $this->_exec(sprintf('%s %s', $prefix, $command));
    }

    /**
     * Returns true if daemon should be controlled by Magento
     * 
     * @return bool
     */
    public function isManage()
    {
        return $this->_getConfig()->getConfig('manage') == '1';
    }
    
    /**
     * Checks file checksum with expected on remote host
     * 
     * @param string $file
     * @param string $expectedChecksum
     * @return bool
     */
    protected function _compareChecksum($file, $expectedChecksum)
    {
        $command = sprintf(
            'echo "%s  %s" | md5sum -c - 2>/dev/null',
            $expectedChecksum, 
            $file
        );

        $checksum = $this->_exec($command);
        return strpos($checksum, 'OK') !== false;
    }
    
    /**
     * Destructs created key files
     * 
     * 
     */
    public function __destruct()
    {
        if ($this->_publicKeyFile !== null) {
            unlink($this->_publicKeyFile);
        }
        
        if ($this->_privateKeyFile !== null) {
            unlink($this->_privateKeyFile);
        }
    }

    /**
     * Wait for rotation of indexer
     *
     * @param string $indexName
     * @param string $storeId
     * @param null $output
     * @param int $attempts
     * @return $this
     */
    public function waitForRotate($indexName, $storeId, $output = null, $attempts = 20)
    {
        $expectedFileName = sprintf(
            '%s_%s.new.', $indexName, $storeId
        );

        $command = sprintf(
            'ls %s | grep "%s"',
            $this->_getConfig()->getIndexPath(),
            $expectedFileName
        );

        while ($attempts > 0) {
            $result = $this->_execWithExitCode($command);
            if ($result[1] !== 0) {
                break;
            }

            if (is_resource($output)) {
                fwrite($output, 'Waiting for rotate process to finish, as such files are found: ' . PHP_EOL);
                fwrite($output, $result[0]);
            }

            $attempts--;
            sleep(1);
        }

        return $this;
    }

    /**
     * @param $collection
     *
     * @return array
     */
    private function collectKeywordIndexerCodesAndImportKeywordData($collection)
    {
        $forceReindexList = [];
        foreach ($collection as $index) {
            if ($index->getCode() === self::TYPE_KEYWORD) {
                $forceReindexList[] = [$this->_types[$index->getCode()][0], (int)$index->getStoreId()];
            }
            $this->keywordImport((int)$index->getStoreId());
        }
        return $forceReindexList;
    }

    /**
     * @param $type
     * @param $item
     *
     * @return bool
     */
    private function calculateSphinxDeltaUpdatePossibilities($type, $item)
    {
        $configLimit = (int)$this->_getConfig()->getConfig(sprintf('index_%s_merge_limit', $type));
        $indexedRows = $item->getData('indexed_rows');
        $pendingRows = $item->getData('pending_rows');

        $isOutOfSync = ($pendingRows > 0 && $configLimit > 0 && $pendingRows < $configLimit);
        $isCheaperFullReindex = $pendingRows < ($indexedRows / 2);

        return [$isOutOfSync, $isCheaperFullReindex];
    }

    /**
     * @param $forceReindex
     * @param $collection
     * @param $forceReindexList
     * @return array
     */
    private function collectDeltaAndFullReindexBasedOnIndexSyncStatus($forceReindex, $collection, $forceReindexList)
    {
        $deltaList = [];

        foreach ($collection as $item) {
            if (!isset($this->_types[$item->getCode()])) {
                continue;
            }

            if ($item->getCode() === self::TYPE_KEYWORD) {
                continue;
            }

            list($indexName, $type) = $this->_types[$item->getCode()];

            $storeId = (int)$item->getStoreId();

            list($isOutOfSync, $isCheaperFullReindex) = $this->calculateSphinxDeltaUpdatePossibilities($type, $item);

            if ($forceReindex || ($isOutOfSync && $isCheaperFullReindex)) {
                $forceReindexList[] = [$indexName, $storeId];
                continue;
            }

            if ($isOutOfSync) {
                $deltaList[] = [$indexName . '_delta', $indexName, $storeId];
            }
        }
        return array($forceReindexList, $deltaList);
    }


    private function filterIndexList($indexList, $prefixFilter)
    {
        if (!$prefixFilter) {
            return $indexList;
        }

        return array_filter($indexList, function ($item) use ($prefixFilter) {
            return strpos($item[0], $prefixFilter) === 0;
        });
    }
}
