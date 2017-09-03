<?php

class Rx_Crypt
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Crypt $_instance
     */
    protected static $_instance = null;
    /**
     * List of loaded encryption adapters
     *
     * @var array $_adapters
     */
    protected $_adapters = array();
    /**
     * Loader for encryption adapters
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_adapterLoader = null;
    /**
     * List of loaded encryption providers
     *
     * @var array $_providers
     */
    protected $_providers = array();
    /**
     * Loader for encryption providers
     *
     * @var Zend_Loader_PluginLoader $_providerLoader
     */
    protected $_providerLoader = null;

    protected function __construct()
    {
        $this->_adapterLoader = Rx_Loader::getPluginLoader('Rx_Crypt_Adapter');
        $this->_providerLoader = Rx_Loader::getPluginLoader('Rx_Crypt_Provider');
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Crypt
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Get crypt adapter object by given Id
     *
     * @param string $id                Crypt adapter Id to get
     * @param array|Zend_Config $config OPTIONAL Additional configuration options for adapter
     * @return Rx_Crypt_Adapter_Abstract
     * @throws Rx_Crypt_Exception
     */
    public static function getAdapter($id, $config = null)
    {
        $instance = self::getInstance();
        $adapter = null;
        if (!isset($instance->_adapters[$id])) {
            $class = Rx_Loader::loadPlugin($id, $instance->_adapterLoader);
            if (!$class) {
                trigger_error('Unavailable crypt adapter Id: ' . $id, E_USER_WARNING);
                return (null);
            }
            $class = new $class();
            if (!$class instanceof Rx_Crypt_Adapter_Abstract) {
                throw new Rx_Crypt_Exception('Crypt adapter class for Id "' . $id . '" must be instance of Rx_Crypt_Adapter_Abstract');
            }
            $instance->_adapters[$id] = $class;
            $adapter = $class;
        } else {
            $adapter = $instance->_adapters[$id];
        }
        if ($adapter) {
            $adapter = clone($adapter);
            $adapter->setConfig($config);
        }
        return ($adapter);
    }

    /**
     * Get crypt provider object by given Id
     *
     * @param string $id                Crypt provider Id to get
     * @param array|Zend_Config $config OPTIONAL Additional configuration options for adapter
     * @return Rx_Crypt_Provider_Abstract
     * @throws Rx_Crypt_Exception
     */
    public static function getProvider($id, $config = null)
    {
        $instance = self::getInstance();
        $provider = null;
        if (!isset($instance->_providers[$id])) {
            $class = Rx_Loader::loadPlugin($id, $instance->_providerLoader);
            if (!$class) {
                trigger_error('Unavailable crypt provider Id: ' . $id, E_USER_WARNING);
                return (null);
            }
            $class = new $class();
            if (!$class instanceof Rx_Crypt_Provider_Abstract) {
                throw new Rx_Crypt_Exception('Crypt provider class for Id "' . $id . '" must be instance of Rx_Crypt_Provider_Abstract');
            }
            $instance->_providers[$id] = $class;
            $provider = $class;
        } else {
            $provider = $instance->_providers[$id];
        }
        if ($provider) {
            $provider = clone($provider);
            $provider->setConfig($config);
        }
        return ($provider);
    }

    /**
     * Encrypt given content using crypt adapter with given Id
     *
     * @param string $id                Crypt adapter Id to use for encryption
     * @param string $content           Content to encrypt
     * @param array|Zend_Config $config OPTIONAL Additional configuration options for adapter
     * @return string|boolean               Encrypted content or false in a case of error
     * @throws Rx_Crypt_Exception
     */
    public static function encrypt($id, $content, $config = null)
    {
        $adapter = self::getAdapter($id, $config);
        if (!is_object($adapter)) {
            return (false);
        }
        $result = $adapter->encrypt($content, $config);
        return ($result);
    }

    /**
     * Decrypt given content using crypt adapter with given Id
     *
     * @param string $id                Crypt adapter Id to use for decryption
     * @param string $content           Content to decrypt
     * @param array|Zend_Config $config OPTIONAL Additional configuration options for adapter
     * @return string|boolean               Decrypted content or false in a case of error
     * @throws Rx_Crypt_Exception
     */
    public static function decrypt($id, $content, $config = null)
    {
        $adapter = self::getAdapter($id, $config);
        if (!is_object($adapter)) {
            return (false);
        }
        $result = $adapter->decrypt($content, $config);
        return ($result);
    }

    /**
     * Generate initialization vector for encryption
     *
     * @param string $id       Crypt adapter Id to use for generation
     * @param string $strength OPTIONAL Encryption strength
     * @return string|boolean       Initialization vector or false in a case of error
     */
    public static function generateIv($id, $strength = null)
    {
        $adapter = self::getAdapter($id);
        if (!is_object($adapter)) {
            return (false);
        }
        $iv = $adapter->generateIv($strength);
        return ($iv);
    }

    /**
     * Register new prefix path for crypt adapters loader
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Loader_PluginLoader
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     */
    public static function addAdapterPrefixPath($prefix, $path)
    {
        return (self::getInstance()->_adapterLoader->addPrefixPath($prefix, $path));
    }

    /**
     * Register new prefix path for crypt providers loader
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Loader_PluginLoader
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     */
    public static function addProviderPrefixPath($prefix, $path)
    {
        return (self::getInstance()->_providerLoader->addPrefixPath($prefix, $path));
    }

}
