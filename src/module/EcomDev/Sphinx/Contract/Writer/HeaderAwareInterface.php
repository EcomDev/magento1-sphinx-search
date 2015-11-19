<?php

interface EcomDev_Sphinx_Contract_Writer_HeaderAwareInterface
{
    /**
     * Flag for output headers for exported file
     *
     * @param bool $flag
     * @return $this
     */
    public function setOutputHeaders($flag);
}
