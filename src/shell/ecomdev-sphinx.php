<?php

$shellDirectory = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
require_once $shellDirectory . DIRECTORY_SEPARATOR . 'abstract.php';
require_once dirname($shellDirectory) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

use Mage_Catalog_Model_Product_Visibility as Visibility;

/**
 * Shell script for generation of sphinx data
 *
 */
class EcomDev_Sphinx_Shell extends Mage_Shell_Abstract
{
    /**
     * Script action
     *
     * @var string
     */
    protected $_action;

    /**
     * Do not include Mage class via constructor
     *
     * @var bool
     */
    protected $_includeMage = false;

    /**
     * Map of arguments for shell script,
     * for making possible using shortcuts
     *
     * @var array
     */
    protected $_actionArgsMap = array(
        'export:product' => array(
            'store' => 's',
            'visibility' => 'v',
            'delta' => 'd',
            'format' => 'f',
            'output' => 'o',
            'header' => 't'
        ),
        'export:cached:product' => array(
            'store' => 's',
            'visibility' => 'v',
            'output' => 'o',
            'header' => 't'
        ),
        'export:category' => array(
            'store' => 's',
            'visibility' => 'v',
            'delta' => 'd',
            'format' => 'f',
            'output' => 'o',
            'header' => 't'
        ),
        'export:keyword' => array(
            'store' => 's',
            'format' => 'f',
            'output' => 'o'
        ),
        'export:keyword:product' => array(
            'store' => 's',
            'output' => 'o',
            'fields' => 'f',
            'header' => 't'
        ),
        'console' => array(),
        'notify:category' => array(),
        'notify:product' => array(),
        'config:index' => array(
            'output' => 'o'
        ),
        'config:daemon' => array(
            'output' => 'o'
        ),
        'create:product:cache' => array(),
        'keyword:dump' => array(
            'store' => 's',
            'output' => 'o'
        ),
        'keyword:import' => array(
            'store' => 's'
        ),
        'keyword:import:all' => array(),
        'index:all' => array(
            'ignore-keyword' => 'i',
            'prefix' => 'p'
        ),
        'index:delta' => array(),
        'index:validate' => array()
    );

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ecomdev-sphinx.php -- <action> <options>
  -h --help             Shows usage
Defined <action>s:

  export:product    Exports product index

    -s --store          Store identifier for index                           [required]
    -v --visibility     Visibility, possible values: category, search        [required]
    -d --delta          Is it a delta index?
    -f --format         Format of the output, allowed values: csv, tsv, xml  [required]
    -o --output         Output filename, by default outputs to STDOUT
    -t --header         Output header of CSV or TSV file
    
   export:cached:product    Exports cached product index

    -s --store          Store identifier for index                           [required]
    -v --visibility     Visibility, possible values: category, search        [required]
    -o --output         Output filename, by default outputs to STDOUT
    -t --header         Output header of CSV file


  export:category   Exports category index

    -s --store          Store identifier for index                           [required]
    -d --delta          Is it a delta index?
    -f --format         Format of the output, allowed values: csv, tsv, xml  [required]
    -o --output         Output file, by default outputs to STDOUT
    -t --header         Output header of CSV or TSV file

  export:keyword    Export keyword index

    -s --store          Store identifier for index                           [required]
    -f --format         Format of the output, allowed values: csv, tsv, xml  [required]
    -o --output         Output file, by default outputs to STDOUT

  export:keyword:product    Export keyword product index base

    -s --store          Store identifier for index                           [required]
    --fields            Comma separated list of fields to use            
    -o --output         Output file, by default outputs to STDOUT

  notify:category   Notifies changes in category entities to indexer

  notify:product    Notifies changes in product entities to indexer

  config:index      Generates index configuration

  config:daemon     Generates daemon configuration
  
  create:product:cache Creates product data cache for indexation

  index:all         Indexes all sphinx data

     -i --ignore-keyword No keywords flag
     -p --prefix         Reindex only indexes starting with specified prefix

  index:delta       Indexes changes to sphinx data

  keyword:dump      Dumps keywords for index

     -s --store         Store id
     -i --index         Index name. Allowed values: product_catalog, product_search, category. Default is product_search
     -o --output        Output file, by default outputs to STDOUT

  keyword:import    Imports keywords from product_search index

     -s --store         Store
     
  keyword:import:all Imports all keywords for a store

  index:validate    Validates index configuration and updates it if needed. 

  console           Opens sphinx console
USAGE;
    }
    /**
     * Parses actions for shell script
     *
     */
    protected function _parseArgs()
    {
        foreach ($_SERVER['argv'] as $index => $argument) {
            if (isset($this->_actionArgsMap[$argument])) {
                $this->_action = $argument;
                unset($_SERVER['argv'][$index]);
                break;
            }
            unset($_SERVER['argv'][$index]);
        }
        parent::_parseArgs();
    }

    /**
     * Returns a lock model
     *
     * @return EcomDev_Sphinx_Model_Lock
     */
    protected function _getLock()
    {
        return Mage::getSingleton('ecomdev_sphinx/lock');
    }

    /**
     * Retrieves arguments (with map)
     *
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed|bool
     */
    public function getArg($name, $defaultValue = false)
    {
        if (parent::getArg($name) !== false) {
            return parent::getArg($name);
        }
        if ($this->_action && isset($this->_actionArgsMap[$this->_action][$name])) {
            $value = parent::getArg($this->_actionArgsMap[$this->_action][$name]);
            if ($value === false) {
                return $defaultValue;
            }
            return $value;
        }
        return $defaultValue;
    }
    /**
     * Runs scripts itself
     *
     */
    public function run()
    {
        if ($this->_action === null) {
            die($this->usageHelp());
        }
        $reflection = new ReflectionClass(__CLASS__);
        $methodName = 'run' . uc_words($this->_action, '', ':');
        if ($reflection->hasMethod($methodName)) {
            try {
                Mage::app('admin');
                ini_set('memory_limit', '-1');
                ob_implicit_flush(1);
                $this->$methodName();
            } catch (Exception $e) {
                fwrite(STDERR, "Error: \n{$e->getMessage()}\n");
                fwrite(STDERR, "Trace: \n{$e->getTraceAsString()}\n");
                exit(1);
            }
        } else {
            die($this->usageHelp());
        }
    }

    /**
     * Config instance
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    private function getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    /**
     * Service instance
     *
     * @return EcomDev_Sphinx_Model_Index_Service
     */
    private function getService()
    {
        return Mage::getSingleton('ecomdev_sphinx/index_service');
    }

    /**
     * Service instance
     *
     * @return EcomDev_Sphinx_Model_Cron
     */
    private function getCron()
    {
        return Mage::getSingleton('ecomdev_sphinx/cron');
    }

    /**
     * Returns index configuration model
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Config_Index
     */
    private function getIndexConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/sphinx_config_index');
    }


    /**
     * Returns daemon configuration model
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Config_Daemon
     */
    private function getDaemonConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/sphinx_config_daemon');
    }


    /**
     * Returns sphinx configuration model
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Config
     */
    private function getSphinxConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/sphinx_config');
    }

    /** @return EcomDev_Sphinx_Model_Index_Cache */
    private function getIndexCache()
    {
        return Mage::getSingleton('ecomdev_sphinx/index_cache');
    }

    /**
     * Runs mysql console for sphinx daemon
     *
     * @return $this
     */
    protected function runConsole()
    {
        $command = sprintf(
            'mysql --prompt="SphinxQL> " -P%d -h%s',
            $this->getConfig()->getConfig('port', 'connection'),
            $this->getConfig()->getConfig('host', 'connection')
        );

        $descriptorSpec = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        );

        $pipes = array();
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (is_resource($process)) {
            proc_close($process);
        }
    }

    protected function runExportCategory()
    {
        $this->validateArgs([
            'store' => 'Store argument is missing',
            'format' => 'Format argument is missing'
        ]);

        $output = $this->getOutput(false);

        $store = Mage::app()->getStore($this->getArg('store'))->getId();
        $reader = $this->getService()->getReader('category');

        $updatedAt = null;

        if ($this->getArg('delta')) {
            $tmpScope = $this->getService()->getCategoryScope($store);
            $metaType = $reader->getProvider()->getMetaType($tmpScope);
            $updatedAt = $this->getConfig()->getMetaDataUpdatedAt($metaType, $store);
        }

        $scope = $this->getService()->getCategoryScope($store, $updatedAt);
        $writer = $this->getService()->getWriter($this->getArg('format'), $output);
        $writer->process($reader, $scope);
    }

    protected function runExportKeyword()
    {
        $this->validateArgs([
            'store' => 'Store argument is missing',
            'format' => 'Format argument is missing'
        ]);

        $output = $this->getOutput(false);

        $store = (int)$this->getArg('store');
        // Then we supply them to sphinx
        $reader = $this->getService()->getReader('keyword');
        $scope = $this->getService()->getKeywordScope($store);
        $writer = $this->getService()->getWriter($this->getArg('format'), $output);
        $writer->process($reader, $scope);
    }

    protected function runExportKeywordProduct()
    {
        $this->validateArgs([
            'store' => 'Store argument is missing'
        ]);

        $visibility = [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_SEARCH];
        $fields = array_filter(
            array_map('trim', explode(',', $this->getArg('fields', '')))
        );

        $output = $this->getOutput(false);
        $includeHeaders = (bool)$this->getArg('header');
        $storeId = Mage::app()->getStore($this->getArg('store'))->getId();

        if ($fields) {
            $this->getIndexCache()->exportProductsWithColumns($storeId, $visibility, $output, $fields, $includeHeaders);
        } else {
            $this->getIndexCache()->exportProducts($storeId, $visibility, $output, $includeHeaders);
        }
    }



    public function runExportProduct()
    {
        $this->validateArgs([
            'store' => 'Store argument is missing',
            'format' => 'Format argument is missing',
            'visibility' => 'Visibility argument is missing'
        ]);

        $visibilityToCode = [
            'catalog' => [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_CATALOG],
            'search' => [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_SEARCH]
        ];

        if (!isset($visibilityToCode[$this->getArg('visibility')])) {
            throw new InvalidArgumentException('Unknown visibility type');
        }

        $visibility = $visibilityToCode[$this->getArg('visibility')];

        $output = $this->getOutput(false);

        $store = Mage::app()->getStore($this->getArg('store'))->getId();

        $reader = $this->getService()->getReader('product');

        $updatedAt = null;

        if ($this->getArg('delta')) {
            $tmpScope = $this->getService()->getProductScope($store, $visibility);
            $metaType = $reader->getProvider()->getMetaType($tmpScope);
            $updatedAt = $this->getConfig()->getMetaDataUpdatedAt($metaType, $store);
        }

        $scope = $this->getService()->getProductScope($store, $visibility, $updatedAt);
        $writer = $this->getService()->getWriter($this->getArg('format'), $output);
        $this->applyHeaderOutput($writer);
        $writer->process($reader, $scope);
    }

    public function runExportCachedProduct()
    {
        $this->validateArgs([
            'store' => 'Store argument is missing',
            'visibility' => 'Visibility argument is missing'
        ]);

        $visibilityToCode = [
            'catalog' => [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_CATALOG],
            'search' => [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_SEARCH]
        ];

        if (!isset($visibilityToCode[$this->getArg('visibility')])) {
            throw new InvalidArgumentException('Unknown visibility type');
        }

        $visibility = $visibilityToCode[$this->getArg('visibility')];

        $output = $this->getOutput(false);
        $includeHeaders = (bool)$this->getArg('header');
        $storeId = Mage::app()->getStore($this->getArg('store'))->getId();

        $this->getIndexCache()->exportProducts($storeId, $visibility, $output, $includeHeaders);
    }

    public function runCreateProductCache()
    {
        $this->getIndexCache()->createProductCache();
    }

    /**
     * Notifies about changes in products
     */
    public function runNotifyProduct()
    {
        $this->getCron()->validateProductChanges();
        fwrite($this->getOutput(), 'Product changes update executed' . PHP_EOL);
    }

    /**
     * Notifies about changes in categories
     */
    public function runNotifyCategory()
    {
        $this->getCron()->validateProductChanges();
        fwrite($this->getOutput(), 'Category changes update executed' . PHP_EOL);
    }

    /**
     * Creates output instance
     *
     * @param bool $stream
     * @return resource|SplFileObject
     */
    private function getOutput($stream = true)
    {
        $output = $this->getArg('output');

        if ($output !== false && $stream) {
            $output = fopen($output, 'w');
        } elseif ($stream) {
            $output = STDOUT;
        } elseif ($output === false) {
            $output = 'php://stdout';
        }

        return $output;
    }

    /**
     * Generates index configuration
     *
     */
    public function runConfigIndex()
    {
        fwrite($this->getOutput(), $this->getIndexConfig()->render());
    }

    /**
     * Generates daemon configuration
     *
     */
    public function runConfigDaemon()
    {
        fwrite($this->getOutput(), $this->getDaemonConfig()->render());
    }


    /**
     * Reindex all sphinx data
     */
    public function runIndexAll()
    {
        if (!$this->_getLock()->lock()) {
            fwrite($this->getOutput(), 'Index process is running at the moment, please try again later');
            exit(1);
        }

        $withKeywords = !$this->getArg('ignore-keyword', false);
        $this->getSphinxConfig()->controlIndexData(
            true,
            $this->getOutput(),
            $withKeywords,
            $this->getArg('prefix', '')
        );
    }

    /**
     * Reindex all sphinx data
     */
    public function runIndexDelta()
    {
        if (!$this->_getLock()->lock()) {
            fwrite($this->getOutput(), 'Index process is running at the moment, please try again later');
            exit(1);
        }

        $this->getSphinxConfig()->controlIndexData(false, $this->getOutput());
    }

    /**
     * Reindex all sphinx data
     */
    public function runIndexValidate()
    {
        if (!$this->_getLock()->lock()) {
            fwrite($this->getOutput(), 'Index process is running at the moment, please try again later');
            exit(1);
        }

        $this->getSphinxConfig()->controlIndex();
    }

    /**
     * Dumps keywords
     *
     *
     */
    public function runKeywordDump()
    {
        $output = $this->getOutput();

        $this->validateArgs([
            'store' => 'Store argument is missing'
        ]);

        $tmpFile = tempnam(Mage::getConfig()->getVarDir('sphinx'), 'keyword');

        $this->getSphinxConfig()->keywordDump(
            (int)$this->getArg('store'),
            $tmpFile
        );

        if (!file_exists($tmpFile)) {
            new RuntimeException('There was an issue with keyword dump');
        }

        $file = fopen($tmpFile, 'r');
        stream_copy_to_stream($file, $output);
        fclose($file);
        unlink($tmpFile);
    }

    /**
     * Dumps keywords
     *
     *
     */
    public function runKeywordImport()
    {
        $output = $this->getOutput();

        $this->validateArgs([
            'store' => 'Store argument is missing'
        ]);


        $this->getSphinxConfig()->keywordImport(
            (int)$this->getArg('store')
        );

        fwrite($output, "Keywords are imported\n");
    }


    public function runKeywordImportAll()
    {
        $output = $this->getOutput();

        $collection = Mage::getResourceSingleton('ecomdev_sphinx/sphinx_config_index_collection');
        foreach ($collection->getStoreIds() as $storeId) {
            if (!$collection->isKeywordEnabled($storeId)) {
                fwrite($output, sprintf("Skip keywords for store #%s\n", $storeId));
                continue;
            }

            $this->getSphinxConfig()->keywordImport(
                (int)$storeId
            );
            fwrite($output, sprintf("Keywords are imported for store #%s\n", $storeId));
        }
    }

    /**
     * Validates arguments
     *
     * @param string[] $args
     */
    private function validateArgs($args)
    {
        foreach ($args as $code => $message) {
            if (!$this->getArg($code)) {
                throw new InvalidArgumentException($message);
            }
        }
    }

    /**
     * @param $writer
     *
     */
    private function applyHeaderOutput($writer)
    {
        if ($writer instanceof EcomDev_Sphinx_Contract_Writer_HeaderAwareInterface && $this->getArg('header')) {
            $writer->setOutputHeaders(true);
        }
    }


}

$shell = new EcomDev_Sphinx_Shell();
$shell->run();
