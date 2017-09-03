<?php

/**
 * Plugins loader service
 */
class Rx_Service_Loader
{
    /**
     * Cache for registered namespace for autoloader
     *
     * @var array $_namespaces
     */
    protected $_namespaces = array();
    /**
     * Regular expression to cut namespace prefixes from given prefix paths
     *
     * @var string $_nsRegexp
     */
    protected $_nsRegexp = null;
    /**
     * Cache for normalized prefix paths
     *
     * @var array $_prefixPaths
     */
    protected $_prefixPaths = array();
    /**
     * Cache of plugin loaders
     *
     * @var array $_pluginLoaders
     */
    protected $_pluginLoaders = array();
    /**
     * Cache of already loaded classes
     *
     * @var array $_classes
     */
    protected $_classes = array();

    /**
     * Class constructor
     *
     * @return Rx_Service_Loader
     */
    public function __construct()
    {
        $namespaces = Zend_Loader_Autoloader::getInstance()->getRegisteredNamespaces();
        foreach ($namespaces as $ns) {
            $ns = $this->_normalize($ns);
            if (substr($ns, -1) != '_') {
                $ns .= '_';
            }
            $this->_namespaces[] = $ns;
        }
        $this->_nsRegexp = '/^' . join('|', $this->_namespaces) . '/i';
    }

    /**
     * Normalize given prefix paths
     *
     * @param string|array $prefix Prefix names or prefix paths to normalize
     * @return array                    Prefix paths in a form "prefix"=>"path"
     */
    public function getPrefixPath($prefix)
    {
        $key = $this->_getPrefixKey($prefix);
        if (!array_key_exists($key, $this->_prefixPaths)) {
            $result = array();
            if (!is_array($prefix)) {
                $prefix = array($prefix);
            }
            asort($prefix);
            foreach ($prefix as $path) {
                if (strpos($path, '\\') !== false) {
                    // This is class namespace prefix
                    $path = rtrim($path, '\\');
                    $p = str_replace('\\', '/', $path) . '/';
                    $result[$path] = $p;
                } else {
                    // This is normal class prefix
                    $path = preg_replace($this->_nsRegexp, '', str_replace('/', '_', $path));
                    $path = $this->_normalize($path);
                    if (substr($path, -1) != '_') {
                        $path .= '_';
                    }
                    foreach ($this->_namespaces as $ns) {
                        $p = $ns . $path;
                        $result[$p] = str_replace('_', '/', $p);
                    }
                }
            }
            $this->_prefixPaths[$key] = $result;
            // Cache also normalized prefix paths to avoid their duplicated processing
            $rKey = $this->_getPrefixKey($result);
            if (!array_key_exists($rKey, $this->_prefixPaths)) {
                $this->_prefixPaths[$rKey] = $result;
            }
        }
        return ($this->_prefixPaths[$key]);
    }

    /**
     * Get list of possible class names for given plugin class name and prefix
     *
     * @param string $name              Name of plugin class to get class names for
     * @param string|array $prefix      Prefix names or prefix paths to use for building class names
     * @param boolean $onlyAvailable    OPTIONAL true to get only already available classes,
     *                                  false to get all possible class names (default)
     * @param boolean $autoload         OPTIONAL true to attempt to autoload classes to check their availability
     *                                  Used only if $onlyAvailable option is enabled
     * @return array
     */
    public function getClassNames($name, $prefix, $onlyAvailable = false, $autoload = false)
    {
        $classes = array();
        $name = $this->_normalize($name);
        $prefixes = $this->getPrefixPath($prefix);
        foreach ($prefixes as $prefix => $path) {
            $class = $prefix . $name;
            if (($onlyAvailable) && (!class_exists($class, $autoload))) {
                continue;
            }
            $classes[] = $class;
        }
        return ($classes);
    }

    /**
     * Create plugin loader instance and initialize it with given prefix paths
     *
     * @param string|array $prefix Prefix names or prefix path to initialize plugin loader with
     * @return Zend_Loader_PluginLoader
     */
    public function getPluginLoader($prefix)
    {
        $prefix = $this->getPrefixPath($prefix);
        $key = $this->_getPrefixKey($prefix);
        if (!array_key_exists($key, $this->_pluginLoaders)) {
            $loader = new Zend_Loader_PluginLoader($prefix);
            $this->_pluginLoaders[$key] = $loader;
        }
        return ($this->_pluginLoaders[$key]);
    }

    /**
     * Load given class via plugin loader
     *
     * @param string $name                                      Plugin class name to load
     * @param string|array|Zend_Loader_PluginLoader $prefix     One of following:
     *                                                          - Prefix name to use for constructing plugin class name
     *                                                          - Prefix paths to use for plugin autoloader
     *                                                          - Instance of plugin loader to use for loading class
     * @return string|null
     */
    public function loadPlugin($name, $prefix)
    {
        $loader = null;
        if ($prefix instanceof Zend_Loader_PluginLoader) {
            $loader = $prefix;
            $prefix = array_keys($loader->getPaths());
        }
        $prefix = $this->getPrefixPath($prefix);
        $name = $this->_normalize($name);
        $key = $name . '|' . $this->_getPrefixKey($prefix);
        if (!array_key_exists($key, $this->_classes)) {
            $class = null;
            if ((sizeof($prefix) == 1) && (strpos(key($prefix), '\\') !== false)) {
                // We have class namespace defined as a prefix - construct complete class name and load it
                $class = rtrim(key($prefix), '\\') . '\\' . ltrim($name, '\\');
                if (!class_exists($class, true)) {
                    $class = null;
                }
            } elseif (class_exists($name, true)) {
                // If complete class name is passed as plugin name - use it
                $class = $name;
            } else {
                // Try to load class with plugin loader
                if (!$loader) {
                    $loader = $this->getPluginLoader($prefix);
                }
                $class = $loader->load($name, false);
                if (!$class) {
                    // Reverse prefixes order. It should be done by default
                    // but not implemented in class constructor because plugins loader
                    // reverts them during plugin loading process
                    $prefixes = array_keys($prefix);
                    krsort($prefixes);
                    // Class is not found by plugin loader - try to find it with autoloader
                    $flag = Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings();
                    Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
                    foreach ($prefixes as $p) {
                        $class = $p . $name;
                        if (Zend_Loader_Autoloader::autoload($class)) {
                            break;
                        }
                    }
                    Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings($flag);
                }
                if (!class_exists($class)) {
                    $class = null;
                }
            }
            $this->_classes[$key] = $class;
        }
        return ($this->_classes[$key]);
    }

    /**
     * Normalize plugin name passed as "all lowercase"
     *
     * @param string $name Plugin name to normalize
     * @return string
     */
    protected function _normalize($name)
    {
        // Fix for ZF-11950
        return (str_replace(' ', '_', ucwords(str_replace('_', ' ', $name))));
    }

    /**
     * Get key to use for caching information related to given prefix paths
     *
     * @param array $prefix Normalized prefix paths
     * @return string
     */
    protected function _getPrefixKey($prefix)
    {
        $key = Rx_Uid::getUid(serialize($prefix));
        return ($key);
    }

}
