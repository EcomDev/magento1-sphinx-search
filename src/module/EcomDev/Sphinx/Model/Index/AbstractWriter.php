<?php

/**
 * Abstract writer implementation
 *
 *
 */
abstract class EcomDev_Sphinx_Model_Index_AbstractWriter
    implements EcomDev_Sphinx_Contract_WriterInterface
{
    /**
     * Stream for having data output
     *
     * @var string
     */
    private $path;

    /**
     * Assigns stream
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Stream where data gets written
     *
     * @return SplFileObject
     */
    public function getFileObject()
    {
        return new SplFileObject($this->path, 'w');
    }

    /**
     * Returns path
     *
     * @return string
     */
    protected function getPath()
    {
        return $this->path;
    }
}
