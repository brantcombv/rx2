<?php

class Rx_SharedFiles_Collection
{
    /**
     * Id of this shared files collection
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
     * Index for shared files
     *
     * @var array $_index
     */
    protected $_index = array();
    /**
     * Provider class for this shared files collection
     *
     * @var Rx_SharedFiles_Provider_Abstract $_provider
     */
    protected $_provider = null;
    /**
     * Cache to store shared files to
     *
     * @var Zend_Cache_Core $_cache
     */
    protected $_cache = null;

    /**
     * Class constructor
     * Provider class can be defined in configuration file as:
     * rx.sharedfiles.provider.<$id>
     *
     * @param string $id                                        Shared files collection Id
     * @param Rx_SharedFiles_Provider_Abstract|string $provider OPTIONAL Provider class for this shared files collection
     * @param Zend_Cache_Core $cache                            OPTIONAL Cache to store shared files to
     * @return void
     * @throws Rx_SharedFiles_Exception
     */
    public function __construct($id, $provider = null, $cache = null)
    {
        if (!strlen($id)) {
            throw new Rx_SharedFiles_Exception('Shared files collection Id should not be empty');
        }
        $this->_id = $id;
        $prefix = $id;
        if (substr($prefix, -1) != '_') {
            $prefix .= '_';
        }
        $this->_prefix = $prefix;
        if ($provider !== null) {
            $this->setProvider($provider);
        }
        if ($cache !== null) {
            $this->setCache($cache);
        }
    }

    /**
     * Get shared collection identifier
     *
     * @return string
     */
    public function getId()
    {
        return ($this->_id);
    }

    /**
     * Set cache object to use with this shared files collection
     *
     * @param Zend_Cache_Core $cache Cache object
     * @return void
     * @throws Rx_SharedFiles_Exception
     */
    public function setCache($cache)
    {
        if (!$cache instanceof Zend_Cache_Core) {
            throw new Rx_SharedFiles_Exception('Cache object must be instance of Zend_Cache_Core');
        }
        $this->_cache = $cache;
    }

    /**
     * Get cache object
     *
     * @return Zend_Cache_Core
     * @throws Rx_SharedFiles_Exception
     */
    public function getCache()
    {
        if ($this->_cache) {
            return ($this->_cache);
        }
        // No cache is set, attempt to get it from provider
        $this->setCache($this->getProvider()->getCache());
        if ($this->_cache) {
            return ($this->_cache);
        }
        throw new Rx_SharedFiles_Exception('No cache is defined for shared files collection "' . $this->getId() . '"');
    }

    /**
     * Set shared files provider object to use for
     *
     * @param Rx_SharedFiles_Provider_Abstract|string $provider OPTIONAL Provider class for this shared files collection
     * @return void
     * @throws Rx_SharedFiles_Exception
     */
    public function setProvider($provider)
    {
        if (is_object($provider)) {
            if (!$provider instanceof Rx_SharedFiles_Provider_Abstract) {
                throw new Rx_SharedFiles_Exception('Shared files provider must be instance of Rx_SharedFiles_Provider_Abstract');
            }
            $this->_provider = $provider;
            $this->_provider->setId($this->getId());
        } elseif (is_string($provider)) {
            $name = $provider;
            $provider = $this->_getProvider($provider);
            if (is_object($provider)) {
                $this->_provider = $provider;
            } else {
                trigger_error('Unable to find shared files provider: ' . $name, E_USER_ERROR);
            }
        } else {
            trigger_error('Shared files provider should be either object or string', E_USER_ERROR);
        }
    }

    /**
     * Get shared files provider object
     *
     * @return Rx_SharedFiles_Provider_Abstract
     * @throws Rx_SharedFiles_Exception
     */
    public function getProvider()
    {
        if (!$this->_provider) {
            // No provider is available yet, attempt to load it
            $provider = Rx_Config::get('rx.sharedfiles.provider.' . $this->getId(), 'default');
            $this->setProvider($provider);
            if (!$this->_provider) {
                throw new Rx_SharedFiles_Exception('No information provider is defined for shared files collection "' . $this->getId() . '"');
            }
        }
        return ($this->_provider);
    }

    /**
     * Check if shared file with given Id is available in collection
     *
     * @param string $id Id of shared file to check existence of
     * @return boolean
     */
    public function exists($id)
    {
        $this->_loadIndex();
        return (array_key_exists($id, $this->_index));
    }

    /**
     * Get information about available shared files
     *
     * @param string|array $files       OPTIONAL List of Ids of shared files to receive information about
     *                                  or null to receive information about all available files
     * @param boolean $getContent       OPTIONAL true to get file content, false to skip it (default)
     * @return array                    Array of Rx_SharedFiles_File
     */
    public function getFilesInfo($files = null, $getContent = false)
    {
        $this->_loadIndex();
        if ($files === null) {
            $files = array_keys($this->_index);
        } elseif (!is_array($files)) {
            $files = array($files);
        }
        $result = array();
        foreach ($files as $id) {
            $info = $this->_getFileInfo($id);
            if (!$info instanceof Rx_SharedFiles_File) {
                continue;
            }
            // We should return cloned file information object with content
            // to avoid content saving in shared files collection index
            $info = clone($info);
            if ($getContent) {
                $info->content = $this->_getFileContent($info);
            }
            $result[$id] = $info;
        }
        return ($result);
    }

    /**
     * Get information about shared file with given Id
     *
     * @param string $id          Id of shared file to receive information about
     * @param boolean $getContent OPTIONAL true to get file content, false to skip it (default)
     * @return Rx_SharedFiles_File|null
     */
    public function getFileInfo($id, $getContent = false)
    {
        $info = $this->getFilesInfo($id, $getContent);
        $info = array_shift($info);
        return ($info);
    }

    /**
     * Set shared file information in collection
     *
     * @param Rx_SharedFiles_File $info Shared file information
     * @return boolean                      true if shared file was added in collection, false in a case of error
     * @throws Rx_SharedFiles_Exception
     */
    public function setFileInfo($info)
    {
        if (!$info instanceof Rx_SharedFiles_File) {
            throw new Rx_SharedFiles_Exception('Shared file information structure should be instance of Rx_SharedFiles_File');
        }
        $content = $info->content;
        if (!$this->_setFileInfo($info)) {
            return (false);
        }
        if ($content !== null) {
            return ($this->setFileContent($info->id, $content));
        }
        return (true);
    }

    /**
     * Get content of shared file with given Id
     *
     * @param Rx_SharedFiles_File|string $id Information structure or Id of shared file to get content of
     * @return mixed
     */
    public function getFileContent($id)
    {
        $content = null;
        if (is_string($id)) {
            $info = $this->_getFileInfo($id);
        } elseif ($id instanceof Rx_SharedFiles_File) {
            $info = $id;
        }
        if ($info instanceof Rx_SharedFiles_File) {
            $content = $this->_getFileContent($info);
        }
        return ($content);
    }

    /**
     * Set content for shared file with given Id
     *
     * @param Rx_SharedFiles_File|string $id Information structure or Id of shared file to set content for
     * @param mixed $content                 Shared file content
     * @return boolean                          true if shared file content was saved successfully, false in a case of error
     */
    public function setFileContent($id, $content)
    {
        if ($id instanceof Rx_SharedFiles_File) {
            $id = $id->id;
        }
        if (!$this->exists($id)) {
            trigger_error('Unknown shared file Id: ' . $id, E_USER_WARNING);
            return (false);
        }
        // Setting content for not cacheable file have no meaning, so ignore it
        if (!$this->getProvider()->isCacheable($id)) {
            return (true);
        }
        // Attempt to set file contents
        $info = $this->_getFileInfo($id);
        if (!$info instanceof Rx_SharedFiles_File) {
            trigger_error('Failed to set shared file content: shared file "' . $id . '" is not exists', E_USER_WARNING);
            return (false);
        }
        $cacheId = $this->_getFileCacheId($info);
        if (!$this->getCache()->save($content, $cacheId)) {
            trigger_error('Failed to save shared file content: cache saving error', E_USER_WARNING);
            return (false);
        }
        $hash = $info->hash;
        $info->setContent($content);
        // Content's information was changed, save index to fix changes
        if ($info->hash != $hash) {
            return ($this->_saveIndex());
        }
        return (true);
    }

    /**
     * Add new file into shared files collection
     *
     * @param Rx_SharedFiles_File $info Shared file information
     * @param mixed $content            OPTIONAL Shared file content
     * @return boolean                      true if shared file was added in collection, false in a case of error
     * @throws Rx_SharedFiles_Exception
     */
    public function addFile($info, $content = null)
    {
        $result = $this->setFileInfo($info);
        if ($content !== null) {
            $result &= $this->setFileContent($info, $content);
        }
        return ($result);
    }

    /**
     * Remove shared file from collection
     *
     * @param string $id Id of shared file to remove
     * @return boolean                  true if shared file was successfully removed, false in a case of error
     */
    public function removeFile($id)
    {
        if (!$this->exists($id)) {
            return (true);
        }
        $result = true;
        $info = $this->_getFileInfo($id);
        if ($info instanceof Rx_SharedFiles_File) {
            $cacheId = $this->_getFileCacheId($info);
            $result &= $this->getCache()->remove($cacheId);
        }
        unset($this->_index[$id]);
        $result &= $this->_saveIndex();
        return ($result);
    }

    /**
     * Wipe all items from shared files collection
     *
     * @return void
     */
    public function clear()
    {
        $this->_loadIndex();
        foreach ($this->_index as $id => $info) {
            $this->removeFile($id);
        }
        $this->_index = array();
        $this->_saveIndex();
    }

    /**
     * Get information about shared file with given Id
     *
     * @param string $id Id of shared file to receive information about
     * @return Rx_SharedFiles_File|null
     */
    protected function _getFileInfo($id)
    {
        $this->_loadIndex();
        if (array_key_exists($id, $this->_index)) {
            $info = $this->_index[$id];
            if ($info === true) {
                $info = $this->getProvider()->getFileInfo($id);
                if ($info instanceof Rx_SharedFiles_File) {
                    unset($info->content);
                }
            }
            return ($info);
        }
        // File information is missed in index, attempt to get it from provider
        $info = $this->getProvider()->getFileInfo($id);
        if ($info instanceof Rx_SharedFiles_File) {
            $this->_setFileInfo($info);
        }
        return ($info);
    }

    /**
     * Set shared file information into collection index
     *
     * @param Rx_SharedFiles_File $info Shared file information structure to set
     * @return boolean
     */
    protected function _setFileInfo($info)
    {
        $this->_loadIndex();
        if ($this->getProvider()->isCacheable($info->id)) {
            // No content should be stored into collection index
            unset($info->content);
            $this->_index[$info->id] = $info;
        } else {
            $this->_index[$info->id] = true;
        }
        return ($this->_saveIndex());
    }

    /**
     * Get content of shared file by its file information
     *
     * @param Rx_SharedFiles_File $info Shared file information to get content for
     * @return mixed
     */
    protected function _getFileContent($info)
    {
        $this->_loadIndex();

        // Check if we have file content in local filesystem
        $path = $info->path;
        if (($path) &&
            (file_exists($path)) &&
            (is_readable($path))
        ) {
            $hash = $info->hash;
            // Content is available from local filesystem. Setting content path
            // back into structure will cause its parameters to be re-calculated
            $info->setPath($path);
            if ($info->hash != $hash) {
                $this->_setFileInfo($info);
            }
            return (file_get_contents($path));
        }

        // Attempt to get file content from cache or information provider
        if ($this->getProvider()->isCacheable($info->id)) {
            // Check if content is available in cache
            $cache = $this->getCache();
            $cacheId = $this->_getFileCacheId($info);
            $content = $cache->load($cacheId);
            if ($content !== false) {
                return ($content);
            }
            // Attempt to get content from provider
            $content = $this->getProvider()->getFileContent($info);
            if ($content !== null) {
                // Cacheable content is retrieved from provider, save it in local cache
                if (!$cache->save($content, $cacheId)) {
                    trigger_error('Failed to save shared files index: cache saving error', E_USER_WARNING);
                }
                $hash = $info->hash;
                // Setting content into file information structure will cause its parameters to be re-calculated
                $info->setContent($content);
                if ($info->hash != $hash) {
                    $this->_setFileInfo($info);
                }
            }
        } else {
            // File is not cacheable, we should take it from provider
            $content = $this->getProvider()->getFileContent($info);
        }
        return ($content);
    }

    /**
     * Get cache Id for shared file collection index
     *
     * @return string
     */
    protected function _getIndexCacheId()
    {
        return ($this->getProvider()->getIndexCacheId());
    }

    /**
     * Get cache Id for shared file content
     *
     * @param Rx_SharedFiles_File $info Shared file information structure
     * @return string
     */
    protected function _getFileCacheId($info)
    {
        return ($this->getProvider()->getFileCacheId($info));
    }

    /**
     * Load shared files collection index from cache
     *
     * @return boolean  true if index was loaded, false if error occurs
     */
    protected function _loadIndex()
    {
        static $loaded = false;

        if ($loaded) {
            return (true);
        }
        $index = $this->getCache()->load($this->_getIndexCacheId());
        if (!is_array($index)) {
            $index = array();
        }
        $this->_index = $index;
        $loaded = true;
        return (true);
    }

    /**
     * Save shared files collection index into cache
     *
     * @return boolean  true if index was saved, false if error occurs
     * @return void
     */
    protected function _saveIndex()
    {
        if (!$this->getCache()->save($this->_index, $this->_getIndexCacheId())) {
            trigger_error('Failed to save shared files index: cache saving error', E_USER_WARNING);
            return (false);
        }
        return (true);
    }

    /**
     * Get provider class by its plugin name
     *
     * @param string $provider Provider class plugin name
     * @return Rx_SharedFiles_Provider_Abstract|null
     * @throws Rx_SharedFiles_Exception
     */
    protected function _getProvider($provider)
    {
        $name = $provider;
        $provider = Rx_Loader::loadPlugin($provider, 'Rx_SharedFiles_Provider');
        if (!$provider) {
            throw new Rx_SharedFiles_Exception('Unable to find shared information provider: ' . $name);
        }
        $provider = new $provider($this->getId());
        if (!$provider instanceof Rx_SharedFiles_Provider_Abstract) {
            throw new Rx_SharedFiles_Exception('Shared files provider must be instance of Rx_SharedFiles_Provider_Abstract');
        }
        return ($provider);
    }

}
