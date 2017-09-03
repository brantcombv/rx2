<?php

/**
 * Client for JSON RPC server
 */
class Rx_Rpc_Client
{
    /**
     * Additional configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;
    /**
     * URL of RPC server to work with
     *
     * @var string $_url
     */
    protected $_url = null;
    /**
     * HTTP client to use for performing RPC requests
     *
     * @var Zend_Http_Client $_http
     */
    protected $_http = null;

    /**
     * Class constructor
     *
     * @param string $url               URL of RPC server to work with
     * @param array|Zend_Config $config OPTIONAL Additional configuration options
     * @return Rx_Rpc_Client
     */
    public function __construct($url, $config = null)
    {
        $this->_url = $url;
        $this->_config = new Rx_Configurable_Embedded($this, array(
            'http'           => null, // HTTP client object to use for performing RPC requests
            'json_handler'   => null, // Handler of additional JSON processing
            'request_class'  => 'Rx_Rpc_Request', // Class name to use for JSON-RPC request
            'response_class' => 'Rx_Rpc_Response', // Class name to use for JSON-RPC response
        ), array(
            'checkConfig'     => '_checkConfig',
            'onConfigChanged' => '_onConfigChanged',
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
            case 'http':
                if ($value instanceof Zend_Http_Client) {
                    $value = true;
                }
                break;
            case 'json_handler':
                if (($value === null) || (in_array('Rx_Json_Handler_Interface', class_implements($value)))) {
                    $valid = true;
                }
                break;
            case 'request_class':
                $class = 'Rx_Rpc_Request';
                if (!class_exists($value, true)) {
                    trigger_error(
                        'Unable to find RPC request class "' . $value . '", fallback to default class',
                        E_USER_WARNING
                    );
                    $value = $class;
                }
                if (($value != $class) && (!is_subclass_of($value, $class))) {
                    trigger_error(
                        'RPC request class "' . $value . '" must be instance of "' . $class . '", fallback to default class',
                        E_USER_WARNING
                    );
                    $value = $class;
                }
                break;
            case 'response_class':
                $class = 'Rx_Rpc_Response';
                if (!class_exists($value, true)) {
                    trigger_error(
                        'Unable to find RPC response class "' . $value . '", fallback to default class',
                        E_USER_WARNING
                    );
                    $value = $class;
                }
                if (($value != $class) && (!is_subclass_of($value, $class))) {
                    trigger_error(
                        'RPC response class "' . $value . '" must be instance of "' . $class . '", fallback to default class',
                        E_USER_WARNING
                    );
                    $value = $class;
                }
                break;
        }
        return ($valid);
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Configuration option value
     * @param string $operation Current operation Id
     * @return void
     */
    public function _onConfigChanged($name, $value, $operation)
    {
        switch ($name) {
            case 'http':
                $this->setHttp($value);
                break;
        }
    }

    /**
     * Get HTTP client to use for performing RPC requests
     *
     * @return Zend_Http_Client
     */
    public function getHttp()
    {
        if (!$this->_http) {
            $this->setHttp(new Zend_Http_Client());
        }
        return ($this->_http);
    }

    /**
     * Set HTTP client to use for performing RPC requests
     *
     * @param Zend_Http_Client $http HTTP client to use for performing RPC requests
     * @return void
     * @throws Rx_Rpc_Exception
     */
    public function setHttp($http)
    {
        if (!$http instanceof Zend_Http_Client) {
            throw new Rx_Rpc_Exception('HTTP client must be instance of Zend_Http_Client');
        }
        $this->_http = $http;
    }

    /**
     * Perform RPC request of given method and return response
     *
     * @param string $method  RPC method to call
     * @param mixed $args,... OPTIONAL List of arguments for RPC method
     * @return mixed
     * @throws Rx_Rpc_Exception
     */
    public function call($method, $args = null)
    {
        $args = func_get_args();
        array_shift($args);
        return ($this->callArgs($method, $args));
    }

    /**
     * Perform RPC request of given method and return response
     *
     * @param string $method RPC method to call
     * @param array $args    OPTIONAL List of arguments for RPC method
     * @return mixed
     * @throws Rx_Rpc_Exception
     */
    public function callArgs($method, $args = array())
    {
        if (!is_array($args)) {
            throw new Rx_Rpc_Exception('Arguments list for RPC method should be an array');
        }
        $response = $this->_request($method, $args, false);
        if ($response->isError()) {
            throw new Rx_Rpc_Exception('RPC method "' . $method . '" call failed: ' . $response->getError()->getMessage());
        }
        return ($response->getResult());
    }

    /**
     * Send notification request for RPC server
     *
     * @param string $method  Name of called RPC method
     * @param mixed $args,... OPTIONAL List of arguments for RPC method
     * @return void
     * @throws Rx_Rpc_Exception
     */
    public function notify($method, $args = null)
    {
        $args = func_get_args();
        array_shift($args);
        $this->_request($method, $args, true);
    }

    /**
     * Send notification request for RPC server
     *
     * @param string $method Name of called RPC method
     * @param array $args    OPTIONAL List of arguments for RPC method
     * @return void
     * @throws Rx_Rpc_Exception
     */
    public function notifyArgs($method, $args = array())
    {
        if (!is_array($args)) {
            throw new Rx_Rpc_Exception('Arguments list for RPC method should be an array');
        }
        $this->_request($method, $args, true);
    }

    /**
     * Perform RPC request of given method and return RPC response object
     *
     * @param string $method  RPC method to call
     * @param mixed $args,... OPTIONAL List of arguments for RPC method
     * @return Rx_Rpc_Response
     * @throws Rx_Rpc_Exception
     */
    public function request($method, $args = null)
    {
        $args = func_get_args();
        array_shift($args);
        $response = $this->_request($method, $args, false);
        return ($response);
    }

    /**
     * Perform RPC request of given method and return RPC response object
     *
     * @param string $method RPC method to call
     * @param array $args    OPTIONAL List of arguments for RPC method
     * @return Rx_Rpc_Response
     * @throws Rx_Rpc_Exception
     */
    public function requestArgs($method, $args = array())
    {
        if (!is_array($args)) {
            throw new Rx_Rpc_Exception('Arguments list for RPC method should be an array');
        }
        $response = $this->_request($method, $args, false);
        return ($response);
    }

    /**
     * Magic method to perform implicit calls to RPC server methods
     *
     * @param string $method Name of called RPC method
     * @param array $args    Arguments passed to RPC method
     * @return mixed
     * @throws Rx_Rpc_Exception
     */
    public function __call($method, $args)
    {
        array_unshift($args, $method);
        return (call_user_func_array(array($this, 'call'), $args));
    }

    /**
     * Perform RPC request to given method and return RPC response object
     *
     * @param string $method    RPC method to call
     * @param array $args       List of arguments for RPC method
     * @param boolean $isNotify OPTIONAL true for notification request, false to normal request
     * @return Rx_Rpc_Response|null
     * @throws Rx_Rpc_Exception
     */
    protected function _request($method, $args, $isNotify = false)
    {
        /** @var $notify Rx_Notify_Event */
        $notify = new Rx_Notify_Event('rx_rpc_client_request', array(
            'success'       => true,
            'reason'        => null,
            'exception'     => null,
            'notify'        => $isNotify,
            'rpc_request'   => null,
            'json'          => null,
            'http'          => null,
            'http_response' => null,
            'rpc_response'  => null,
        ), $this);
        try {
            $class = $this->getConfig('request_class');
            /** @var $request Rx_Rpc_Request */
            $request = new $class(array(
                'rpc_client'   => $this,
                'json_handler' => $this->getConfig('json_handler'),
            ));
            $notify->rpc_request = $request;
            $request->setMethod($method)->setVersion('2.0');
            if (sizeof($args)) {
                $request->addParams($args);
            }
            if (!$isNotify) {
                $request->setId(Rx_Uid::getRandomUid());
            }
            $json = $request->toJson();
            $notify->json = $json;
            $http = $this->getHttp();
            $notify->http = $http;
            $http->setUri($this->_url)
                ->setMethod(Zend_Http_Client::POST)
                ->setRawData($json);
            $httpResponse = $http->request();
            $notify->http_response = $httpResponse;
            $class = $this->getConfig('response_class');
            $response = new $class(array(
                'rpc_client'   => $this,
                'json_handler' => $this->getConfig('json_handler'),
            ));
            $notify->rpc_response = $response;
            if ($httpResponse->isSuccessful()) {
                if ($httpResponse->getStatus() != 204) {
                    $response->loadJson($httpResponse->getBody());
                    if ($response->isError()) {
                        $notify->success = false;
                        $notify->reason = 'rpc_response_failure';
                    }
                }
            } else {
                $notify->success = false;
                $notify->reason = 'http_failure';
            }
            Rx_Notify::notify($notify);
            return ($response);
        } catch (Exception $e) {
            $notify->success = false;
            $notify->reason = 'exception';
            $notify->exception = $e;
            Rx_Notify::notify($notify);
            throw new Rx_Rpc_Exception('Exception occurs during RPC request: ' . $e->getMessage());
        }
    }

}
