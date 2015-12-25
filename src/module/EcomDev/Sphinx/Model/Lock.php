<?php

class EcomDev_Sphinx_Model_Lock
{
    /**
     * File object
     *
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var string
     */
    private $path;

    /**
     * Is it locked
     *
     * @var bool
     */
    private $isLocked = false;

    /**
     * Constructs a lock model
     *
     * @param string $options
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['file'])) {
            $options['file'] = 'sphinx.lock';
        }

        if (!isset($options['directory'])) {
            $options['directory'] = Mage::getConfig()->getVarDir('lock');
        }

        $fileName = $options['file'];
        $locksDirectory = $options['directory'];

        if (!is_dir($locksDirectory)) {
            mkdir($locksDirectory, 0755, true);
        }

        $this->path = $locksDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!is_file($this->path)) {
            file_put_contents($this->path, '');
        }
    }

    /**
     * Returns a file instance for lock
     *
     * @return \SplFileObject
     */
    private function getFile()
    {
        if ($this->file === null) {
            $this->file = new \SplFileObject($this->path, 'r+');
        }

        return $this->file;
    }

    /**
     * @return bool
     */
    public function lock()
    {
        if ($this->getFile()->flock(LOCK_EX | LOCK_NB)) {
            $this->isLocked = true;
            return true;
        }

        return false;
    }

    /**
     * Returns true if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        if ($this->isLocked) {
            return true;
        }

        if ($this->getFile()->flock(LOCK_EX | LOCK_NB)) {
            $this->getFile()->flock(LOCK_UN);
            return false;
        }

        return true;
    }

    /**
     * Unlocks a file
     *
     * @return $this
     */
    public function unlock()
    {
        if (!$this->isLocked) {
            return $this;
        }

        if ($this->getFile()->flock(LOCK_UN)) {
            $this->isLocked = false;
        }

        return $this;
    }
}
