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
     * @var resource
     */
    private $stream;

    /**
     * Assigns stream
     *
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream should be a resource');
        }

        $this->stream = $stream;
    }

    /**
     * Stream where data gets written
     *
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }


}
