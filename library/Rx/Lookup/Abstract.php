<?php

abstract class Rx_Lookup_Abstract extends Rx_Configurable_Object implements Countable, Iterator, ArrayAccess
{
    /**
     * Variables for implementing Countable and Iterator interfaces
     */
    protected $_index = 0;
    protected $_count = 0;

    /**
     * Contains lookup table values
     *
     * @var array $_lookup
     */
    protected $_lookup = array();
    protected $_keys = array();

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     * @return void
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $lookup = $this->init();
        if (is_array($lookup)) {
            $this->_lookup = $lookup;
        }
        $this->_keys = array();
        if (function_exists('mb_strtolower')) {
            foreach ($this->_lookup as $k => $v) {
                $this->_keys[mb_strtolower($k)] = $k;
            }
        } else {
            foreach ($this->_lookup as $k => $v) {
                $this->_keys[strtolower($k)] = $k;
            }
        }
        $this->_index = 0;
        $this->_count = sizeof($this->_lookup);
    }

    /**
     * Initialize lookup table contents
     *
     * @return array|void
     */
    abstract protected function init();

    /**
     * Retrieve a value and return $default if there is no element set.
     *
     * @param string $name                   Lookup element name
     * @param mixed $default                 Default value to return in a case if element is not available (can be skipped)
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return mixed
     */
    public function get($name, $default = null, $config = null)
    {
        if (is_array($default)) {
            $config = $default;
            $default = null;
        }
        $_config = $this->getConfig($config);
        $result = $this->_get($name, $default, $_config);
        // Perform lookup value translation if necessary
        if ((is_string($result)) && ($_config['translate'])) {
            if ($_config['translatePrefix']) {
                $key = null;
                if (array_key_exists($name, $this->_lookup)) {
                    $key = $name;
                } else {
                    $name = (function_exists('mb_strtolower')) ? mb_strtolower($name) : strtolower($name);
                    if (array_key_exists($name, $this->_keys)) {
                        $key = $name;
                    }
                }
                if ($key !== null) {
                    $key = (function_exists('mb_strtolower')) ? mb_strtolower($key) : strtolower($key);
                    $text = $_config['translatePrefix'] . $key;
                }
                $translation = Rx_Translate::translate($text, false, $_config['language']);
                if (($translation !== null) && ($translation != $text)) {
                    $result = $translation;
                }
            } else {
                $result = Rx_Translate::translate($result, false, $_config['language']);
            }
        }
        return ($result);
    }

    /**
     * Actual implementation of lookup value retrieving
     */
    protected function _get($name, $default, $config)
    {
        $result = $default;
        if ($config['noCase']) {
            $name = (function_exists('mb_strtolower')) ? mb_strtolower($name) : strtolower($name);
            if (array_key_exists($name, $this->_keys)) {
                $result = $this->_lookup[$this->_keys[$name]];
            }
        } else {
            if (array_key_exists($name, $this->_lookup)) {
                $result = $this->_lookup[$name];
            }
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
     * Setting values in lookup tables is not allowed
     *
     * @throws Rx_Lookup_Exception
     * @return void
     */
    public function __set($name, $value)
    {
        throw new Rx_Lookup_Exception('Lookup tables can be used only for reading');
    }

    /**
     * Return an associative array of lookup table
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $keys = array_keys($this->_lookup);
        // Do not use direct retrieving of values from lookup table
        // because get() method may use additional value processing
        foreach ($keys as $key) {
            $array[$key] = $this->get($key);
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
        return (isset($this->_lookup[$name]));
    }

    /**
     * Support unset() overloading on PHP 5.1
     *
     * @param  string $name
     * @throws Rx_Lookup_Exception
     * @return void
     */
    public function __unset($name)
    {
        throw new Rx_Lookup_Exception('Lookup tables can be used only for reading');
    }

    /**
     * Defined by Countable interface
     *
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->_lookup);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_lookup);
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function next()
    {
        next($this->_lookup);
        $this->_index++;
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function rewind()
    {
        reset($this->_lookup);
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
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        // By default create translation prefix that matches lookup class name
        $prefix = preg_replace('/^(.*?)_Lookup_/i', 'lookup_', strtolower(get_class($this)));
        if (substr($prefix, -1) != '_') {
            $prefix .= '_';
        }
        parent::_initConfig();
        $this->_mergeConfig(array(
            'translate'       => false, // true to use Rx_Translate for translating lookup item values
            'translatePrefix' => $prefix, // Translation Id prefix to use for retrieving translated value or null to translate actual lookup value (used only if translate==true)
            'language'        => null, // Language to translate to (used only if translate==true)
            'noCase'          => false, // true to use case-insensitive lookup keys matching
        ));
    }

}
