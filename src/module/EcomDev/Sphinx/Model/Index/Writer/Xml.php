<?php

use EcomDev_Sphinx_Contract_ReaderInterface as ReaderInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Writer_Xml
    extends EcomDev_Sphinx_Model_Index_AbstractWriter
{
    const SPHINX_NS = 'http://sphinxsearch.com';

    /**
     * Number of flushed docs
     *
     * @var int
     */
    private $flushSize = 10;

    /**
     * Processes reader within specified scope
     *
     * @param ReaderInterface $reader
     * @param ScopeInterface $scope
     * @return $this
     */
    public function process(ReaderInterface $reader, ScopeInterface $scope)
    {
        $columns = $scope->getConfiguration()->getFields();
        $reader->setScope($scope);
        $writer = new XMLWriter();
        if (!$writer->openMemory()) {
            return $this;
        }

        $writer->startDocument('1.0', 'utf-8');
        $writer->setIndent(true);
        $writer->startElementNs('sphinx', 'docset', self::SPHINX_NS);

        $counter = 0;
        /** @var EcomDev_Sphinx_Contract_DataRowInterface $dataRow */
        foreach ($reader as $dataRow) {
            $writer->startElementNs('sphinx', 'document', self::SPHINX_NS);
            $writer->writeAttribute('id', $dataRow->getId());

            foreach ($columns as $column) {
                $value = $column->getValue($dataRow, $scope);

                if ($column->isMultiple() && is_array($value)) {
                    $value = implode(',', $value);
                }

                $writer->writeElement($column->getName(), (string)$value);
            }

            $writer->endElement();

            $counter++;
            if ($counter > $this->flushSize) {
                $counter = 0;
                $this->getFileObject()->fwrite($writer->flush());
            }
        }

        $killIdentifiers = $reader->getProvider()->getKillRecords($scope);
        if ($killIdentifiers) {
            $writer->startElementNs('sphinx', 'killlist', self::SPHINX_NS);

            foreach ($killIdentifiers as $identifier) {
                if (is_array($identifier)) {
                    $identifier = $identifier['entity_id'];
                }

                $writer->writeElement('id', (string)$identifier);
            }

            $writer->endElement();
        }

        $writer->endElement();
        $this->getFileObject()->fwrite($writer->flush());
    }

}
