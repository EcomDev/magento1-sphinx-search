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
     * Check if index data is up to date
     * If it is not, it will automatically reindex delta or main index
     * 
     * @return $this
     */
    public function controlIndexData($forceReindex = false)
    {
        $additionalArgs = '';
        if ($this->isRunning()) {
            $additionalArgs .= '--rotate';
        }

        $collection = Mage::getResourceModel('ecomdev_sphinx/sphinx_config_index_collection');
        $forceReindexList = [];

        foreach ($collection as $item) {
            if (!isset($this->_types[$item->getCode()])) {
                continue;
            }

            list($indexName, $type) = $this->_types[$item->getCode()];
            $configLimit = (int)$this->_getConfig()->getConfig(sprintf('index_%s_merge_limit', $type));
            $indexedRows = $item->getData('indexed_rows');
            $pendingRows = $item->getData('pending_rows');
            $storeId = (int)$item->getStoreId();

            if (!$forceReindex && $configLimit && $pendingRows && $configLimit && $pendingRows < $configLimit) {
                $this->reindexIndex($indexName . '_delta', $additionalArgs, $storeId);
                $this->mergeDeltaIndex($indexName, $additionalArgs, $storeId);
            } elseif ($forceReindex || !$indexedRows || ($configLimit && ($pendingRows > $configLimit))) {
                $forceReindexList[] = [$indexName, $storeId];
            }
        }

        if ($forceReindexList) {
            $this->reindexIndexes($forceReindexList, $additionalArgs);
        }

        return $this;
    }

    /**
     * Re-index data in index
     *
     * @param string $indexName
     * @param string $additionalArguments
     * @param int $storeId
     * @return $this
     */
    protected function reindexIndex($indexName, $additionalArguments, $storeId)
    {
        $this->executeIndexerCommand(sprintf(
            '%s_%s %s', $indexName, $storeId, $additionalArguments
        ));

        return $this;
    }

    /**
     * Re-index data in index
     *
     * @param string[] $indexNames
     * @param string $additionalArguments
     * @return $this
     */
    protected function reindexIndexes($indexNames, $additionalArguments)
    {
        if (strpos($additionalArguments, '--rotate') !== false) {
            $additionalArguments .= ' --sighup-each';
        }

        $renderedIndexNames = [];

        foreach ($indexNames as $info) {
            list($name, $storeId) = $info;
            $renderedIndexNames[] = sprintf('%s_%s', $name, $storeId);
        }

        $this->executeIndexerCommand(sprintf(
            '%s %s', implode(' ', $renderedIndexNames), $additionalArguments
        ));

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
    public function keywordDump($type, $storeId, $filePath, $limit = 100000)
    {
        $indexPrefix = $this->_types[$type][0];

        $indexName = sprintf('%s_%s', $indexPrefix, $storeId);

        $outputFile = $filePath;
        if (is_resource($filePath)) {
            $outputFile = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'keyword');
        }

        $this->executeIndexerCommand(sprintf('--buildstops %s %d --buildfreqs %s', $outputFile, $limit, $indexName));

        if (!file_exists($outputFile)) {
            new RuntimeException('There was an issue with keyword dump');
        }

        if (is_resource($filePath)) {
            $tmpFileHandle = fopen($outputFile, 'r');
            stream_copy_to_stream($tmpFileHandle, $outputFile);
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
    public function keywordImport($storeId, $limit = 100000)
    {
        $type = self::TYPE_PRODUCT_SEARCH;

        $outputFile = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'keyword_import');

        $this->keywordDump($type, $storeId, $outputFile);

        $csv = \League\Csv\Reader::createFromPath($outputFile);
        $csv->setDelimiter(' ');

        /** @var EcomDev_Sphinx_Model_Index_Keyword $keyword */
        $keyword = Mage::getModel('ecomdev_sphinx/index_keyword');
        $keyword->importData($csv, $storeId);

        return $this;
    }


    /**
     * Merge delta index
     *
     * @param string $indexName
     * @param string $additionalArguments
     * @param int $storeId
     * @return $this
     */
    protected function mergeDeltaIndex($indexName, $additionalArguments, $storeId)
    {
        $this->executeIndexerCommand(sprintf(
            '--merge %1$s_%2$s %1$s_delta_%2$s %3$s',
            $indexName, $storeId, $additionalArguments
        ));

        return $this;
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
            $result = $exec->run(
                $command
            );
        } else {
            $result = shell_exec($command);
        }
        
        return $result;
    }
    
    /**
     * Execute service command
     * 
     * @param string $command
     * @return string
     */
    protected function executeIndexerCommand($command)
    {
        $prefix = $this->_getConfig()->getConfig('indexer_command');
        $trim = $this->_exec(sprintf('%s %s', $prefix, $command));
        return trim($trim);
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
    
}
