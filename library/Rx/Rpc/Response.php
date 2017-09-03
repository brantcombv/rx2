<?php

/**
 * JSON-RPC server response
 */
class Rx_Rpc_Response extends Zend_Json_Server_Response
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
     * @param array|Zend_Config $config OPTIONAL Additional configuration options
     * @return Rx_Rpc_Response
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
     * Convert response information into JSON
     *
     * @return string
     */
    public function toJson()
    {
        $response = array();
        $isV2 = ($this->getVersion() == '2.0');
        $isError = $this->isError();
        $isNotify = ($this->getId() === null);
        if ($isV2) {
            // JSON-RPC 2.0 response
            if ($isNotify) // No response should be given for
            {
                return ('');
            } // notification requests in JSON-RPC 2.0
            $response['jsonrpc'] = $this->getVersion();
            if ($isError) {
                $response['error'] = $this->getError()->toArray();
            } else {
                $response['result'] = $this->getResult();
            }
            $response['id'] = $this->getId();
        } else {
            // JSON-RPC 1.0 response
            $response['result'] = ($isError) ? null : $this->getResult();
            $response['error'] = ($isError) ? $this->getError()->toArray() : null;
            $response['id'] = $this->getId();
        }
        // Render response to JSON
        $handler = $this->getConfig('json_handler');
        $jsonConfig = ($handler) ? array('handler' => $handler) : null;
        $json = Rx_Json_Encoder::encode($response, $jsonConfig);
        return ($json);
    }

    /**
     * Set response state based on given JSON-encoded response
     *
     * @param string $json JSON-encoded response
     * @return void
     * @throws Rx_Rpc_Exception
     */
    public function loadJson($json)
    {
        // Reset all current object properties
        $this->_version = null;
        $this->_id = null;
        $this->_result = null;
        $this->_error = null;
        $handler = $this->getConfig('json_handler');
        $jsonConfig = ($handler) ? array('handler' => $handler) : null;
        $response = Rx_Json_Decoder::decode($json, $jsonConfig);
        if (!is_array($response)) {
            throw new Rx_Rpc_Exception('Invalid JSON response object is given, is does\'t represents array');
        }
        if (array_key_exists('jsonrpc', $response)) {
            $this->setVersion($response['jsonrpc']);
        }
        if (array_key_exists('id', $response)) {
            $this->setId($response['id']);
        }
        if (array_key_exists('result', $response)) {
            $this->setResult($response['result']);
        }
        if ((array_key_exists('error', $response)) && (is_array($response['error']))) {
            $message = (array_key_exists('message', $response['error'])) ? $response['error']['message'] : null;
            $code = (array_key_exists(
                'code',
                $response['error']
            )) ? $response['error']['code'] : Rx_Rpc_Error::ERROR_PARSE;
            $data = (array_key_exists('data', $response['error'])) ? $response['error']['data'] : null;
            $error = new Rx_Rpc_Error($message, $code, $data, $this->getConfig());
            $this->setError($error);
        }
    }

    /**
     * Get HTTP response for this JSON-RPC response
     *
     * @return array
     */
    public function getResponse()
    {
        $response = array(
            'code'    => 200,
            'headers' => array(),
            'body'    => null,
        );
        $isV2 = ($this->getVersion() == '2.0');
        $isNotify = ($this->getId() === null);
        // No content should be sent for notification requests in JSON-RPC 2.0
        // so reply with corresponding HTTP status
        if (($isV2) && ($isNotify)) {
            $response['code'] = 204; // HTTP/1.1 No Content
            return ($response);
        }
        $response['body'] = $this->toJson();
        $contentType = 'application/json';
        // If we have SMD - check if there is some specific content type is defined
        $smd = $this->getServiceMap();
        if ($smd) {
            $ct = $smd->getContentType();
            if ($ct) {
                $contentType = $ct;
            }
        }
        $response['headers']['Content-Type'] = $contentType;
        $response['headers']['Content-Length'] = strlen($response['body']);
        return ($response);
    }

    /**
     * Send response
     *
     * @return void
     */
    public function sendResponse()
    {
        $response = $this->getResponse();
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $response['code']);
            if ($response['code'] == 204) // No Content
            {
                return;
            }
            foreach ($response['headers'] as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $response['body'];
    }

}
