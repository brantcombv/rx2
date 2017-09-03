<?php

/**
 * Base implementation of fixed information structure
 */
abstract class Rx_Struct_Abstract extends Rx_Configurable_Object implements Countable, Iterator, ArrayAccess, Serializable
{
    /**
     * Variables for implementing Countable and Iterator interfaces
     */
    protected $_index = 0;
    protected $_count = 0;

    /**
     * Structure contents
     *
     * @var array $_struct
     */
    protected $_struct = array();

    /**
     * Class constructor
     *
     * @param array $struct             OPTIONAL Structure fields to set on class creation
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     */
    public function __construct($struct = null, $config = null)
    {
        parent::__construct($config);
        $this->_reset();
        if ($struct !== null) {
            $this->set($struct);
        }
    }

    /**
     * Retrieve value of structure field with given name and return $default if there is no element set.
     *
     * @param string $name                   Structure element name to get value of
     * @param mixed $default                 OPTIONAL Default value to return in a case if element is not available
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return mixed
     */
    public function get($name, $default = null, $config = null)
    {
        $config = $this->getConfig($config);
        $result = $this->_get($name, $default, $config);
        return ($result);
    }

    /**
     * Actual implementation of structure field retrieving by name
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name   Structure element name to get value of
     * @param mixed $default Default value to return in a case if element is not available
     * @param array $config  Configuration options
     * @return mixed
     */
    protected function _get($name, $default, $config)
    {
        $result = $default;
        if (array_key_exists($name, $this->_struct)) {
            $result = $this->_struct[$name];
        } elseif ($config['strict_fields_access']) {
            trigger_error(
                'Attempt to get unavailable property "' . $name . '" for structure "' . get_class($this) . '"',
                E_USER_NOTICE
            );
        }
        return ($result);
    }

    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return ($this->get($name));
    }

    /**
     * Set value of structure field with given name
     *
     * @param string|array $name             Either structure element name to set value of or array of structure fields to set
     * @param mixed $value                   OPTIONAL New value for this element (only if $name is a string)
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function set($name, $value = null, $config = null)
    {
        if ((is_object($name)) && (is_callable(array($name, 'toArray')))) {
            $name = $name->toArray();
        }
        $config = $this->getConfig($config);
        if (!is_array($name)) {
            $name = array($name => $value);
        }
        foreach ($name as $k => $v) {
            $this->_set($k, $v, $config);
        }
    }

    /**
     * Actual implementation of setting structure field value.
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name  Structure element name to set value of
     * @param mixed $value  New value for this element
     * @param array $config Configuration options
     * @return void
     */
    protected function _set($name, $value, $config)
    {
        if (array_key_exists($name, $this->_struct)) {
            $this->_struct[$name] = $value;
        } elseif ($name !== null) {
            if ($config['strict_fields_access']) {
                trigger_error(
                    'Attempt to set unavailable property "' . $name . '" for structure "' . get_class($this) . '"',
                    E_USER_NOTICE
                );
            }
        }
    }

    /**
     * Magic function for setting structure fields values
     *
     * @param string $name Structure element name to set value of
     * @param mixed $value New value for this element
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Return structure as associative array
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $keys = array_keys($this->_struct);
        $config = $this->getConfig();
        // Do not use direct retrieving of values from structure table
        // because get() method may use additional value processing
        foreach ($keys as $key) {
            $array[$key] = $this->_get($key, null, $config);
        }
        return ($array);
    }

    /**
     * Support isset() overloading on PHP 5.1
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return (array_key_exists($name, $this->_struct));
    }

    /**
     * Support unset() overloading on PHP 5.1
     * Unsetting field name in a term of removing it from structure is not allowed,
     * so unset() just wipes field's value.
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        $this->set($name, null);
    }

    /**
     * Defined by Countable interface
     *
     * @return int
     */
    public function count()
    {
        return ($this->_count);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        return ($this->get(key($this->_struct)));
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return (key($this->_struct));
    }

    /**
     * Defined by Iterator interface
     *
     * @return void
     */
    public function next()
    {
        next($this->_struct);
        $this->_index++;
    }

    /**
     * Defined by Iterator interface
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->_struct);
        $this->_index = 0;
    }

    /**
     * Defined by Iterator interface
     *
     * @return boolean
     */
    public function valid()
    {
        return ($this->_index < $this->_count);
    }

    /**
     * Defined by ArrayAccess interface
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return ($this->__isset($offset));
    }

    /**
     * Defined by ArrayAccess interface
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return ($this->get($offset));
    }

    /**
     * Defined by ArrayAccess interface
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * Defined by ArrayAccess interface
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * Implementation of Serializable interface
     *
     * @return string
     */
    public function serialize()
    {
        return (serialize($this->_struct));
    }

    /**
     * Implementation of Serializable interface
     *
     * @param array $data Serialized object data
     * @return void
     */
    public function unserialize($data)
    {
        $this->_reset();
        $data = @unserialize($data);
        if (!is_array($data)) {
            return;
        }
        foreach ($data as $name => $value) {
            if (array_key_exists($name, $this->_struct)) {
                $this->_struct[$name] = $value;
            }
        }
    }

    /**
     * Reset structure to its initial state
     *
     * @return void
     */
    public function reset()
    {
        $this->_reset();
    }

    /**
     * Reset class member variables before re-initializing them
     *
     * @return void
     */
    protected function _reset()
    {
        $this->getConfig(); // Make sure that configuration is initialized
        $struct = $this->init();
        if (is_array($struct)) {
            $this->_struct = array();
            foreach ($struct as $name => $value) {
                if (is_int($name)) {
                    $this->_struct[$value] = null;
                } else {
                    $this->_struct[$name] = $value;
                }
            }
        }
        $this->_index = 0;
        $this->_count = sizeof($this->_struct);
    }

    /**
     * Initialize structure fields list
     * Method can either return list of structure fields (then structure will be initialized by standard method)
     * or initialize $_struct variable by itself, in this case no further modifications will be performed
     * NOTE: Initialization of structure fields list directly in init() is about twice faster then just returning list of fields
     *
     * @return array|void   Initial structure state
     */
    abstract protected function init();

    /**
     * Initialize list of configuration options
     *
     * @return void
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'strict_fields_access' => true, // true to trigger errors upon access to unavailable structure fields,
            // false to silently ignore such requests
        ));
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
        switch ($name) {
            case 'strict_fields_access':
                $value = (boolean)$value;
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

}
