<?php

/**
 * Generic implementation of object's internal configuration
 */
abstract class Rx_Configurable_Abstract
{
    /**#@+
     * Name of array item within configuration options array
     * that defines ID of base class for configuration options set
     */
    const CONFIG_CLASS_ID_KEY = '__config__';
    /**#@-*/

    /**#@+
     * Types of currently performed operation
     */
    const CONFIG_OPERATION_GET = 'get';
    const CONFIG_OPERATION_SET = 'set';
    const CONFIG_OPERATION_PATCH = 'patch';
    const CONFIG_OPERATION_MERGE = 'merge';
    /**#@-*/

    /**
     * Configuration options
     *
     * @var array $_config
     */
    private $_config = null;
    /**
     * Patch for configuration options from patch provider
     *
     * @var array $_configPatch
     */
    private $_configPatch = null;
    /**
     * Callback to function that is responsible for providing patches
     * for configuration options set
     *
     * @var callback|null|boolean $_configPatchProvider
     */
    private $_configPatchProvider = null;
    /**
     * true if configuration options bootstrap is being performed, false otherwise
     *
     * @var boolean $_configInBootstrap
     */
    private $_configInBootstrap = false;
    /**
     * Mapping table between class name and its configuration options set
     *
     * @var array $_configClassesMap
     */
    private static $_configClassesMap = array();

    /**
     * Check if configuration option with given name is available in object configuration
     *
     * @param string $name Configuration option name
     * @return boolean
     */
    public final function isConfigExists($name)
    {
        return (((is_array($this->_config)) && (array_key_exists($name, $this->_config))));
    }

    /**
     * Set configuration options for object
     *
     * @param array|string|Zend_Config $config      Configuration options to set
     * @param mixed $value                          If first parameter is passed as string then it will be treated as
     *                                              configuration option name and $value as its value
     * @return void
     */
    public final function setConfig($config, $value = null)
    {
        if ((is_object($config)) && (is_callable(array($config, 'toArray')))) {
            $config = $config->toArray();
        }
        if ((is_string($config)) && (strlen($config))) {
            $config = array($config => $value);
        }
        if ((!is_array($config)) || (!sizeof($config))) {
            return;
        }
        if (!is_array($this->_config)) {
            $this->_bootstrapConfig();
        }
        $patch = $this->_getConfigPatch();
        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $this->_config)) {
                continue;
            }
            if (!$this->_checkConfig($key, $value, self::CONFIG_OPERATION_SET)) {
                continue;
            }
            if (array_key_exists($key, $patch)) {
                $value = $patch[$key];
            }
            $this->_config[$key] = $value;
            $this->_onConfigChanged($key, $value, self::CONFIG_OPERATION_SET);
        }
    }

    /**
     * Get object's configuration or configuration option with given name
     * If argument is passed as string - value of configuration option with this name will be returned
     * If argument is some kind of configuration options set - it will be merged with current object's configuration and returned
     * If no argument is passed - current object's configuration will be returned
     *
     * @param string|array|Zend_Config|null $config OPTIONAL Option name to get or configuration options
     *                                              to override default object's configuration.
     * @return mixed
     */
    public function getConfig($config = null)
    {
        if (!is_array($this->_config)) {
            $this->_bootstrapConfig();
        }
        if ($config === null) {
            $config = $this->_config;
            $config[self::CONFIG_CLASS_ID_KEY] = $this->_getConfigClassId();
            return ($config);
        } elseif (is_string($config)) {
            // This is request for configuration option value
            if (array_key_exists($config, $this->_config)) {
                return ($this->_config[$config]);
            } else {
                return (null);
            }
        } elseif ((is_array($config)) &&
            (array_key_exists(self::CONFIG_CLASS_ID_KEY, $config)) && // This is repetitive call to getConfig()
            ($config[self::CONFIG_CLASS_ID_KEY] == $this->_getConfigClassId())
        ) // Only classes with same configuration class Id can share configurations
        {
            return ($config);
        }
        // This is request for configuration (with possible merging)
        if ((is_object($config)) && (is_callable(array($config, 'toArray')))) {
            $config = $config->toArray();
        }
        if (!is_array($config)) {
            $config = $this->_config;
            $config[self::CONFIG_CLASS_ID_KEY] = $this->_getConfigClassId();
            return ($config);
        }
        $result = $this->_config;
        $result[self::CONFIG_CLASS_ID_KEY] = $this->_getConfigClassId();
        foreach ($config as $k => $v) {
            if ((!array_key_exists($k, $result)) || ($k == self::CONFIG_CLASS_ID_KEY)) {
                continue;
            }
            $value = $config[$k];
            if ($this->_checkConfig($k, $value, self::CONFIG_OPERATION_GET)) {
                $result[$k] = $value;
            }
        }
        return ($result);
    }

    /**
     * Get patch for object configuration options from patch provider
     *
     * @return array
     */
    protected function _getConfigPatch()
    {
        if (!is_array($this->_configPatch)) {
            $patch = array();
            if (is_callable($this->_configPatchProvider)) {
                $patchInfo = call_user_func_array($this->_configPatchProvider, array($this, $this->_config));
                if (!is_array($patchInfo)) {
                    $patchInfo = array();
                }
                foreach ($patchInfo as $name => $value) {
                    if (!$this->_checkConfig($name, $value, self::CONFIG_OPERATION_PATCH)) {
                        continue;
                    }
                    $patch[$name] = $value;
                }
            }
            $this->_configPatch = $patch;
        }
        return ($this->_configPatch);
    }

    /**
     * Get Id of configuration class that is used for given class
     *
     * @param string|null $class OPTIONAL Class name to get configuration class Id for
     * @return string
     */
    protected function _getConfigClassId($class = null)
    {
        if ($class === null) {
            $class = get_class($this);
        }
        if (!array_key_exists($class, self::$_configClassesMap)) {
            // Determine which class actually defines configuration for given class
            $reflection = new ReflectionClass($class);
            $id = $reflection->getMethod('_initConfig')->getDeclaringClass()->getName();
            self::$_configClassesMap[$class] = $id;
        }
        return (self::$_configClassesMap[$class]);
    }

    /**
     * Apply given modifications to given object configuration set and return resulted configuration
     *
     * @param array|Zend_Config $config     Object configuration options set
     * @param array|string $modification    Configuration modifications to apply to given object configuration
     * @param mixed $value                  OPTIONAL If $modification is passed as string - it is treated
     *                                      as single option name to modify and $value will be treated as new
     *                                      option value in this case. Ignored otherwise.
     * @return array
     */
    public function modifyConfig($config, $modification, $value = null)
    {
        // Call getConfig() for given configuration options, but only if it is necessary
        // Without this check it is possible to get infinite recursion loop in a case
        // if getConfig() is overridden and calls modifyConfig() by itself
        if ((!is_array($config)) ||
            (!array_key_exists(self::CONFIG_CLASS_ID_KEY, $config)) ||
            ($config[self::CONFIG_CLASS_ID_KEY] != $this->_getConfigClassId())
        ) {
            $config = $this->getConfig($config);
        }
        if (is_string($modification)) {
            $modification = array($modification => $value);
        }
        $modification = $this->_normalizeConfig($modification);
        foreach ($modification as $name => $value) {
            if ($name == self::CONFIG_CLASS_ID_KEY) {
                continue;
            }
            $config[$name] = $value;
        }
        return ($config);
    }

    /**
     * Normalize given configuration options set
     *
     * @param array|Zend_Config|null $config    Configuration options set that was passed
     *                                          to configurable class method
     * @return array
     */
    protected function _normalizeConfig($config)
    {
        $normalized = array();
        if (!is_array($this->_config)) {
            $this->_bootstrapConfig();
        }
        if ((is_object($config)) && (is_callable(array($config, 'toArray')))) {
            $config = $config->toArray();
        }
        if (!is_array($config)) {
            return ($normalized);
        }
        foreach ($config as $k => $v) {
            if ((!array_key_exists($k, $this->_config)) || ($k == self::CONFIG_CLASS_ID_KEY)) {
                continue;
            }
            $value = $config[$k];
            if ($this->_checkConfig($k, $value, self::CONFIG_OPERATION_GET)) {
                $normalized[$k] = $value;
            }
        }
        return ($normalized);
    }

    /**
     * Merge given configuration options with current configuration options
     *
     * @param array $config Configuration options to merge
     * @return void
     */
    protected final function _mergeConfig($config)
    {
        if (!is_array($this->_config)) {
            $this->_bootstrapConfig();
        }
        if (!is_array($config)) {
            return;
        }
        $patch = $this->_getConfigPatch();
        foreach ($config as $key => $value) {
            if ((!$this->_configInBootstrap) && (!$this->_checkConfig($key, $value, self::CONFIG_OPERATION_MERGE))) {
                continue;
            }
            if (array_key_exists($key, $patch)) {
                $value = $patch[$key];
            }
            $this->_config[$key] = $value;
            if (!$this->_configInBootstrap) {
                $this->_onConfigChanged($key, $value, self::CONFIG_OPERATION_MERGE);
            }
            // Clear config patch information because list of configuration options is changed
            // and it may cause patch information to change
            $this->_configPatch = null;
        }
    }

    /**
     * Bootstrap object configuration options
     *
     * @return void
     */
    protected function _bootstrapConfig()
    {
        if ((is_array($this->_config)) || ($this->_configInBootstrap)) {
            return;
        }
        $this->_configInBootstrap = true;
        $this->_initConfig();
        $this->_configInBootstrap = false;
    }

    /**
     * Initialize list of configuration options
     *
     * @return void
     */
    protected function _initConfig()
    {
        // This method is mean to be overridden to provide configuration options set.
        // To allow inheritance of configuration options sets across several levels
        // of inherited classes - this method in nested classes should look like this:
        // parent::_initConfig();
        // $this->_mergeConfig(array(
        //     'option' => 'default value',
        // ));
        $this->_config = array();
        $this->_configPatch = null;
        $provider = $this->_getConfigPatchProvider();
        if (is_callable($provider)) {
            $this->_configPatchProvider = $provider;
        }
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        // This method is mean to be overridden in a case if additional validation
        // of configuration option value should be performed before using it
        // into current operation (identified in $operation argument)
        // Method should validate and, if required, normalize given value
        // of configuration option and return true if option can be used and false if not
        // It is important that this method will be:
        // - as simple as possible to optimize performance
        // - will not call other methods that attempts to modify or merge object configuration
        //   to avoid infinite loop
        return (true);
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Configuration option value
     * @param string $operation Current operation Id
     * @return void
     */
    protected function _onConfigChanged($name, $value, $operation)
    {
        // This method is mean to be overridden in a case if some kind of additional logic
        // is required to be performed upon setting value of configuration option.
    }

    /**
     * Get callback to function that is responsible for providing patches
     * for object's configuration options set
     *
     * @return callback|null
     */
    protected function _getConfigPatchProvider()
    {
        // - Callback function should accept two arguments:
        //   1. instance of object to get configuration patch for
        //   2. array with current set of object's configuration options
        // - Callback should return array with patched default values
        //   for object's configuration options.
        // - Any non-array values will be treated as "no patch"
        return (null);
    }

}
