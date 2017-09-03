<?php

class Rx_Console_Output_Message
{
    /**
     * Message Id
     *
     * @var string $_id
     */
    protected $_id = null;
    /**
     * Message creation date
     *
     * @var int $_date
     */
    protected $_date = null;
    /**
     * Message owner
     *
     * @var object $_owner
     */
    protected $_owner = null;
    /**
     * Message parameters
     *
     * @var array $_params
     */
    protected $_params = array();

    /**
     * Class constructor
     *
     * @param string $id    Message identifier
     * @param array $params OPTIONAL Additional message parameters
     * @param object $owner OPTIONAL Message owner
     * @return Rx_Console_Output_Message
     */
    public function __construct($id, $params = array(), $owner = null)
    {
        $this->_id = $id;
        $this->_params = array();
        $this->_date = new Zend_Date();
        if (!is_object($owner)) {
            $owner = null;
        }
        $this->_owner = $owner;
        $this->setParams($params);
    }

    /**
     * Get message identifier
     *
     * @return string
     */
    public function getId()
    {
        return ($this->_id);
    }

    /**
     * Get message creation date
     *
     * @return Zend_Date
     */
    public function getDate()
    {
        return ($this->_date);
    }

    /**
     * Get parameter value by name
     *
     * @param string $name   Parameter name to get
     * @param mixed $default OPTIONAL Default value for parameter
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return ((array_key_exists($name, $this->_params)) ? $this->_params[$name] : $default);
    }

    /**
     * Get all available message parameters
     *
     * @return array
     */
    public function getParams()
    {
        return ($this->_params);
    }

    /**
     * Set message parameter
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     * @return void
     */
    public function setParam($name, $value)
    {
        $this->_params[$name] = $value;
    }

    /**
     * Set multiple message parameters
     *
     * @param array $params Parameters to set
     * @return boolean
     */
    public function setParams($params)
    {
        if ((is_object($params)) && (is_callable(array($params, 'toArray')))) {
            $params = $params->toArray();
        }
        if (!is_array($params)) {
            return (false);
        }
        foreach ($params as $k => $v) {
            $this->setParam($k, $v);
        }
        return (true);
    }

}

;
