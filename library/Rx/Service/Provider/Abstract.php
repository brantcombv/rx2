<?php

/**
 * Base implementation of service provider for Rx_Service
 */
abstract class Rx_Service_Provider_Abstract
{
    /**
     * Service provider Id
     *
     * @var string $_id
     */
    protected $_id = null;
    /**
     * Cache for already instantiated services
     *
     * @var array $_cache
     */
    private $_cache = array();

    /**
     * Get Id of this service provider
     *
     * @return string
     */
    public function getId()
    {
        if (!$this->_id) {
            // By default construct service provider Ids from its class name
            $class = get_class($this);
            $class = explode('_', $class);
            $id = array_pop($class);
            $id = strtolower(substr($id, 0, 1)) . substr($id, 1);
            $this->_id = $id;
        }
        return ($this->_id);
    }

    /**
     * Get list of service identifiers that can be provided by this provider
     *
     * @return array
     */
    public function getServicesList()
    {
        // By default service provider is mean to provide single service
        // with same Id as its own Id
        return (array($this->getId()));
    }

    /**
     * Get service object instance by given Id and parameters
     *
     * @param string $serviceId Service Id to get
     * @param mixed $params     OPTIONAL List of parameters to use for constructing service
     * @return object|null
     */
    public function getService($serviceId, $params = null)
    {
        $service = null;
        if (!is_array($params)) {
            $params = ($params !== null) ? array($params) : array();
        }
        if ($this->isCacheable($serviceId, $params)) {
            $key = $this->getCacheKey($serviceId, $params);
            if (!$this->cacheExists($key)) {
                $service = $this->createServiceInstance($serviceId, $params);
                $this->cachePut($key, $service);
            } else {
                $service = $this->cacheGet($key);
            }
        } else {
            $service = $this->createServiceInstance($serviceId, $params);
        }
        return ($service);
    }

    /**
     * Create instance of the service
     *
     * @param string $serviceId Service Id to get
     * @param array $params     List of parameters to use for constructing service
     * @return object|null
     */
    abstract protected function createServiceInstance($serviceId, $params);

    /**
     * Determine if service instance with given Id and parameters can be cached
     *
     * @param string $serviceId Service Id to get
     * @param array $params     List of parameters to use for constructing service
     * @return boolean
     */
    protected function isCacheable($serviceId, $params)
    {
        // By default treat service Id that is requested without parameters as cacheable
        return (!sizeof($params));
    }

    /**
     * Construct cache key for storing service object with given Id and parameters
     *
     * @param string $serviceId Service Id to get
     * @param array $params     List of parameters to use for constructing service
     * @throws Rx_Service_Exception
     * @return string
     */
    protected function getCacheKey($serviceId, $params)
    {
        if (sizeof($params)) {
            throw new Rx_Service_Exception('Cache key for service with parameters can\'t be constructed automatically');
        }
        return ($serviceId);
    }

    /**
     * Check if we have cached entry with given key
     *
     * @param string $key Cache entry key
     * @return boolean
     */
    protected function cacheExists($key)
    {
        return (array_key_exists($key, $this->_cache));
    }

    /**
     * Get cached entry by given key
     *
     * @param string $key Cache entry key
     * @return mixed
     */
    protected function cacheGet($key)
    {
        if (!$this->cacheExists($key)) {
            return (null);
        }
        return ($this->_cache[$key]);
    }

    /**
     * Store given value into cache by given key
     *
     * @param string $key  Cache entry key
     * @param mixed $value Value to store in cache
     * @return void
     */
    protected function cachePut($key, $value)
    {
        $this->_cache[$key] = $value;
    }

}
