<?php

class Rx_Loader
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Loader $_instance
     */
    protected static $_instance = null;
    /**
     * Loader service instance
     *
     * @var Rx_Service_Loader $_loader
     */
    protected $_loader = null;

    /**
     * Class constructor
     *
     * @return Rx_Loader
     */
    private function __construct()
    {
        $this->_loader = Rx_Service::getInstance()->get('loader');
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Loader
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Normalize given prefix paths
     *
     * @param string|array $prefix Prefix names or prefix paths to normalize
     * @return array                    Prefix paths in a form "prefix"=>"path"
     */
    public static function getPrefixPath($prefix)
    {
        return (self::getInstance()->_loader->getPrefixPath($prefix));
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
    public static function getClassNames($name, $prefix, $onlyAvailable = false, $autoload = false)
    {
        return (self::getInstance()->_loader->getClassNames($name, $prefix, $onlyAvailable, $autoload));
    }

    /**
     * Create plugin loader instance and initialize it with given prefix paths
     *
     * @param string|array $prefix Prefix names or prefix path to initialize plugin loader with
     * @return Zend_Loader_PluginLoader
     */
    public static function getPluginLoader($prefix)
    {
        return (self::getInstance()->_loader->getPluginLoader($prefix));
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
    public static function loadPlugin($name, $prefix)
    {
        return (self::getInstance()->_loader->loadPlugin($name, $prefix));
    }

}
