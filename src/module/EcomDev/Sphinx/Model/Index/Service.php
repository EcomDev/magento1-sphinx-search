<?php

use Mage_Catalog_Model_Product as Product;
use Mage_Catalog_Model_Category as Category;

class EcomDev_Sphinx_Model_Index_Service
{
    const XML_PATH_BATCH_SIZE = 'ecomdev_sphinx/export/reader_batch_size';

    protected $configurationProviders = [
        'product' => [
            'ecomdev_sphinx/index_field_provider_product_attribute_system',
            'ecomdev_sphinx/index_field_provider_product_attribute_price',
            'ecomdev_sphinx/index_field_provider_product_attribute_option',
            'ecomdev_sphinx/index_field_provider_product_attribute_regular',
            'ecomdev_sphinx/index_field_provider_product_attribute_virtual'
        ],
        'category' => [
            'ecomdev_sphinx/index_field_provider_category'
        ],
        'keyword' => [
            'ecomdev_sphinx/index_field_provider_keyword'
        ]
    ];

    protected $readerProviders = [
        'product' => 'ecomdev_sphinx/index_reader_provider_product',
        'category' => 'ecomdev_sphinx/index_reader_provider_category',
        'keyword' => 'ecomdev_sphinx/index_reader_provider_keyword'
    ];

    protected $readerPlugins = [
        'product' => [
            [10, 'ecomdev_sphinx/index_reader_plugin_attribute_static', [Product::ENTITY]],
            [20, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Product::ENTITY, 'int']],
            [30, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Product::ENTITY, 'varchar']],
            [40, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Product::ENTITY, 'text']],
            [50, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Product::ENTITY, 'decimal']],
            [60, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Product::ENTITY, 'datetime']],
            [70, 'ecomdev_sphinx/index_reader_plugin_category'],
            [80, 'ecomdev_sphinx/index_reader_plugin_price'],
            [90, 'ecomdev_sphinx/index_reader_plugin_stock', [1]],
            [100, 'ecomdev_sphinx/index_reader_plugin_url', ['product/%d']]
        ],
        'category' => [
            [10, 'ecomdev_sphinx/index_reader_plugin_attribute_static', [Category::ENTITY]],
            [20, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Category::ENTITY, 'int']],
            [30, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Category::ENTITY, 'varchar']],
            [40, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Category::ENTITY, 'text']],
            [50, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Category::ENTITY, 'decimal']],
            [60, 'ecomdev_sphinx/index_reader_plugin_attribute_eav', [Category::ENTITY, 'datetime']],
            [70, 'ecomdev_sphinx/index_reader_plugin_url', ['category/%d']]
        ],
        'keyword' => []
    ];

    protected $writers = [
        'tsv' => 'ecomdev_sphinx/index_writer_tsv',
        'xml' => 'ecomdev_sphinx/index_writer_xml',
        'csv' => 'ecomdev_sphinx/index_writer_csv'
    ];

    /**
     * Configuration model per type
     *
     * @var EcomDev_Sphinx_Contract_ConfigurationInterface[]
     */
    private $configuration = [];

    /**
     * Returns configuration instance for type
     *
     * @param $type
     * @return EcomDev_Sphinx_Contract_ConfigurationInterface
     */
    public function getConfiguration($type)
    {
        if (!isset($this->configuration[$type])) {
            $this->configuration[$type] = $this->createModel('ecomdev_sphinx/index_configuration');
            foreach ($this->getConfigurationProviders($type) as $provider) {
                /** @var EcomDev_Sphinx_Contract_FieldProviderInterface $provider */
                $provider = $this->createModel($provider, [Mage::getSingleton('ecomdev_sphinx/config')]);
                $this->configuration[$type]->addFieldProvider($provider);
            }
        }

        return $this->configuration[$type];
    }

    /**
     * List of configuration providers
     *
     * @param string $type
     * @return string[]
     */
    private function getConfigurationProviders($type)
    {
        $container = new stdClass();
        $container->providers = $this->configurationProviders[$type];
        $container->type = $type;

        Mage::dispatchEvent(
            'ecomdev_sphinx_index_service_configuration_providers',
            ['container' => $container, 'type' => $type]
        );

        return $container->providers;
    }

    /**
     * Returns plugin container
     *
     * @return EcomDev_Sphinx_Contract_Reader_PluginContainerInterface
     */
    public function getPluginContainer()
    {
        return $this->createModel('ecomdev_sphinx/index_reader_plugin_container');
    }

    /**
     * Returns plugin container
     *
     * @return EcomDev_Sphinx_Contract_DataRowFactoryInterface
     */
    public function getDataRowFactory()
    {
        return $this->createModel('ecomdev_sphinx/index_data_row_factory');
    }

    /**
     * Returns provider instance for reader
     *
     * @param string $type
     * @return EcomDev_Sphinx_Contract_Reader_ProviderInterface
     */
    public function getProvider($type)
    {
        return $this->createResourceModel($this->readerProviders[$type]);
    }

    /**
     * Returns reader for a specified type of object
     *
     * @param string $type
     * @return EcomDev_Sphinx_Contract_ReaderInterface
     */
    public function getReader($type)
    {
        /** @var EcomDev_Sphinx_Contract_ReaderInterface $reader */
        $reader = $this->createModel(
            'ecomdev_sphinx/index_reader',
            [$this->getPluginContainer(), $this->getDataRowFactory(), $this->getProvider($type)]
        );

        if (Mage::getStoreConfig(self::XML_PATH_BATCH_SIZE)) {
            $reader->setBatchSize((int)Mage::getStoreConfig(self::XML_PATH_BATCH_SIZE));
        }

        if ($reader && isset($this->readerPlugins[$type])) {
            foreach ($this->readerPlugins[$type] as $pluginInfo) {
                $alias = $pluginInfo[1];
                $arguments = isset($pluginInfo[2]) ? $pluginInfo[2] : [];
                $priority = $pluginInfo[0];
                /** @var EcomDev_Sphinx_Contract_Reader_PluginInterface $plugin */
                $plugin = $this->createResourceModel($alias, $arguments);
                $reader->addPlugin($plugin, $priority);
            }
        }

        Mage::dispatchEvent('ecomdev_sphinx_index_service_reader', ['reader' => $reader, 'type' => $type]);
        return $reader;
    }

    /**
     * Returns instance of data writer
     *
     * @param string $format
     * @param string|resource $output
     * @return EcomDev_Sphinx_Contract_WriterInterface
     */
    public function getWriter($format, $output)
    {
        return $this->createModel($this->writers[$format], [$output]);
    }

    /**
     * Returns a scope instance for a product
     *
     * @param int $storeId
     * @param int[] $visibility
     * @param null|string $updatedAt
     * @return EcomDev_Sphinx_Model_Index_Reader_Scope
     */
    public function getProductScope($storeId, $visibility, $updatedAt = null)
    {
        $filters = [];
        $filters[] = $this->createResourceModel(
            'ecomdev_sphinx/index_reader_filter',
            ['store_id', $storeId]
        );
        $filters[] = $this->createResourceModel(
            'ecomdev_sphinx/index_reader_filter',
            ['visibility', $visibility]
        );

        if ($updatedAt !== null) {
            $filters[] = $this->createResourceModel(
                'ecomdev_sphinx/index_reader_filter',
                ['updated_at', $updatedAt]
            );
        }

        $scope = $this->createModel(
            'ecomdev_sphinx/index_reader_scope',
            [$filters, $this->getConfiguration('product')]
        );

        return $scope;
    }

    /**
     * Scope of the category
     *
     * @param int $storeId
     * @param null|string $updatedAt
     * @return EcomDev_Sphinx_Model_Index_Reader_Scope
     */
    public function getCategoryScope($storeId, $updatedAt = null)
    {
        $filters = [];

        $filters[] = $this->createResourceModel(
            'ecomdev_sphinx/index_reader_filter',
            ['store_id', $storeId]
        );

        if ($updatedAt !== null) {
            $filters[] = $this->createResourceModel(
                'ecomdev_sphinx/index_reader_filter',
                ['updated_at', $updatedAt]
            );
        }

        $scope = $this->createModel(
            'ecomdev_sphinx/index_reader_scope',
            [$filters, $this->getConfiguration('category')]
        );

        return $scope;
    }

    /**
     * Scope of the category
     *
     * @param int $storeId
     * @return EcomDev_Sphinx_Model_Index_Reader_Scope
     */
    public function getKeywordScope($storeId)
    {
        $filters = [];

        $filters[] = $this->createResourceModel(
            'ecomdev_sphinx/index_reader_filter',
            ['store_id', $storeId]
        );

        $scope = $this->createModel(
            'ecomdev_sphinx/index_reader_scope',
            [$filters, $this->getConfiguration('keyword')]
        );

        return $scope;
    }

    /**
     * Creates a new instance of model
     *
     * @param string $classAlias
     * @param mixed[] $arguments
     * @return Mage_Core_Model_Abstract
     */
    public function createModel($classAlias, array $arguments = [])
    {
        $className = Mage::getConfig()->getModelClassName($classAlias);
        return $this->newInstance('model', $classAlias, $className, $arguments);
    }

    /**
     * Creates a new instance of model
     *
     * @param string $classAlias
     * @param mixed[] $arguments
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function createResourceModel($classAlias, array $arguments = [])
    {
        $className = Mage::getConfig()->getResourceModelClassName($classAlias);
        return $this->newInstance('resource_model', $classAlias, $className, $arguments);
    }

    /**
     * Returns a new instance
     *
     * @param string $alias
     * @param string $className
     * @param array $arguments
     * @return object
     */
    private function newInstance($type, $alias, $className, array $arguments = [])
    {
        $container = new stdClass();
        $container->args = $arguments;
        $container->className = $className;
        $container->alias = $alias;

        Mage::dispatchEvent(
            'ecomdev_sphinx_index_service_create_' . $type,
            ['container' => $container]
        );

        $reflection = new ReflectionClass($container->className);

        if ($container->args && $reflection->getConstructor() !== null) {
            return $reflection->newInstanceArgs($container->args);
        }

        return $reflection->newInstance();
    }
}
