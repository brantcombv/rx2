<?php

class Rx_ImagesInfoManager
{
    protected static $_instance = null;
    protected $_cache = null;
    protected $_cacheTags = array();
    protected $_images = array();
    protected $_modified = array();
    protected $_shutdown = false;

    protected function __construct()
    {
        if (!Zend_Registry::isRegistered('cache')) {
            throw new Rx_Exception('Rx_ImagesInfoManager requires instance of Zend_Cache to be available in registry');
        }
        $this->_cache = Zend_Registry::get('cache');
        $this->_cache = clone($this->_cache);
        $this->_cache->setOption('cache_id_prefix', 'imgmgr_');
        $this->_cacheTags = array('imgmgr');
        $this->_images = array();
        $this->_modified = array();
        $this->_shutdown = false;
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_ImagesInfoManager
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Get image properties by path
     *
     * @param string $url Image url
     * @return array|null       Image properties (or null if image is not available)
     */
    public static function getByUrl($url)
    {
        // We must not process complete urls
        if (preg_match('/^[a-z0-9\-\+]+:\/\//', $url)) {
            return (null);
        }
        $path = Rx_Path::getPathByUrl($url);
        return (self::getByPath($path));
    }

    /**
     * Get image properties by path
     *
     * @param string $path Image path
     * @return array|null       Image properties (or null if image is not available)
     */
    public static function getByPath($path)
    {
        $instance = self::getInstance();
        $dir = dirname($path);
        $hash = Rx_Uid::getUid($dir, true, false);
        $instance->load($dir, $hash);

        $modified = false;
        $info = null;
        $name = basename($path);
        if ((isset($instance->_images[$hash][$name])) &&
            ($instance->_images[$hash][$name]['mtime'] == filemtime($path))
        ) {
            $info = $instance->_images[$hash][$name];
        } else {
            $info = $instance->getImageInfo($path);
            if ($info) {
                $instance->_images[$hash][$name] = $info;
            } else {
                unset($instance->_images[$hash][$name]);
            }
            $instance->setModified($hash);
        }
        return ($info);
    }

    /**
     * Perform shutdown operations
     */
    public static function shutdown()
    {
        $instance = self::getInstance();
        if (($instance->_shutdown) ||
            (!sizeof($instance->_modified))
        ) {
            return;
        }
        if ($instance->_cache instanceof Zend_Cache_Core) {
            foreach (array_keys($instance->_modified) as $cacheId) {
                $instance->_cache->save($instance->_images[$cacheId], $cacheId, $instance->_cacheTags);
                unset($instance->_modified[$cacheId]);
            }
        }
        $instance->_shutdown = true;
    }

    protected function load($dir, $cacheId = null)
    {
        if ($cacheId === null) {
            $cacheId = Rx_Uid::getUid($dir, true, false);
        }
        if (isset($this->_images[$cacheId])) {
            return;
        }
        if (!$this->_cache->test($cacheId)) {
            $this->_images[$cacheId] = array();
            $files = new DirectoryIterator($dir);
            foreach ($files as $file) {
                if ((!$file->isFile()) || (!$file->isReadable())) {
                    continue;
                }
                $info = $this->getImageInfo($file->getPathname());
                if (!$info) {
                    continue;
                }
                $this->_images[$cacheId][$file->getFilename()] = $info;
            }
            $this->setModified($cacheId);
        } else {
            $this->_images[$cacheId] = $this->_cache->load($cacheId);
        }
    }

    protected function setModified($cacheId)
    {
        if (!isset($this->_modified[$cacheId])) {
            $this->_modified[$cacheId] = true;
        }
    }

    protected function getImageInfo($path)
    {
        $map = array(
            IMAGETYPE_BMP  => 'bmp',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_PSD  => 'psd',
            IMAGETYPE_SWF  => 'swf',
        );
        $data = @getimagesize($path);
        if ((!is_array($data)) || (!isset($map[$data[2]]))) {
            return (false);
        }
        $info = array(
            'type'   => $map[$data[2]],
            'width'  => $data[0],
            'height' => $data[1],
            'mtime'  => filemtime($path),
        );
        return ($info);
    }
}
