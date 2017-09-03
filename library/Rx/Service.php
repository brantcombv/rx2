<?php

/**
 * Services management class. Provides centralized registry of service objects
 * to allow application to have access to them.
 * Implements concept of "service providers" to allow application to provide services
 * in a more flexible way
 *
 * @method Rx_Service_Loader getLoader()
 */
class Rx_Service
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Service $_instance
     */
    protected static $_instance = null;
    /**
     * List of registered service providers
     *
     * @var array $_providers
     */
    protected $_providers = array();
    /**
     * List of known services and their providers
     *
     * @var array $_services
     */
    protected $_services = array();
    /**
     * Plugin loader to use for loading service providers
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_loader = null;

    /**
     * Class constructor.
     * Disabled for public because of Singleton pattern implementation
     */
    private function __construct()
    {
        // Register "loader" service provider by default
        // to be able to load other service providers
        $loader = new Rx_Service_Provider_Loader();
        $this->registerProvider($loader);
    }

    /**
     * Class cloning handler.
     * Disabled for public because of Singleton pattern implementation
     */
    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Service
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Get plugin loader that is used to load service providers
     *
     * @return Zend_Loader_PluginLoader
     */
    public function getServiceProviderLoader()
    {
        if (!$this->_loader) {
            $this->_loader = new Zend_Loader_PluginLoader();
            $prefixes = $this->getLoader()->getPrefixPath('Rx_Service_Provider');
            foreach ($prefixes as $prefix => $path) {
                $this->_loader->addPrefixPath($prefix, $path);
            }
        }
        return ($this->_loader);
    }

    /**
     * Get service provider by given Id
     *
     * @param string $providerId Id or class name of service provider to get
     * @throws Rx_Service_Exception
     * @return Rx_Service_Provider_Abstract
     */
    public function getServiceProvider($providerId)
    {
        if (!is_string($providerId)) {
            throw new Rx_Service_Exception('Service provider Id must be a string');
        }
        $providerId = $this->normalizeId($providerId);
        if (array_key_exists($providerId, $this->_providers)) {
            return ($this->_providers[$providerId]);
        } else {
            $provider = null;
            if (class_exists($providerId, true)) {
                // Provider Id is given as complete class name
                $provider = new $providerId();
            } else {
                // Attempt to load service provider class by given Id
                $provider = $this->getServiceProviderLoader()->load($providerId, false);
                if ($provider) {
                    $provider = new $provider();
                }
            }
            if (!$provider) {
                throw new Rx_Service_Exception('No service provider is available: ' . $providerId);
            } elseif (!$provider instanceof Rx_Service_Provider_Abstract) {
                throw new Rx_Service_Exception('Service provider must be instance of Rx_Service_Provider_Abstract');
            }
            $this->_providers[$provider->getId()] = $provider;
            return ($provider);
        }
    }

    /**
     * Register service provider
     *
     * @param Rx_Service_Provider_Abstract|string $provider Provider instance or class name to register
     * @throws Rx_Service_Exception
     * @return void
     */
    public function registerProvider($provider)
    {
        if (is_string($provider)) {
            $provider = $this->getServiceProvider($provider);
        }
        if (!$provider instanceof Rx_Service_Provider_Abstract) {
            throw new Rx_Service_Exception('Service provider must be instance of Rx_Service_Provider_Abstract');
        }
        $services = $provider->getServicesList();
        foreach ($services as $service) {
            $this->_services[$service] = $provider;
        }
    }

    /**
     * Get service by given Id and parameters
     *
     * @param string $serviceId     Service Id to get
     * @param mixed $params,...     OPTIONAL Arbitrary list of additional parameters
     *                              to use for creating service
     * @throws Rx_Service_Exception
     * @return object
     */
    public function get($serviceId, $params = null)
    {
        if (!is_string($serviceId)) {
            throw new Rx_Service_Exception('Service Id must be a string');
        }
        $serviceId = $this->normalizeId($serviceId);
        if (!array_key_exists($serviceId, $this->_services)) {
            // We don't know such service - attempt to find provider for it
            $provider = $this->getServiceProvider($serviceId);
            if ($provider) {
                // We seems to have provider - try to register it
                try {
                    $this->registerProvider($provider);
                } catch (Rx_Service_Exception $e) {
                    // Silently hide registration exceptions
                    // because we can't be really sure if provider that we found is correct one
                }
            }
            if (!array_key_exists($serviceId, $this->_services)) {
                throw new Rx_Service_Exception('Unknown service: ' . $serviceId);
            }
        }
        /** @var $provider Rx_Service_Provider_Abstract */
        $provider = $this->_services[$serviceId];
        $params = func_get_args();
        array_shift($params);
        $service = $provider->getService($serviceId, $params);
        return ($service);
    }

    /**
     * Normalize given provider/service Id
     *
     * @param string $id
     * @return string
     */
    protected function normalizeId($id)
    {
        $id = strtolower(substr($id, 0, 1)) . substr($id, 1);
        return ($id);
    }

    /**
     * Handle services access by calling "magic" get<ServiceId> methods
     *
     * @param string $method   Called method
     * @param array $arguments List of method arguments
     * @throws Rx_Service_Exception
     * @return object|null
     */
    public function __call($method, $arguments)
    {
        if (!preg_match('/^get([A-Z][a-zA-Z0-9]+)$/', $method, $t)) {
            throw new Rx_Service_Exception('Unknown method call: ' . $method);
        }
        array_unshift($arguments, $t[1]);
        return (call_user_func_array(array($this, 'get'), $arguments));
    }

    /**
     * Handle services access by calling "magic" get<ServiceId> methods
     *
     * @param string $method   Called method
     * @param array $arguments List of method arguments
     * @return object|null
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = self::getInstance();
        return (call_user_func_array(array($instance, $method), $arguments));
    }

}
