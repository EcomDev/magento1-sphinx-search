<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

class EcomDev_Sphinx_Model_Resource_Trigger extends Mage_Core_Model_Resource_Db_Abstract
{
    const TRIGGER_PREFIX = 'ecomdev_sphinx_trigger_';

    const TRIGGER_FORMAT = self::TRIGGER_PREFIX . '%2$s_%1$s';

    const CODE_DELIMETER = '/*--ECOMDEV_SPHINX_ADDITIONAL_STATEMENT--*/';

    /** @var array */
    private $triggers;

    /**
     * Returns an instance of EAV config
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    private function getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    private function initializeTriggers()
    {
        $availableTriggers = (object)[];
        $availableTriggers->category = ['ecomdev_sphinx/index_category', 'category_id'];
        $availableTriggers->product = ['ecomdev_sphinx/index_product', 'product_id'];
        Mage::dispatchEvent('ecomdev_sphinx_trigger_initialize', ['triggers' => $availableTriggers]);
        $this->triggers = (array)$availableTriggers;
    }

    private function getTriggerTypes()
    {
        if (!$this->triggers) {
            $this->initializeTriggers();
        }

        return $this->triggers;
    }

    private function getConfiguredTriggers()
    {
        $triggers = [];

        foreach ($this->getTriggerTypes() as $type => $deleteTable) {
            if (count($deleteTable) === 2) {
                list($tableName, $field) = $deleteTable;
                $triggers[$this->getTable($tableName)]['delete'][$type] = [
                    'field' => $field,
                    'target' => 'ecomdev_sphinx/index_deleted',
                    'date_field' => 'deleted_at',
                    'check' => [],
                    'type' => 'delete',
                    'table' => $this->getTable($tableName)
                ];
            }

            foreach ($this->getConfig()->getTriggers($type) as $tableName => $trigger) {
                foreach ($trigger as $event => $info) {
                    $triggers[$tableName][$event][$type] = $info;
                }
            }
        }

        return $triggers;
    }

    protected function _construct()
    {
        $this->_setResource('ecomdev_sphinx');
    }

    public function validateTriggers()
    {
        $configuredTriggers = $this->getConfiguredTriggers();

        $currentTriggers = $this->fetchCurrentTriggers(array_keys($configuredTriggers));

        $triggersToCreate = [];
        $triggersToRemove = [];
        foreach ($configuredTriggers as $tableName => $listeners) {
            foreach ($listeners as $event => $triggerByType) {
                $triggerCode = $this->generateTriggerCode(
                    $triggerByType,
                    $currentTriggers[$tableName][$event]['additional_code'] ?? ''
                );

                $currentCode = $currentTriggers[$tableName][$event]['code'] ?? '';

                if ($triggerCode !== $currentCode) {
                    $triggersToCreate[] = [$tableName, $event, $triggerCode];
                    if (isset($currentTriggers[$tableName][$event])) {
                        $triggersToRemove[] = $currentTriggers[$tableName][$event]['name'];
                    }
                }

                unset($currentTriggers[$tableName][$event]);
            }
        }

        foreach ($currentTriggers as $tableName => $triggers) {
            foreach ($triggers as $event => $triggerInfo) {
                $triggersToRemove[] = $triggerInfo['name'];
                if ($triggerInfo['additional_code']) {
                    $triggersToCreate[] = [$tableName, $event, $triggerInfo['additional_code']];
                }
            }
        }

        foreach ($triggersToRemove as $triggerName) {
            $this->_getWriteAdapter()->dropTrigger($triggerName);
        }

        foreach ($triggersToCreate as $triggerInfo) {
            list($tableName, $event, $code) = $triggerInfo;

            $this->createTrigger(
                $this->generateTriggerName(
                    $tableName,
                    $event
                ),
                $tableName,
                $event,
                $code
            );
        }

        return $this;
    }

    private function fetchCurrentTriggers(array $tables)
    {
        $currentTriggers = [];
        $adapter = $this->_getWriteAdapter();

        $stmt = $adapter->query(
            sprintf(
                'SHOW TRIGGERS WHERE %s IN(:tables) OR %s LIKE :trigger_prefix',
                $adapter->quoteIdentifier('Table'),
                $adapter->quoteIdentifier('Trigger')
            ),
            [
                'tables' => $tables,
                'trigger_prefix' => self::TRIGGER_PREFIX . '%'
            ]
        );

        while ($row = $stmt->fetch()) {
            if ($row['Timing'] !== Magento_Db_Sql_Trigger::SQL_TIME_AFTER) {
                continue;
            }

            $currentTriggers[$row['Table']][strtolower($row['Event'])] = [
                'additional_code' => $this->extractAdditionalTriggerCode($row),
                'code' => trim(preg_replace('/^\s*begin\s*(.*)\s*end\s*$/si', '\\1', $row['Statement'])),
                'name' => $row['Trigger']
            ];
        }

        return $currentTriggers;
    }

    /**
     * Creates a new trigger
     *
     */
    private function createTrigger(string $triggerName, string $table, string $event, string $code)
    {
        $trigger = new Magento_Db_Sql_Trigger();
        $trigger->setName($triggerName);
        $trigger->setEvent(strtoupper($event));
        $trigger->setTime(Magento_Db_Sql_Trigger::SQL_TIME_AFTER);
        $trigger->setBody($code);
        $trigger->setTarget($table);

        $this->_getWriteAdapter()->query($trigger->assemble());
        return $this;
    }

    /**
     * Returns additional trigger code based on row information
     */
    protected function extractAdditionalTriggerCode(array $row)
    {
        $code = '';
        $matches = [];
        if ($this->isManagedTrigger($row['Trigger'])) {
            $pattern = preg_quote(self::CODE_DELIMETER, '/');
            if (preg_match(sprintf('/^%1$s(.*?)%1$s$/m', $pattern), $row['Statement'], $matches)) {
                $code = self::CODE_DELIMETER . "\n" . $matches[1] . "\n" . self::CODE_DELIMETER;
            }
        } elseif (preg_match('/^\s*begin(.*?)end$\s*/mi', $row['Statement'], $matches)) {
            $code = self::CODE_DELIMETER . "\n" . $matches[1] . "\n" . self::CODE_DELIMETER;
        }

        return $code;
    }


    private function generateTriggerName($table, $event)
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

    private function isManagedTrigger($triggerName): bool
    {
        return strpos($triggerName, self::TRIGGER_PREFIX) === 0;
    }

    private function generateTriggerCode(array $types, string $additionalCode = '')
    {
        $code = [];
        foreach ($types as $type => $triggerInfo) {
            $code[] = $this->generateTriggerStatement($type, $triggerInfo);
        }

        if ($additionalCode) {
            $code[] = $additionalCode;
        }

        return implode("\n", $code);
    }

    private function generateTriggerStatement(string $type, $triggerInfo)
    {
        $dateField = isset($triggerInfo['date_field']) ? $triggerInfo['date_field'] : 'updated_at';
        $targetTable = isset($triggerInfo['target']) ? $triggerInfo['target'] : 'ecomdev_sphinx/index_updated';
        $targetTable = $this->getTable($targetTable);

        $fieldPrefix = 'NEW';
        if ($triggerInfo['type'] === 'delete') {
            $fieldPrefix = 'OLD';
        }

        $adapter = $this->_getWriteAdapter();

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
            $adapter->quote($type),
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

        return $code;
    }
}
