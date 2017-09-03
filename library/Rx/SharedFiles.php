<?php

class Rx_SharedFiles
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_SharedFiles $_instance
     */
    protected static $_instance = null;
    /**
     * List of registered shared files collections
     *
     * @var array $_collections
     */
    protected $_collections = array();

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_SharedFiles
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Register shared files collection object
     *
     * @param string|Rx_SharedFiles_Collection $id              Shared files collection object or Id
     * @param Rx_SharedFiles_Provider_Abstract|string $provider OPTIONAL Provider class for this shared files collection
     * @param Zend_Cache_Core $cache                            OPTIONAL Cache to store shared files to
     * @return Rx_SharedFiles_Collection                            Registered collection
     * @throws Rx_SharedFiles_Exception
     */
    public static function register($id, $provider = null, $cache = null)
    {
        $instance = self::getInstance();
        if ($id instanceof Rx_SharedFiles_Collection) {
            $instance->_collections[$id->getId()] = $id;
            return ($id);
        } elseif (is_string($id)) {
            $collection = new Rx_SharedFiles_Collection($id, $provider, $cache);
            $instance->_collections[$id] = $collection;
            return ($collection);
        } else {
            throw new Rx_SharedFiles_Exception('Registered shared collection should be either string or instance of Rx_SharedFiles_Collection');
        }
    }

    /**
     * Get shared files collection object with given Id
     *
     * @param string $id            Id of shared files collection to get
     * @param boolean $create       OPTIONAL true to attempt to auto-register shared files collection if missed,
     *                              false to return null for missed collection
     * @return Rx_SharedFiles_Collection|null
     */
    public static function get($id, $create = false)
    {
        $instance = self::getInstance();
        if (array_key_exists($id, $instance->_collections)) {
            return ($instance->_collections[$id]);
        } elseif ($create) {
            $collection = self::register($id);
            return ($collection);
        }
        return (null);
    }

}
