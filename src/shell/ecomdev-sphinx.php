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
            'output' => 'o'
        ),
        'export:category' => array(
            'store' => 's',
            'visibility' => 'v',
            'delta' => 'd',
            'format' => 'f',
            'output' => 'o'
        ),
        'export:keyword' => array(
            'store' => 's',
            'format' => 'f',
            'output' => 'o'
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
        'keyword:dump' => array(
            'store' => 's',
            'index' => 'i',
            'output' => 'o'
        ),
        'keyword:import' => array(
            'store' => 's',
            'index' => 'i',
            'output' => 'o'
        )
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

    -s --store          Store identifier for index                      [required]
    -v --visibility     Visibility, possible values: category, search   [required]
    -d --delta          Is it a delta index?
    -f --format         Format of the output, allowed values: csv, tsv, xml  [required]
    -o --output         Output filename, by default outputs to STDOUT

  export:category   Exports category index

    -s --store          Store identifier for index                      [required]
    -d --delta          Is it a delta index?
    -f --format         Format of the output, allowed values: csv, tsv, xml  [required]
    -o --output         Output file, by default outputs to STDOUT

  export:keyword    Export keyword index

    -s --store          Store identifier for index                      [required]
    -f --format         Format of the output, allowed values: csv, tsv, xml  [required]
    -o --output         Output file, by default outputs to STDOUT


  notify:category   Notifies changes in category entities to indexer

  notify:product    Notifies changes in product entities to indexer

  config:index      Generates index configuration

  keyword:dump      Dumps keywords for index

     -s --store         Store id
     -i --index         Index name. Allowed values: product_catalog, product_search, category. Default is product_search
     -o --output        Output file, by default outputs to STDOUT

  keyword:import    Imports keywords from product_search index

     -s --store         Store

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
     */
    private function getIndexConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/sphinx_config_index');
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

        $store = Mage::app()->getStore($this->getArg('store'))->getId();
        // First we import all keywords
        $this->getSphinxConfig()->keywordImport((int)$store);

        // Then we supply them to sphinx
        $reader = $this->getService()->getReader('keyword');
        $scope = $this->getService()->getKeywordScope($store);
        $writer = $this->getService()->getWriter($this->getArg('format'), $output);
        $writer->process($reader, $scope);
    }

    protected function runExportProduct()
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
        $writer->process($reader, $scope);
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
            $this->getArg('index', 'product_search'),
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
}

$shell = new EcomDev_Sphinx_Shell();
$shell->run();