<?php

/**
 * JSON-RPC server error
 */
class Rx_Rpc_Error extends Zend_Json_Server_Error
{
    /**
     * Additional configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;

    /**
     * Class constructor
     *
     * @param string $message           OPTIONAL Error message text
     * @param int $code                 OPTIONAL Error code
     * @param mixed $data               OPTIONAL Additional data related to error
     * @param array|Zend_Config $config OPTIONAL Additional configuration options
     * @return Rx_Rpc_Error
     */
    public function __construct($message = null, $code = -32000, $data = null, $config = null)
    {
        parent::__construct($message, $code, $data);
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
     * Cast error to JSON
     *
     * @return string
     */
    public function toJson()
    {
        $handler = $this->getConfig('json_handler');
        $jsonConfig = ($handler) ? array('handler' => $handler) : null;
        $json = Rx_Json_Encoder::encode($this->toArray(), $jsonConfig);
        return ($json);
    }

}
