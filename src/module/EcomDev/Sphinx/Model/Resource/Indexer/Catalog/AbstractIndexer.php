<?php

abstract class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_AbstractIndexer
    extends Mage_Index_Model_Resource_Abstract
{
    const TRIGGER_FORMAT = 'ecomdev_sphinx_trigger_%2$s_%1$s';
    const CODE_DELIMETER = '/*--ECOMDEV_SPHINX_ADDITIONAL_STATEMENT--*/';

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

    /**
     * Updates index from passed select and applies limit if there is any
     *
     * @param Varien_Db_Select $select
     * @param Varien_Db_Select $limit
     * @param string[]|null $table
     * @return $this
     */
    protected function _updateIndexTableFromSelect(
        Varien_Db_Select $select,
        Varien_Db_Select $limit = null,
        $table = null
    )
    {
        if ($limit !== null) {
            $select->where(sprintf('index.%s IN(?)', $this->getIdFieldName()), $limit);
        }

        if ($table === null) {
            $table = array('index' => $this->getMainTable());
        }

        $sql = $this->_getIndexAdapter()->updateFromSelect(
            $select, $table
        );

        $this->_getIndexAdapter()->query(
            $sql
        );

        return $this;
    }

    /**
     * Creates a new trigger
     *
     * @param string $triggerName
     * @param string $table
     * @param string $event
     * @param string $time
     * @param string $code
     * @return $this
     */
    protected function _createTrigger($triggerName, $table, $event, $time, $code)
    {
        $trigger = new Magento_Db_Sql_Trigger();
        $trigger->setName($triggerName);
        $trigger->setEvent($event);
        $trigger->setTime($time);
        $trigger->setBody($code);
        $trigger->setTarget($table);

        $this->_getIndexAdapter()->query($trigger->assemble());
        return $this;
    }

    /**
     * Trigger name generator
     *
     * @param string $table
     * @param string $event
     * @return string
     */
    protected function _triggerName($table, $event)
    {
        if (strpos($table, '/') !== false) {
            $table = $this->getTable($table);
        }

        $name = sprintf(self::TRIGGER_FORMAT, $table, $event);

        if (strlen($name) > 64) {
            $name = substr($name, 0, 31) . '_' . md5($name);
        }

        return $name;
    }

    /**
     * Returns additional trigger code
     *
     * @param string $row
     * @return bool|string
     */
    protected function _getAdditionalTriggerCode($row)
    {
        $code = '';
        $matches = [];
        if (strpos($row['Trigger'], 'ecomdev_sphinx') !== false) {
            $pattern = preg_quote(self::CODE_DELIMETER, '/');
            if (preg_match(sprintf('/^%1$s(.*?)%1$s$/m', $pattern), $row['Statement'], $matches)) {
                $code = self::CODE_DELIMETER . "\n" . $matches[1] . "\n" . self::CODE_DELIMETER;
            }
        } elseif (preg_match('/^\s*begin(.*?)end$\s*/mi', $row['Statement'], $matches)) {
            $code = self::CODE_DELIMETER . "\n" . $matches[1] . "\n" . self::CODE_DELIMETER;
        }

        return $code;
    }

    /**
     * Returns trigger list for passed tables
     *
     * @param array $tableNames
     * @return string[][][]
     */
    protected function _getCurrentTriggers(array $tableNames)
    {
        $currentTriggers = [];
        $stmt = $this->_getIndexAdapter()->query(
            $this->_getIndexAdapter()->quoteInto(
                sprintf(
                    'SHOW TRIGGERS WHERE %s IN(?)',
                    $this->_getIndexAdapter()->quoteIdentifier('Table')
                ),
                $tableNames
            )
        );

        while ($row = $stmt->fetch()) {
            if ($row['Timing'] !== Magento_Db_Sql_Trigger::SQL_TIME_AFTER) {
                continue;
            }

            $currentTriggers[$row['Table']][strtolower($row['Event'])] = [
                'additional_code' => $this->_getAdditionalTriggerCode($row),
                'code' => trim(preg_replace('/^\s*begin\s*(.*)\s*end\s*$/si', '\\1', $row['Statement'])),
                'name' => $row['Trigger']
            ];
        }

        return $currentTriggers;
    }

    /**
     * Validates all triggers for the entity
     *
     * @param $entityType
     * @param bool|string[] $deleteTrigger
     * @return $this
     */
    protected function _validateTriggers($entityType, $deleteTrigger = false)
    {
        $allowedTriggers = $this->_getConfig()->getTriggers($entityType);

        if ($deleteTrigger !== false) {
            $allowedTriggers[$this->getTable($deleteTrigger[0])]['delete'] = [
                'field' => $deleteTrigger[1],
                'target' => 'ecomdev_sphinx/index_deleted',
                'date_field' => 'deleted_at',
                'check' => [],
                'type' => 'delete',
                'table' => $this->getTable($deleteTrigger[0])
            ];
        }


        $currentTriggers = $this->_getCurrentTriggers(array_keys($allowedTriggers));

        $toRemove = [];
        $toCreate = [];

        foreach ($allowedTriggers as $table => $types) {
            if (isset($currentTriggers[$table])) {
                foreach ($currentTriggers[$table] as $type => $triggerInfo) {
                    if (!isset($allowedTriggers[$table][$type])) {
                        $toRemove[] = $triggerInfo['name'];
                        if (!empty($triggerInfo['additional_code'])) {
                            $toCreate[] = [
                                'table' => $table,
                                'type' => $type,
                                'code' => $triggerInfo['additional_code']
                            ];
                        }
                    }
                }
            }

            foreach ($types as $type => $triggerInfo) {
                if (isset($currentTriggers[$table][$type])) {
                    $code = $this->_generateTriggerCode(
                        $entityType,
                        $triggerInfo,
                        $currentTriggers[$table][$type]['additional_code']
                    );

                    if ($code !== $currentTriggers[$table][$type]['code']) {
                        $toRemove[] = $currentTriggers[$table][$type]['name'];
                        $toCreate[] = [
                            'table' => $table,
                            'type' => $type,
                            'code' => $code
                        ];
                    }
                } else {
                    $toCreate[] = [
                        'table' => $table,
                        'type' => $type,
                        'code' => $this->_generateTriggerCode($entityType, $triggerInfo, '')
                    ];
                }
            }
        }

        foreach ($toRemove as $triggerName) {
            $this->_getIndexAdapter()->dropTrigger($triggerName);
        }

        foreach ($toCreate as $triggerInfo) {
            $triggerName = $this->_triggerName(
                $triggerInfo['table'],
                $triggerInfo['type']
            );

            $this->_createTrigger(
                $triggerName,
                $triggerInfo['table'],
                strtoupper($triggerInfo['type']),
                Magento_Db_Sql_Trigger::SQL_TIME_AFTER,
                $triggerInfo['code']
            );
        }

        return $this;
    }

    /**
     * Generates trigger code
     *
     * @param string $entityType
     * @param string[] $triggerInfo
     * @param string $additionalCode
     * @return string
     */
    protected function _generateTriggerCode($entityType, $triggerInfo, $additionalCode)
    {
        $dateField = isset($triggerInfo['date_field']) ? $triggerInfo['date_field'] : 'updated_at';
        $targetTable = isset($triggerInfo['target']) ? $triggerInfo['target'] : 'ecomdev_sphinx/index_updated';
        $targetTable = $this->getTable($targetTable);

        $fieldPrefix = 'NEW';
        if ($triggerInfo['type'] === 'delete') {
            $fieldPrefix = 'OLD';
        }

        $adapter = $this->_getIndexAdapter();

        $statement = 'INSERT %1$s (%2$s, %3$s, %4$s) '
                   . ' VALUES (%5$s, %6$s, %7$s) '
                   . ' ON DUPLICATE KEY UPDATE %4$s = VALUES(%4$s);';

        $statement = sprintf(
            $statement,
            $targetTable,
            $adapter->quoteIdentifier('entity_id'),
            $adapter->quoteIdentifier('type'),
            $adapter->quoteIdentifier($dateField),
            $fieldPrefix . '.' . $adapter->quoteIdentifier($triggerInfo['field']),
            $adapter->quote($entityType),
            'NOW()'
        );

        if ($triggerInfo['type'] === 'update' && !empty($triggerInfo['check'])) {
            $conditions = [];
            foreach ((array)$triggerInfo['check'] as $field) {
                $conditions[] = sprintf('OLD.%1$s <> NEW.%1$s', $field);
            }
            $code = sprintf(
                "IF %s THEN\n%s\nEND IF;",
                implode(' OR ', $conditions),
                $statement
            );
        } else {
            $code = $statement;
        }

        if ($additionalCode !== '') {
            $code .= "\n" . $additionalCode;
        }

        return $code;
    }
}
