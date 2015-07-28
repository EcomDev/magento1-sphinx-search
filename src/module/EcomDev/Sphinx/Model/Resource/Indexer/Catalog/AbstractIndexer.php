<?php

abstract class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_AbstractIndexer
    extends Mage_Index_Model_Resource_Abstract
{
    const TRIGGER_FORMAT = 'ecomdev_sphinx_trigger_%s';

    /**
     * Returns an instance of EAV config
     * 
     * @return EcomDev_Sphinx_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }
    
    /**
     * Joins attribute into select
     *
     * @param Varien_Db_Select $select
     * @param string $condition
     * @param string $attributeCode
     * @param string $entityType
     * @param string $prefix
     * @param string $joinMethod
     * @return $this
     */
    protected function _joinAttribute($select, $condition, $attributeCode, $entityType, 
                                      $prefix = 'index', 
                                      $joinMethod = 'join')
    {
        $attribute = $this->_getConfig()->getEavConfig()->getAttribute(
            $entityType,
            $attributeCode
        );

        if (!$attribute) {
            return $this;
        }

        $defaultPrefix = $attributeCode . '_default';
        $storePrefix = $attributeCode . '_store';
        $attributeCondition = $this->_quoteInto('?', $attribute->getId());

        $select
            ->$joinMethod(
                array($defaultPrefix => $attribute->getBackendTable()),
                sprintf($condition, $defaultPrefix, 0, $attributeCondition),
                array()
            )
            ->joinLeft(
                array($storePrefix => $attribute->getBackendTable()),
                sprintf($condition, $storePrefix, $prefix . '.store_id', $attributeCondition),
                array(
                    $attributeCode => $this->_getAttributeIfNullExpr($attributeCode)
                )
            )
        ;

        return $this;
    }
    
    protected function _getAttributeIfNullExpr($attributeCode, 
                                      $suffixDefault = '_default', 
                                      $suffixStore = '_store')
    {
        $storePrefix = $attributeCode . $suffixStore;
        $defaultPrefix = $attributeCode . $suffixDefault;
        return $this->_getIndexAdapter()->getCheckSql(
            $storePrefix . '.value_id IS NOT NULL',
            $storePrefix . '.value',
            $defaultPrefix . '.value'
        );
    }

    /**
     * Quotes a value into a condition
     * 
     * @param string $condition
     * @param mixed $value
     * @return string
     */
    protected function _quoteInto($condition, $value)
    {
        return $this->_getIndexAdapter()->quoteInto($condition, $value);
    }

    /**
     * Runs a transaction database operation.
     * In case of condition equal to true,
     * it will wrap operation with transaction statement
     *
     * @param callable $method
     * @param bool $condition
     * @param mixed $argument passed argument to a callback
     * @return $this
     * @throws Exception in case of any statements failure
     */
    protected function _transactional(Closure $method, $condition = true, $argument = null)
    {
        try {
            if ($condition) {
                $this->_getIndexAdapter()->beginTransaction();
            }
            $method($argument);
            if ($condition) {
                $this->_getIndexAdapter()->commit();
            }
        } catch (Exception $e) {
            if ($condition) {
                $this->_getIndexAdapter()->rollBack();
            }
            
            Mage::logException($e);
            throw $e;
        }
        
        return $this;
    }

    /**
     * Get insert from Select object query
     *
     * @param Varien_Db_Select $select
     * @param string $table     insert into table
     * @param string[] $columns 
     * @param array $fields
     * @param bool|int $mode
     * @return string
     */
    public function insertFromSelectInternal(Varien_Db_Select $select, $table, array $columns = array(), array $fields = array(), $mode = false)
    {
        $query = 'INSERT';
        if ($mode == Varien_Db_Adapter_Interface::INSERT_IGNORE) {
            $query .= ' IGNORE';
        }
        
        $query = sprintf('%s INTO %s', $query, $this->_getIndexAdapter()->quoteIdentifier($table));
        if ($columns) {
            $columns = array_map(array($this->_getIndexAdapter(), 'quoteIdentifier'), $columns);
            $query = sprintf('%s (%s)', $query, join(', ', $columns));
        }

        $query = sprintf('%s %s', $query, $select->assemble());

        if ($mode == Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE) {
            if (!$fields) {
                $describe = $this->_getIndexAdapter()->describeTable($table);
                foreach ($describe as $column) {
                    if ($column['PRIMARY'] === false) {
                        $fields[] = $column['COLUMN_NAME'];
                    }
                }
            }

            $update = array();
            foreach ($fields as $k => $v) {
                $field = $value = null;
                if (!is_numeric($k)) {
                    $field = $this->_getIndexAdapter()->quoteIdentifier($k);
                    if ($v instanceof Zend_Db_Expr) {
                        $value = $v->__toString();
                    } elseif (is_string($v)) {
                        $value = sprintf('VALUES(%s)', $this->_getIndexAdapter()->quoteIdentifier($v));
                    } elseif (is_numeric($v)) {
                        $value = $this->_quoteInto('?', $v);
                    }
                } elseif (is_string($v)) {
                    $value = sprintf('VALUES(%s)', $this->_getIndexAdapter()->quoteIdentifier($v));
                    $field = $this->_getIndexAdapter()->quoteIdentifier($v);
                }

                if ($field && $value) {
                    $update[] = sprintf('%s = %s', $field, $value);
                }
            }
            if ($update) {
                $query = sprintf('%s ON DUPLICATE KEY UPDATE %s', $query, join(', ', $update));
            }
        }

        return $query;
    }

    /**
     * Renders conditions from provided arguments
     *
     * @param [$condition1]
     * @param [$condition2]
     * @return string
     */
    protected function _createCondition()
    {
        $conditions = func_get_args();

        if (end($conditions) === 'or') {
            array_pop($conditions);
            return implode(' or ', $conditions);
        }

        return implode(' and ', $conditions);
    }

    /**
     * Executes sql queries and returns amount of affected rows
     *
     * @param string[] $queries
     * @return int
     */
    protected function _executeQueries($queries)
    {
        $affectedRows = 0;
        foreach ($queries as $query) {
            try {
                $stmt = $this->_getIndexAdapter()->query($query);
                $affectedRows += $stmt->rowCount();
            } catch (Exception $e) {
                Mage::logException($e);
                throw new RuntimeException(
                    sprintf("Unable to execute query: %s\n Message: %s", $query, $e->getMessage())
                );
            }
        }

        return $affectedRows;
    }

    protected function _createIndexTrigger($table, $type, $field = 'document_id')
    {
        if ($this->_triggerExists($table)) {
            return $this;
        }

        $triggerName = $this->_triggerName($table);
        $trigger = new Magento_Db_Sql_Trigger();
        $trigger->setName($triggerName);
        $trigger->setEvent(Magento_Db_Sql_Trigger::SQL_EVENT_DELETE);
        $trigger->setTime(Magento_Db_Sql_Trigger::SQL_TIME_AFTER);
        $insertStatement = sprintf(
            'INSERT IGNORE INTO %s (%s, %s) VALUES (?, old.%s);',
            $this->getTable('ecomdev_sphinx/index_deleted'),
            $this->_getIndexAdapter()->quoteIdentifier('type'),
            $this->_getIndexAdapter()->quoteIdentifier('document_id'),
            $field
        );

        $trigger->setBody($this->_getIndexAdapter()->quoteInto($insertStatement, $type));
        $trigger->setTarget($table);

        $this->_getIndexAdapter()->query($trigger->assemble());
        return $this;
    }

    protected function _dropIndexTrigger($table)
    {
        $triggerName = $this->_triggerName($table);
        $this->_getIndexAdapter()->dropTrigger($triggerName);
        return $this;
    }

    protected function _triggerExists($table)
    {
        $triggerName = $this->_triggerName($table);
        $query = $this->_getIndexAdapter()->quoteInto(
            sprintf(
                'SHOW TRIGGERS WHERE %s = ?',
                $this->_getIndexAdapter()->quoteIdentifier('Trigger')
            ),
            $triggerName
        );
        return $this->_getIndexAdapter()->fetchOne($query) === $triggerName;
    }

    protected function _triggerName($table)
    {
        if (strpos($table, '/') !== false) {
            $table = $this->getTable($table);
        }

        return sprintf(self::TRIGGER_FORMAT, $table);
    }
}
