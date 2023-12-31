<?php

class EcomDev_Sphinx_Model_Sphinx_Config_Daemon
    extends EcomDev_Sphinx_Model_Sphinx_AbstractConfig
    implements EcomDev_Sphinx_Model_Sphinx_ConfigInterface
{

    /**
     * Should render a configuration
     *
     * @return string
     */
    public function render()
    {
        $statements = $this->_getStatements();

        $configuration = array();
        foreach ($statements as $key => $values) {
            $configuration[] = sprintf(
                "%s \n{ \n%s\n}\n",
                $key,
                $this->_renderStrings($this->_renderStatements($values))
            );
        }
        
        return sprintf(
            $this->_getFileTemplate(), 
            implode("\n", $configuration),
            $this->_getConfig()->getConfig('index_config_path')
        );
    }

    /**
     * Returns rendered statements
     * 
     * @param string[] $statements
     * @return string
     */
    protected function _renderStatements($statements)
    {
        $result = array();
        $renderStatement = function ($key, $value) {
            return sprintf('%s=%s', $key, $value);
        };
        
        foreach ($statements as $key => $statement) {
            if (is_array($statement)) {
                foreach ($statement as $subStatement) {
                    $result[] = $renderStatement($key, $subStatement);
                }
            } else {
                $result[] = $renderStatement($key, $statement);
            }
        }
        
        return $result;
    }

    /***
     * Return all available configuration options
     * 
     * @return string[][]
     */
    protected function _getStatements()
    {
        $config = array(
            'searchd' => array(),
            'indexer' => array()
        );

        $listens = array_filter(
            array_map(
                'trim',
                explode("\n", trim($this->_getConfig()->getConfig('listen')))
            )
        );

        foreach ($listens as $listen) {
            $config['searchd']['listen'][] = $listen;
        }

        $config['searchd']['pid_file'] = $this->_getConfig()->getConfig('pid');
        $config['searchd']['log'] = $this->_getConfig()->getConfig('log');
        $config['searchd']['binlog_path'] = '';

        $possibleEmptyConfigOptions = [
            'query_log',
            'read_buffer',
            'max_batch_queries',
            'subtree_docs_cache',
            'subtree_hits_cache',
            'collation_server',
            'workers',
            'read_timeout',
            'max_children'
        ];

        $configModel = $this->_getConfig();

        if ($configModel->getConfig('workers') === 'prefork') {
            $possibleEmptyConfigOptions[] = 'prefork_rotation_throttle';
        }

        foreach ($possibleEmptyConfigOptions as $option) {
            $value = $configModel->getConfig($option);
            if ($value !== null && $value !== '') {
                $config['searchd'][$option] = $value;
            }
        }

        if (isset($config['searchd']['query_log'])) {
            $config['searchd']['query_log_format'] = 'sphinxql';
        }

        $config['indexer']['mem_limit'] = $this->_getConfig()->getConfig('indexer_memory_limit');
        $config['indexer']['write_buffer'] = $this->_getConfig()->getConfig('indexer_write_buffer');
        $config['indexer']['max_file_field_buffer'] = $this->_getConfig()->getConfig('indexer_file_buffer');
        
        return $config;
    }
    
    /**
     * Returns a template for a configuration file
     * 
     * @return string
     */
    protected function _getFileTemplate()
    {
        return <<<'SPHINX'
#!/bin/bash
# Manged by a magento installation
# Includes indexes from external file
shopt -s nullglob

cat <<EOT
%s
EOT

cat %s

SPHINX;
    }
}
