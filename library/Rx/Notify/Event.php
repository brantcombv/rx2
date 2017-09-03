<?php

class Rx_Notify_Event implements Countable, Iterator
{
    /**
     * Variables for implementing Countable and Iterator interfaces
     */
    protected $_index = 0;
    protected $_count = 0;
    /**
     * Instance of object that sends event
     *
     * @var object $_sender
     */
    protected $_sender = null;
    /**
     * Unique identifier of each particular event object
     *
     * @var string $_uid
     */
    protected $_uid = null;
    /**
     * Event type identifier
     *
     * @var string $_type
     */
    protected $_type = null;
    /**
     * Additional event data
     *
     * @var array $_data
     */
    protected $_data = array();

    /**
     * Class constructor
     *
     * @param string $type   Event type identifier
     * @param array $data    OPTIONAL Additional data for event
     * @param object $sender OPTIONAL Instance of object that sends event
     */
    public function __construct($type, $data = null, $sender = null)
    {
        $this->_uid = Rx_Uid::getRandomUid(false, true);
        $this->_type = $type;
        $this->_sender = $sender;
        if ((is_object($data)) && (is_callable(array($data, 'toArray')))) {
            $data = $data->toArray();
        } elseif (!is_array($data)) {
            $data = array();
        }
        $this->set($data);
    }

    /**
     * Get event type identifier
     *
     * @return string
     */
    public function getType()
    {
        return ($this->_type);
    }

    /**
     * Get event unique identifier
     *
     * @return string
     */
    public function getUid()
    {
        return ($this->_uid);
    }

    /**
     * Get instance of object that sends this notification event
     *
     * @return object|null
     */
    public function getSender()
    {
        return ($this->_sender);
    }

    /**
     * Retrieve value of event data element with given name and return $default if there is no such element is available.
     *
     * @param string $name   Event data element name to get value of
     * @param mixed $default Default value to return in a case if element is not available
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $result = $default;
        if (array_key_exists($name, $this->_data)) {
            $result = $this->_data[$name];
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
     * Set value of event data element with given name
     *
     * @param string|array $name Either event data element name to set value of or array of event data elements to set
     * @param mixed $value       OPTIONAL New value for this element (only if $name is a string)
     * @return void
     */
    public function set($name, $value = null)
    {
        if ((is_object($name)) && (is_callable(array($name, 'toArray')))) {
            $name = $name->toArray();
        }
        if (!is_array($name)) {
            $name = array($name => $value);
        }
        foreach ($name as $k => $v) {
            $this->_data[$k] = $v;
        }
        $this->_index = 0;
        $this->_count = sizeof($this->_data);
    }

    /**
     * Magic function for setting event data elements values
     *
     * @param string $name Event data element name to set value of
     * @param mixed $value New value for this element
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Return event data as associative array
     *
     * @return array
     */
    public function toArray()
    {
        return ($this->_data);
    }

    /**
     * Support isset() overloading on PHP 5.1
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return (isset($this->_data[$name]));
    }

    /**
     * Support unset() overloading on PHP 5.1
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->_data[$name])) {
            unset($this->_data[$name]);
            $this->_index = 0;
            $this->_count = sizeof($this->_data);
        }
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
        return (current($this->_data));
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return (key($this->_data));
    }

    /**
     * Defined by Iterator interface
     *
     * @return void
     */
    public function next()
    {
        next($this->_data);
        $this->_index++;
    }

    /**
     * Defined by Iterator interface
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->_data);
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

}
