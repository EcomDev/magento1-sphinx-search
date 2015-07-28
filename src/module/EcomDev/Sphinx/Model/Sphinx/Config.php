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

        $additionalArgs = '';
        if ($this->isRunning()) {
            $additionalArgs .= '--rotate';
        }
        
        if ($reindex) {
            $this->executeIndexerCommand(sprintf('--all %s', $additionalArgs));
        }
        
        return $this;
    }

    /**
     * Check if index data is up to date
     * If it is not, it will automatically reindex delta or main index
     * 
     * @return $this
     */
    public function controlIndexData()
    {
        $indexesToControl = array(
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY,
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT
        );

        $additionalArgs = '';
        if ($this->isRunning()) {
            $additionalArgs .= '--rotate';
        }

        $reindexAll = false;
        $reindexPerType = array(
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT => array('product', 'product_search'),
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY => array('category')
        );
        foreach ($indexesToControl as $index) {
            $rowsToIndex = $this->_getIndexConfig()->getPendingRowCount($index);
            $configLimit = (int)$this->_getConfig()->getConfig(sprintf('index_%s_merge_limit', $index));

            if (!$reindexAll && $rowsToIndex && $configLimit && $rowsToIndex < $configLimit) {
                foreach ($reindexPerType[$index] as $item) {
                    $this->reindexIndex($item . '_delta', $additionalArgs);
                    $this->mergeDeltaIndex($item, $additionalArgs);
                }
            } elseif ($rowsToIndex && $configLimit) {
                $reindexAll = true;
            }
        }

        if ($reindexAll) {
            $this->executeIndexerCommand(sprintf('--all %s', $additionalArgs));
        }
        
        return $this;
    }

    /**
     * Re-index data in index
     *
     * @param string $indexName
     * @param string $additionalArguments
     * @return $this
     */
    protected function reindexIndex($indexName, $additionalArguments)
    {
        foreach (Mage::app()->getStores(false) as $store) {
            $this->executeIndexerCommand(sprintf(
                '%s_%s %s', $indexName, $store->getId(), $additionalArguments
            ));
        }

        return $this;
    }

    /**
     * Merge delta index
     *
     * @param string $indexName
     * @param string $additionalArguments
     * @return $this
     */
    protected function mergeDeltaIndex($indexName, $additionalArguments)
    {
        foreach (Mage::app()->getStores(false) as $store) {
            $this->executeIndexerCommand(sprintf(
                '--merge %1$s_%2$s %1$s_delta_%2$s %3$s',
                $indexName, $store->getId(), $additionalArguments
            ));
        }

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
        return trim($this->_exec(sprintf('%s %s', $prefix, $command)));
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