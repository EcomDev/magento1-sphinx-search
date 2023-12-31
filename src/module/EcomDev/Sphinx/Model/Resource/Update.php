<?php

class EcomDev_Sphinx_Model_Resource_Update extends Mage_Core_Model_Resource_Db_Abstract
{
    const LIMIT_ID = 500;

    private $typeMap = [
        'product' => 'ecomdev_sphinx/index_product',
        'category' => 'ecomdev_sphinx/index_category'
    ];

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_updated', 'entity_id');
    }

    /**
     * Updates records
     *
     * @param DateTime $timeStamp
     * @param string $type
     * @return $this
     */
    public function walkUpdatedEntityIds(DateTime $timeStamp, $type, Closure $processor, $limit)
    {
        // Tranform date to UTC timezone
        $timeStamp->setTimezone(new DateTimeZone('UTC'));

        $select = $this->_getLoadSelect('type', $type, null);
        $select->where(
            'updated_at >= ?',
            $timeStamp->format('Y-m-d H:i:s')
        );

        $select->reset(Varien_Db_Select::COLUMNS)
            ->columns('entity_id');

        $stmt = $this->_getReadAdapter()->query($select);
        $ids = [];
        
        while ($id = $stmt->fetchColumn()) {
            $ids[] = $id;
            if (count($ids) > $limit) {
                $processor($ids, $type);
                $ids = [];
            }
        }

        if ($ids) {
            $processor($ids, $type);
        }

        return $this;
    }

    /**
     * Updates records
     *
     * @param DateTime $timeStamp
     * @param string $type
     * @return $this
     */
    public function cleanupUpdatedEntityIds(DateTime $timeStamp, $type)
    {
        // Tranform date to UTC timezone
        $timeStamp->setTimezone(new DateTimeZone('UTC'));

        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            [
                'updated_at < ?' => $timeStamp->format('Y-m-d H:i:s'),
                'type = ?' => $type
            ]
        );

        return $this;
    }


    /**
     * Returns date time instance with latest updated at items
     *
     * @param string $type
     * @return DateTime
     */
    public function getLatestUpdatedAt($type)
    {
        if (!isset($this->typeMap[$type])) {
            return new DateTime();
        }

        $select = $this->_getReadAdapter()->select();
        $select->from(
            $this->getTable($this->typeMap[$type]),
            'MAX(updated_at)'
        );

        $dateTime = $this->_getReadAdapter()->fetchOne($select);
        if (!$dateTime) {
            return new DateTime();
        }

        $dateTime = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $dateTime,
            new DateTimeZone('UTC')
        );

        // Give possible indexation time threshold
        $dateTime->sub(
            DateInterval::createFromDateString('1 minute')
        );

        return $dateTime;
    }

    public function updatedIndexSelect(string $type, DateTimeInterface $startingFrom)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \InvalidArgumentException('Type must be product or category');
        }

        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable($this->typeMap[$type]), [$type . '_id'])
            ->where('updated_at >= ?', $startingFrom->format('Y-m-d H:i:s'));

        return $select;
    }
}
