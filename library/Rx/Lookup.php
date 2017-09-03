<?php

class Rx_Lookup
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Lookup $_instance
     */
    protected static $_instance = null;
    /**
     * Lookup tables loader
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_loader = null;
    /**
     * Cache for already loaded lookup tables
     *
     * @var array $_lookups
     */
    protected $_lookups = array();

    /**
     * Class constructor
     *
     * @return Rx_Lookup
     */
    protected function __construct()
    {
        $this->_loader = Rx_Loader::getPluginLoader('Rx_Lookup');
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Lookup
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Get lookup object by given type
     *
     * @param string $type              Lookup type to get
     * @param array|Zend_Config $config OPTIONAL Additional configuration options for lookup
     * @return Rx_Lookup_Abstract
     */
    public static function get($type, $config = null)
    {
        $lookup = self::getInstance()->getLookup($type);
        if ($lookup) {
            $lookup->setConfig($config);
        }
        return ($lookup);
    }

    /**
     * Magic function for getting lookup objects by type name
     */
    public function __get($type)
    {
        return ($this->get($type));
    }

    /**
     * Register new prefix path for lookup plugins loader
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Loader_PluginLoader
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     */
    public static function addPrefixPath($prefix, $path)
    {
        return (self::getInstance()->_loader->addPrefixPath($prefix, $path));
    }

    /**
     * Get lookup object with given name
     *
     * @param string $type Lookup object type to load
     * @return Rx_Lookup_Abstract
     * @throws Rx_Lookup_Exception
     */
    protected function getLookup($type)
    {
        $instance = self::getInstance();
        if (!array_key_exists($type, $instance->_lookups)) {
            $class = Rx_Loader::loadPlugin($type, $instance->_loader);
            if ($class) {
                $class = new $class();
                if (!$class instanceof Rx_Lookup_Abstract) {
                    throw new Rx_Lookup_Exception('Lookup class of type "' . $type . '" must be instance of Rx_Lookup_Abstract');
                }
            } else {
                trigger_error('Unavailable lookup type: ' . $type, E_USER_WARNING);
            }
            $instance->_lookups[$type] = $class;
        }
        return ($instance->_lookups[$type]);
    }

}
