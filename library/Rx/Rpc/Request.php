<?php

/**
 * JSON-RPC server request
 */
class Rx_Rpc_Request extends Zend_Json_Server_Request
{
    /**
     * Additional configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;
    /**
     * List of allowed members of JSON-RPC request
     *
     * @var array $_allowedMembers
     */
    protected $_allowedMembers = array(
        'jsonrpc' => 'version',
        'method'  => null,
        'params'  => null,
        'id'      => null,
    );
    /**
     * Raw JSON pulled from POST body
     *
     * @var string $_rawJson
     */
    protected $_rawJson;

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Additional configuration options
     * @return Rx_Rpc_Request
     */
    public function __construct($config = null)
    {
        $this->_config = new Rx_Configurable_Embedded($this, array(
            'rpc_server'   => null, // Instance of JSON-RPC server that owns this object
            'rpc_client'   => null, // Instance of JSON-RPC client that owns this object
            'json_handler' => null, // Handler of additional JSON processing
        ), array(
            'checkConfig' => '_checkConfig',
        ), $config);
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
        return ($this->_config->getConfig($config));
    }

    /**
     * Set configuration options for object
     *
     * @param array|string|Zend_Config $config      Configuration options to set
     * @param mixed $value                          If first parameter is passed as string then it will be treated as
     *                                              configuration option name and $value as its value
     * @return void
     */
    public function setConfig($config, $value = null)
    {
        $this->_config->setConfig($config, $value);
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    public function _checkConfig($name, &$value, $operation)
    {
        $valid = false;
        switch ($name) {
            case 'rpc_server':
                if ($value instanceof Rx_Rpc_Server) {
                    $valid = true;
                }
                break;
            case 'rpc_client':
                if ($value instanceof Rx_Rpc_Client) {
                    $valid = true;
                }
                break;
            case 'json_handler':
                if (($value === null) || (in_array('Rx_Json_Handler_Interface', class_implements($value)))) {
                    $valid = true;
                }
                break;
        }
        return ($valid);
    }

    /**
     * Set request state
     *
     * @param array $options
     * @throws Rx_Rpc_Exception
     * @return Rx_Rpc_Request
     */
    public function setOptions(array $options)
    {
        try {
            foreach ($options as $key => $value) {
                if (!array_key_exists($key, $this->_allowedMembers)) {
                    throw new Rx_Rpc_Exception('Not allowed JSON-RPC request member: ' . $key, Rx_Rpc_Error::ERROR_INVALID_PARAMS);
                }
                $oKey = $key;
                if ($this->_allowedMembers[$key] !== null) {
                    $key = $this->_allowedMembers[$key];
                }
                $method = 'set' . ucfirst($key);
                if (!method_exists($this, $method)) {
                    throw new Rx_Rpc_Exception('Unavailable setter method for JSON-RPC request member: ' . $oKey, Rx_Rpc_Error::ERROR_INTERNAL);
                }
                $this->$method($value);
            }
        } catch (Exception $e) {
            /* @var $server Rx_Rpc_Server */
            $server = $this->getConfig('rpc_server');
            if ($server) {
                $code = $e->getCode();
                if ($code == 0) {
                    $code = Rx_Rpc_Error::ERROR_INTERNAL;
                }
                $server->fault('Failed to parse JSON request: ' . $e->getMessage(), $code);
            } else {
                throw new Rx_Rpc_Exception('Failed to parse JSON request: ' . $e->getMessage());
            }
        }
        return ($this);
    }

    /**
     * Set request state based on JSON
     *
     * @param string|null $json     JSON-encoded request or
     *                              null to get JSON request from raw POST body
     * @return void
     * @throws Rx_Rpc_Exception
     */
    public function loadJson($json)
    {
        if ($json === null) {
            $json = file_get_contents('php://input');
        }
        $this->_rawJson = $json;
        $handler = $this->getConfig('json_handler');
        $jsonConfig = ($handler) ? array('handler' => $handler) : null;
        try {
            $options = Rx_Json_Decoder::decode($json, $jsonConfig);
            if (is_array($options)) {
                $this->setOptions($options);
            } else {
                throw new Rx_Rpc_Exception('JSON request should be an object');
            }
        } catch (Exception $e) {
            /* @var $server Rx_Rpc_Server */
            $server = $this->getConfig('rpc_server');
            if ($server) {
                $code = $e->getCode();
                if ($code == 0) {
                    $code = Rx_Rpc_Error::ERROR_PARSE;
                }
                $server->fault('Failed to parse JSON request: ' . $e->getMessage(), $code);
            } else {
                throw new Rx_Rpc_Exception('Failed to parse JSON request: ' . $e->getMessage());
            }
        }
    }

    /**
     * Cast request to JSON
     *
     * @return string
     */
    public function toJson()
    {
        $jsonArray = array(
            'method' => $this->getMethod()
        );
        if (null !== ($id = $this->getId())) {
            $jsonArray['id'] = $id;
        }
        $params = $this->getParams();
        if (!empty($params)) {
            $jsonArray['params'] = $params;
        }
        if ('2.0' == $this->getVersion()) {
            $jsonArray['jsonrpc'] = '2.0';
        }
        $handler = $this->getConfig('json_handler');
        $jsonConfig = ($handler) ? array('handler' => $handler) : null;
        $json = Rx_Json_Encoder::encode($jsonArray, $jsonConfig);
        return ($json);
    }

    /**
     * Get JSON from raw POST body
     *
     * @return string
     */
    public function getRawJson()
    {
        return $this->_rawJson;
    }

}
