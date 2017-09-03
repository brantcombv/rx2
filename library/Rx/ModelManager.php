<?php

class Rx_ModelManager
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_ModelManager $_instance
     */
    protected static $_instance = null;
    /**
     * Model classes loader
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_loader = null;
    /**
     * Mapping table for class names substitution
     *
     * @var array $_map
     */
    protected $_map = array();
    /**
     * Cache of resolved model class names
     *
     * @var array $_cache
     */
    protected $_cache = array();

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->_loader = Rx_Loader::getPluginLoader('Rx_Model');
        // Read classes mapping from configuration
        $map = Rx_Config::getArray('rx.modelmanager.map');
        foreach ($map as $name => $mapping) {
            $this->addClassMapping($name, $mapping);
        }
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_ModelManager
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Add prefix path to use for auto-loading model classes
     *
     * @param string $prefix Model class name prefix
     * @param string $path   Mapped path
     * @return void
     */
    public function addPrefixPath($prefix, $path)
    {
        $this->_loader->addPrefixPath($prefix, $path);
    }

    /**
     * Set mapping for class name substitution
     *
     * @param string $name         Model class name as passed to get()
     * @param string|null $mapping OPTIONAL Mapping name or null to remove mapping
     * @return void
     */
    public function addClassMapping($name, $mapping = null)
    {
        if ($mapping !== null) {
            $this->_map[$name] = $mapping;
        } else {
            unset($this->_map[$name]);
        }
    }

    /**
     * Get instance of model class with given name
     * After name it is possible to pass additional arguments for class creation
     *
     * @param string $name Name of model class to get instance of
     * @return Rx_Model_Abstract
     * @throws Rx_Exception
     */
    public static function get($name)
    {
        static $_stack = array();

        $instance = self::getInstance();
        $name = strtolower($name);
        $singleton = false;
        $class = $instance->resolve($name, $singleton);
        if ($class === null) {
            throw new Rx_Exception('Unable to find model class: ' . $name);
        }
        if (($singleton) && ($instance->_cache[$name]['instance'] instanceof $class)) {
            return ($instance->_cache[$name]['instance']);
        }
        // Create new model instance
        // We should avoid circular references of singleton models
        // in their constructors because we will not be able to provide valid
        // model instances in this case
        if ($singleton) {
            if (in_array($name, $_stack)) {
                throw new Rx_Exception('Circular models reference in constructor: (' . join(
                        ',',
                        $_stack
                    ) . ')');
            }
            array_push($_stack, $name);
        }
        $reflection = new ReflectionClass($class);
        $args = func_get_args();
        array_shift($args);
        $model = $reflection->newInstanceArgs($args);
        if ($singleton) {
            $instance->_cache[$name]['instance'] = $model;
            array_pop($_stack);
        }
        return ($model);
    }

    /**
     * Get class name of model class with given name
     *
     * @param string $name Name of model class to get class name of
     * @return string|null
     */
    public static function getClass($name)
    {
        $instance = self::getInstance();
        $name = strtolower($name);
        $singleton = false;
        $class = $instance->resolve($name, $singleton);
        return ($class);
    }

    /**
     * Resolve full class name by given model name
     *
     * @param string $name       Name of model class to resolve
     * @param boolean $singleton Reference to variable that will indicate if resolved class is singleton or not
     * @return string|null
     */
    protected function resolve($name, &$singleton)
    {
        $_name = strtolower($name);
        if (!array_key_exists($_name, $this->_cache)) {
            // Check if we have mapping for this class name
            if (array_key_exists($_name, $this->_map)) {
                $_name = $this->_map[$_name];
            }
            $class = @Rx_Loader::loadPlugin($_name, $this->_loader);
            if (!$class) {
                return (null);
            }
            $reflection = new ReflectionClass($class);
            $singleton = ($reflection->hasMethod('isSingleton')) ? (boolean)call_user_func(
                array($class, 'isSingleton')
            ) : false;
            $this->_cache[$name] = array(
                'class'     => $class,
                'singleton' => $singleton,
                'instance'  => null,
            );
        }
        $singleton = $this->_cache[$name]['singleton'];
        return ($this->_cache[$name]['class']);
    }

}
