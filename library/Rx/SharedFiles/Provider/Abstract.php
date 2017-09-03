<?php

abstract class Rx_SharedFiles_Provider_Abstract
{
    /**
     * Shared files collection Id
     *
     * @var string $_id
     */
    protected $_id = null;
    /**
     * Prefix to use for shared files collection items
     *
     * @var string $_prefix
     */
    protected $_prefix = null;

    /**
     * Class constructor
     *
     * @param string $id Shared files collection Id
     */
    public function __construct($id)
    {
        $this->setId($id);
    }

    /**
     * Get information about shared file with given name from shared files collection
     *
     * @param string $id Shared file Id to get information for
     * @return Rx_SharedFiles_File|null
     */
    abstract public function getFileInfo($id);

    /**
     * Get contents of shared file
     *
     * @param Rx_SharedFiles_File $info Shared file information structure to get file contents for
     * @return mixed|null
     */
    abstract public function getFileContent($info);

    /**
     * Determine if information of shared file with given Id can be stored in cache
     *
     * @param Rx_SharedFiles_File|string $info Shared file Id or information structure to check
     * @return boolean          true if file can be stored, false if it should be requested from provider
     */
    abstract public function isCacheable($id);

    /**
     * Set shared files collection Id
     *
     * @param string $id Shared files collection Id
     * @return void
     */
    public function setId($id)
    {
        $this->_id = $id;
        $prefix = $id;
        if (substr($prefix, -1) != '_') {
            $prefix .= '_';
        }
        $this->_prefix = $prefix;
    }

    /**
     * Get cache object that contains shared files of collection with given Id
     *
     * @return Zend_Cache_Core
     */
    public function getCache()
    {
        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
        } elseif (Zend_Registry::isRegistered('Zend_Cache')) {
            $cache = Zend_Registry::get('Zend_Cache');
        } else {
            $cache = null;
        }
        if ($cache instanceof Zend_Cache_Core) {
            return ($cache);
        }
        return (null);
    }

    /**
     * Get cache Id for shared file collection index
     *
     * @return string
     */
    public function getIndexCacheId()
    {
        return ('sf_' . $this->_prefix . 'index');
    }

    /**
     * Get cache Id for shared file content item
     *
     * @param Rx_SharedFiles_File|string $info Shared file Id or information structure to get cache Id for
     * @return string
     */
    public function getFileCacheId($info)
    {
        $parts = array('sf', $this->_id, 'file');
        if ($info instanceof Rx_SharedFiles_File) {
            $parts[] = $info->id;
            // We should NOT use shared file hash if file itself is not cacheable
            // because in this case file's cache Id is used by provider itself and
            // should not depend on file's current hash
            if ($this->isCacheable($info->id)) {
                $parts[] = $info->hash;
            }
        } else {
            $parts[] = $info;
        }
        $cacheId = join('_', $parts);
        return ($cacheId);
    }

}
