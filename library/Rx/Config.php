<?php

class Rx_Config
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Config $_instance
     */
    protected static $_instance = null;
    /**
     * Configuration object
     *
     * @var Zend_Config $_config
     */
    protected $_config = null;
    /**
     * Already fetched multi-level configuration options
     *
     * @var array $_configOptions
     */
    protected $_configOptions = array();
    /**
     * Already requested multi-level configuration options that are not available in config
     *
     * @var array $_missedOptions
     */
    protected $_missedOptions = array();

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Config
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Get configuration option value by name
     *
     * @param string $name              Name of configuration option to get
     * @param mixed $default            OPTIONAL Default value to return in a case of missed option
     * @param Zend_Config|array $config OPTIONAL Configuration to get option from
     * @return mixed
     */
    public static function get($name, $default = null, $config = null)
    {
        $instance = self::getInstance();
        $found = false;
        $result = $instance->_get($config, $name, $found);
        if (!$found) {
            return ($default);
        }
        if ($result instanceof Zend_Config) {
            $result = $result->toArray();
            if (sizeof($result) > 1) {
                trigger_error('Multiple results found for configuration option: ' . $name, E_USER_WARNING);
            }
            reset($result);
            $result = current($result);
        }
        return ($result);
    }

    /**
     * Get path from configuration option with given name
     *
     * @param string $name                Name of configuration option to get
     * @param mixed $default              OPTIONAL Default value to return in a case of missed option
     *                                    OR true/false to define "pure path" flag value
     * @param Zend_Config|array $config   OPTIONAL Configuration to get option from
     * @return mixed
     */
    public static function getPath($name, $default = null, $config = null)
    {
        $pure = null;
        if (($default === true) || ($default === false)) {
            $pure = $default;
            $default = null;
        }
        $path = self::get($name, $default, $config);
        $path = Rx_Path::normalize($path, $pure);
        return ($path);
    }

    /**
     * Get Zend_Config object that represents configuration option with given name
     *
     * @param string $name              Name of configuration option to get config of
     * @param Zend_Config|array $config OPTIONAL Configuration to get option from
     * @return Zend_Config
     */
    public static function getConfig($name, $config = null)
    {
        $instance = self::getInstance();
        $found = false;
        $result = $instance->_get($config, $name, $found);
        if (!$found) {
            return (new Zend_Config(array(), false));
        }
        if (!$result instanceof Zend_Config) {
            trigger_error(
                'Options set is requested but single value is found for configuration option: ' . $name,
                E_USER_WARNING
            );
            return (new Zend_Config(array($result), false));
        }
        return ($result);
    }

    /**
     * Get array that represents configuration option with given name
     *
     * @param string $name              Name of configuration option to get config of
     * @param Zend_Config|array $config OPTIONAL Configuration to get option from
     * @return array
     */
    public static function getArray($name, $config = null)
    {
        $instance = self::getInstance();
        $found = false;
        $result = $instance->_get($config, $name, $found);
        if (!$found) {
            return (array());
        }
        if (is_array($result)) {
            return ($result);
        } elseif ($result instanceof Zend_Config) {
            $result = $result->toArray();
            return ($result);
        } else {
            trigger_error(
                'Options set is requested but single value is found for configuration option: ' . $name,
                E_USER_WARNING
            );
            return (array($result));
        }
    }

    /**
     * Get configuration object
     *
     * @return Zend_Config
     */
    protected function _getConfig()
    {
        if ($this->_config === null) {
            $config = (Zend_Registry::isRegistered('Rx_Config')) ? Zend_Registry::get('Rx_Config') : null;
            if ($config instanceof Zend_Config) {
                $this->_config = $config;
            } else {
                trigger_error(
                    'Rx_Config expects to get configuration by "Rx_Config" key from Zend_Registry',
                    E_USER_WARNING
                );
                $this->_config = new Zend_Config(array(), false);
            }
        }
        return ($this->_config);
    }

    /**
     * Get configuration option by name from given configuration object
     *
     * @param Zend_Config $config Configuration object to get configuration value from
     * @param string $name        Configuration option name to get
     * @param boolean $found      REFERENCE true if configuration option was found, false if not
     * @return Zend_Config|null
     */
    protected function _get($config, $name, &$found)
    {
        if (is_array($config)) {
            return ($this->_getFromArray($config, $name, $found));
        }
        $cacheable = false;
        $found = false;
        if (($config !== null) && (!$config instanceof Zend_Config)) {
            trigger_error('Custom configuration object should be instance of Zend_Config', E_USER_WARNING);
            $config = null;
        }
        if ($config === null) {
            $config = $this->_getConfig();
            // For non-custom and read-only configuration object we can cache
            // configuration options values for faster repetitive access
            if ($config->readOnly()) {
                $cacheable = true;
            }
        }
        $name = trim($name);
        if ($cacheable) {
            if (array_key_exists($name, $this->_configOptions)) {
                $found = true;
                return ($this->_configOptions[$name]);
            }
            if (in_array($name, $this->_missedOptions)) {
                $found = false;
                return (null);
            }
        }
        $parts = explode('.', $name);
        foreach ($parts as $part) {
            if (!isset($config->$part)) {
                if ($cacheable) {
                    $this->_missedOptions[] = $name;
                }
                $found = false;
                return (null);
            }
            $config = $config->$part;
        }
        if ($cacheable) {
            $this->_configOptions[$name] = $config;
        }
        $found = true;
        return ($config);
    }

    /**
     * Get configuration option by name from given configuration object
     *
     * @param array $config  Configuration object to get configuration value from
     * @param string $name   Configuration option name to get
     * @param boolean $found REFERENCE true if configuration option was found, false if not
     * @return mixed
     */
    protected function _getFromArray(array $config, $name, &$found)
    {
        $result = null;
        $found = true;
        $path = explode('.', $name);
        foreach ($path as $p) {
            if ((!is_array($config)) || (!array_key_exists($p, $config))) {
                $found = false;
                break;
            }
            $result = $config[$p];
            if (is_array($config[$p])) {
                $config = $config[$p];
            }
        }
        if ($found) {
            return ($result);
        } else {
            return (null);
        }
    }

}
