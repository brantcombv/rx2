<?php

/**
 * Standalone JSON-RPC server
 */
class Rx_Rpc_Server extends Zend_Json_Server
{

    /**#@+
     * Constants for hook points
     */
    const HOOK_START_HANDLING = 'start_handling';
    const HOOK_PRE_DISPATCH = 'pre_dispatch';
    const HOOK_POST_DISPATCH = 'post_dispatch';
    const HOOK_END_HANDLING = 'end_handling';
    const HOOK_EXCEPTION = 'exception';
    const HOOK_INVALID_REQUEST = 'invalid_request';
    /**#@-*/

    /**
     * Additional configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;
    /**
     * Cache to use for storing server definition
     *
     * @var Zend_Cache_Core $_cache
     */
    protected $_cache = null;
    /**
     * Callback to "rpcSetServer" method within RPC handler class
     *
     * @var callback $_cbSetServer
     */
    protected $_cbSetServer = null;
    /**
     * Callback to hooks points handler method within RPC handler class
     *
     * @var callback $_cbHookPointsHandler
     */
    protected $_cbHookPointsHandler = null;
    /**
     * Request handling notification event
     *
     * @var Rx_Notify_Event $_notify
     */
    protected $_notify = null;
    /**
     * @var Zend_Server_Definition $_table
     */
    protected $_table = null;


    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Additional configuration options
     * @return Rx_Rpc_Server
     */
    public function __construct($config = null)
    {
        parent::__construct();
        $this->_config = new Rx_Configurable_Embedded($this, array(
            'json_handler'   => null, // Handler of additional JSON processing
            'acl'            => null, // ACL class to use for determining access to server methods
            'cache'          => null, // Cache to use for storing server definition
            'rpc_class'      => null, // Class to register for handling RPC requests
            'emit_response'  => false, // Alias of $_autoEmitResponse member variable
            'request_class'  => 'Rx_Rpc_Request', // Class name to use for JSON-RPC request
            'response_class' => 'Rx_Rpc_Response', // Class name to use for JSON-RPC response
            'smd_class'      => 'Rx_Rpc_Smd', // Class name to use for service mapping description
        ), array(
            'checkConfig'     => '_checkConfig',
            'onConfigChanged' => '_onConfigChanged',
        ), $config);
        // Since setClass() is not called automatically
        // upon config initialization - do it now
        $class = $this->getConfig('rpc_class');
        if ($class) {
            $this->setClass($class);
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
            case 'json_handler':
                if (($value === null) || (in_array('Rx_Json_Handler_Interface', class_implements($value)))) {
                    $valid = true;
                }
                break;
            case 'acl':
                if ($value === null) {
                    $valid = true;
                } elseif ((is_object($value)) && ($value instanceof Zend_Acl)) {
                    $valid = true;
                } elseif ((is_string($value)) && (class_exists($value, true))) {
                    $value = new $value();
                    if ($value instanceof Zend_Acl) {
                        $valid = true;
                    }
                }
                break;
            case 'cache':
                if ($value instanceof Zend_Cache_Core) {
                    $valid = true;
                }
                break;
            case 'rpc_class':
                if (is_object($value)) {
                    $valid = true;
                } elseif (is_string($value)) {
                    $valid = class_exists($value, true);
                }
                break;
            case 'emit_response':
                $value = (boolean)$value;
                $valid = true;
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
            case 'smd_class':
                $class = 'Rx_Rpc_Smd';
                if (!class_exists($value, true)) {
                    trigger_error(
                        'Unable to find RPC SMD class "' . $value . '", fallback to default class',
                        E_USER_WARNING
                    );
                    $value = $class;
                }
                if (($value != $class) && (!is_subclass_of($value, $class))) {
                    trigger_error(
                        'RPC SMD class "' . $value . '" must be instance of "' . $class . '", fallback to default class',
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
            case 'cache':
                $this->_cache = $value;
                break;
            case 'rpc_class':
                // Avoid problems with access to not-yet-available configuration
                // into class constructor
                if ($this->_config) {
                    $this->setClass($value);
                }
                break;
            case 'emit_response':
                $this->setAutoEmitResponse($value);
                break;
        }
    }

    /**
     * Attach a function or callback to the server
     *
     * @param  string|array $function Valid PHP callback
     * @param  string $namespace      Ignored
     * @return Rx_Rpc_Server
     * @throws Rx_Rpc_Exception
     */
    public function addFunction($function, $namespace = '')
    {
        if ((!is_string($function)) && ((!is_array($function)) || (2 > count($function)))) {
            throw new Rx_Rpc_Exception('Unable to attach function; invalid');
        }
        if (!is_callable($function)) {
            throw new Rx_Rpc_Exception('Unable to attach function; does not exist');
        }
        $argv = null;
        if (func_num_args() > 2) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }
        if (is_string($function)) {
            $method = Zend_Server_Reflection::reflectFunction($function, $argv, $namespace);
            $this->_addMethodServiceMap($this->_buildSignature($method));
        } else {
            /** @var $function array */
            $class = array_shift($function);
            $action = array_shift($function);
            $registered = $this->_setClass($class, $action);
            if (!$registered) {
                throw new Rx_Rpc_Exception('Unable to attach function; requested action "' . $action . '" is not available in class "' . get_class(
                        $class
                    ) . '"');
            }
        }
        return ($this);
    }

    /**
     * Register a class with the server
     *
     * @param string|object $class Class to register
     * @param string $namespace    Ignored
     * @param mixed $argv          Ignored
     * @return Rx_Rpc_Server
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        if ($this->_cache) {
            $cacheId = join(
                '_',
                array(
                    get_class($this),
                    (is_object($class)) ? get_class($class) : $class,
                )
            );
            if ($this->_cache->test($cacheId)) {
                // Load server definition from cache
                $definition = $this->_cache->load($cacheId);
                if ($definition instanceof Zend_Server_Definition) {
                    $this->loadFunctions($definition);
                    return ($this);
                }
            }
            // Create server definition and store it into cache
            $this->_setClass($class);
            $this->_cache->save($this->getFunctions(), $cacheId);
        } else {
            $this->_setClass($class);
        }

        return ($this);
    }

    /**
     * Register a class with the server
     *
     * @param string|object $class       Class to register
     * @param string|array $methodFilter OPTIONAL Additional filter for class methods
     * @return boolean                      true if at least one method was registered, false if no valid methods was found
     */
    protected function _setClass($class, $methodFilter = null)
    {
        if (($methodFilter !== null) && (!is_array($methodFilter))) {
            $methodFilter = array($methodFilter);
        }
        $reflection = Zend_Server_Reflection::reflectClass($class);
        $haveInterface = (in_array('Rx_Rpc_Handler_Interface', class_implements($class, true)));
        $interfaceMethods = array();
        if ($haveInterface) {
            if (!is_object($class)) {
                $class = new $class();
            }
            $r = new ReflectionClass('Rx_Rpc_Handler_Interface');
            $rm = $r->getMethods();
            foreach ($rm as $m) {
                $interfaceMethods[] = $m->getName();
            }
        }
        $found = false;
        /* @var $method Zend_Server_Reflection_Method */
        foreach ($reflection->getMethods() as $method) {
            $mName = $method->getName();
            $name = null;
            if ((is_array($methodFilter)) && (!in_array($mName, $methodFilter))) {
                continue;
            }
            if ($haveInterface) {
                if ($mName == 'rpcSetServer') {
                    $definition = $this->_buildSignature($method, $class);
                    $this->_table->removeMethod($mName);
                    $this->_table->addMethod($definition, '__rpc_set_server');
                    $this->_cbSetServer = $this->_getCallback($definition);
                    continue;
                } elseif ($mName == 'rpcHookPointsHandler') {
                    $definition = $this->_buildSignature($method, $class);
                    $this->_table->removeMethod($mName);
                    $this->_table->addMethod($definition, '__rpc_hooks_handler');
                    $this->_cbHookPointsHandler = $this->_getCallback($definition);
                    continue;
                } elseif (in_array($mName, $interfaceMethods)) {
                    continue;
                }
                $valid = $class->rpcIsValidMethod($mName);
                if (is_string($valid)) {
                    $name = $valid;
                } elseif (!$valid) {
                    continue;
                }
            }
            $definition = $this->_buildSignature($method, $class);
            if ($name !== null) {
                // Change method name in server definition
                $this->_table->removeMethod($mName);
                $this->_table->addMethod($definition, $name);
            }
            $this->_addMethodServiceMap($definition, $name);
            $found = true;
        }
        return ($found);
    }

    /**
     * Indicate fault response
     *
     * @param string $fault OPTIONAL Error message text
     * @param int $code     OPTIONAL Error code
     * @param mixed $data   OPTIONAL Additional data related to error
     * @return Rx_Rpc_Error
     */
    public function fault($fault = null, $code = null, $data = null)
    {
        if ($code === null) {
            $code = Rx_Rpc_Error::ERROR_INTERNAL;
        }
        $error = new Rx_Rpc_Error($fault, $code, $data, array(
            'rpc_server'   => $this,
            'json_handler' => $this->getConfig('json_handler'),
        ));
        $this->getResponse()->setError($error);
        return ($error);
    }

    /**
     * Handle request
     *
     * @param Rx_Rpc_Request $request OPTIONAL RPC request to handle
     * @return Rx_Rpc_Response|null
     * @throws Rx_Rpc_Exception
     */
    public function handle($request = false)
    {
        $this->_notify = new Rx_Notify_Event('rx_rpc_server_request', array(
            'success'   => true,
            'exception' => null,
        ), $this);
        try {
            if ($this->_cbSetServer) {
                call_user_func($this->_cbSetServer, $this);
            }
            if (!$this->_callHookPointsHandler(self::HOOK_START_HANDLING)) {
                return (null);
            }
            if ($request !== false) {
                $this->setRequest($request); // Use given RPC request object
            } elseif (!$this->haveRequest()) { // In a case if there is no request object is set directly
                $this->getRequest()->loadJson(null); // Load JSON-RPC request information from raw POST body
            }
            $this->_handle();
        } catch (Exception $e) {
            $code = Rx_Rpc_Error::ERROR_INTERNAL;
            if ((get_class($e) == 'Rx_Rpc_Exception') && ($e->getCode() != 0)) {
                $code = $e->getCode();
            }
            $this->fault($e->getMessage(), $code);
            $this->_notify->success = false;
            $this->_notify->exception = $e;
            $this->_callHookPointsHandler(self::HOOK_EXCEPTION);
        }
        Rx_Notify::notify($this->_notify);
        if (!$this->_callHookPointsHandler(self::HOOK_END_HANDLING)) {
            return (null);
        }
        $response = $this->getResponse();
        if ($this->autoEmitResponse()) {
            $response->sendResponse();
        } else {
            return ($response);
        }
    }

    /**
     * Get callback to server method by method definition
     *
     * @param Zend_Server_Method_Definition $definition Server method definition to get callback for
     * @return callback|null
     */
    protected function _getCallback($definition)
    {
        $callback = null;
        $cb = $definition->getCallback();
        switch ($cb->getType()) {
            case 'static':
                $callback = array($cb->getClass(), $cb->getMethod());
                break;
            case 'instance':
                $callback = array($definition->getObject(), $cb->getMethod());
                break;
            case 'function':
                $callback = $cb->getFunction();
                break;
        }
        if (!is_callable($callback)) {
            $callback = null;
        }
        return ($callback);
    }

    /**
     * Call hook points handler
     *
     * @param string $hookId Hook point Id (@see Rx_Rpc_Server::HOOK_xxx constants)
     * @return boolean          true to keep request processing, false to break request handling
     */
    protected function _callHookPointsHandler($hookId)
    {
        if (!$this->_cbHookPointsHandler) {
            return (true);
        }
        $result = call_user_func_array($this->_cbHookPointsHandler, array($hookId, $this, $this->_notify));
        if ($result === false) {
            return (false);
        }
        return (true);
    }

    /**
     * Load function definitions
     *
     * @param array|Zend_Server_Definition $definition
     * @return void
     * @throws Rx_Rpc_Exception
     */
    public function loadFunctions($definition)
    {
        if ((!is_array($definition)) && (!$definition instanceof Zend_Server_Definition)) {
            throw new Rx_Rpc_Exception('Functions definition must be instance of Zend_Server_Definition');
        }
        /* @var $method Zend_Server_Method_Definition */
        foreach ($definition as $key => $method) {
            switch ($key) {
                case '__rpc_set_server':
                    $this->_cbSetServer = $this->_getCallback($method);
                    break;
                case '__rpc_hooks_handler':
                    $this->_cbHookPointsHandler = $this->_getCallback($method);
                    break;
                default:
                    $this->_table->addMethod($method, $key);
                    $this->_addMethodServiceMap($method);
                    break;
            }
        }
    }

    /**
     * Check if RPC request object is already defined on server
     *
     * @return boolean
     */
    public function haveRequest()
    {
        return ($this->_request instanceof Rx_Rpc_Request);
    }

    /**
     * Set RPC request object
     *
     * @param  Rx_Rpc_Request $request RPC request object
     * @return Rx_Rpc_Server
     * @throws Rx_Rpc_Exception
     */
    public function setRequest(Zend_Json_Server_Request $request)
    {
        if (!$request instanceof Rx_Rpc_Request) {
            throw new Rx_Rpc_Exception('RPC request object must be instance of Rx_Rpc_Request');
        }
        // Link given request object to server
        $request->setConfig(array(
            'rpc_server'   => $this,
            'json_handler' => $this->getConfig('json_handler'),
        ));
        $this->_request = $request;
        if ($this->_response) {
            // Link response parameters with request parameters
            $this->_response->setId($this->_request->getId());
            $this->_response->setVersion($this->_request->getVersion());
        }
        return ($this);
    }

    /**
     * Get RPC request object
     *
     * @return Rx_Rpc_Request
     */
    public function getRequest()
    {
        if (!$this->_request) {
            $class = $this->getConfig('request_class');
            $this->setRequest(new $class());
        }
        return ($this->_request);
    }

    /**
     * Set RPC response object
     *
     * @param  Rx_Rpc_Response $response RPC response object
     * @return Rx_Rpc_Server
     * @throws Rx_Rpc_Exception
     */
    public function setResponse(Zend_Json_Server_Response $response)
    {
        if (!$response instanceof Rx_Rpc_Response) {
            throw new Rx_Rpc_Exception('RPC response object must be instance of Rx_Rpc_Response');
        }
        // Link given response object to server
        $response->setConfig(array(
            'rpc_server'   => $this,
            'json_handler' => $this->getConfig('json_handler'),
        ));
        $this->_response = $response;
        $this->_response->setServiceMap($this->getServiceMap());
        if ($this->_request) {
            // Link response parameters with request parameters
            $this->_response->setId($this->_request->getId());
            $this->_response->setVersion($this->_request->getVersion());
        }
        return ($this);
    }

    /**
     * Get RPC response object
     *
     * @return Rx_Rpc_Response
     */
    public function getResponse()
    {
        if (!$this->_response) {
            $class = $this->getConfig('response_class');
            $this->setResponse(new $class());
        }
        return ($this->_response);
    }

    /**
     * Retrieve SMD object
     *
     * @return Rx_Rpc_Smd
     */
    public function getServiceMap()
    {
        if (null === $this->_serviceMap) {
            $class = $this->getConfig('smd_class');
            $this->_serviceMap = new $class();
        }
        return ($this->_serviceMap);
    }

    /**
     * Check if access to given RPC method is allowed
     *
     * @return boolean
     */
    public function isAllowed($method)
    {
        /* @var $acl Zend_Acl */
        $acl = $this->getConfig('acl');
        if ($acl instanceof Zend_Acl) {
            return ($acl->isAllowed(Rx_User::getRole(), 'rpc:' . $method));
        } else {
            // No ACL is applied - allow access to all methods
            return (true);
        }
    }

    /**
     * Add service method to service map
     *
     * @param Zend_Server_Method_Definition $method Service method to add
     * @param string $name                          OPTIONAL Method name for service map
     * @return void
     */
    protected function _addMethodServiceMap(Zend_Server_Method_Definition $method, $name = null)
    {
        if ($name === null) {
            $name = $method->getName();
        }
        $return = $this->_getReturnType($method);
        if (is_array($return)) {
            $return = array_values(array_unique($return)); // Workaround for ZF-11180
            if (sizeof($return) == 1) {
                $return = array_shift($return);
            }
        }
        $serviceInfo = array(
            'name'   => $name,
            'return' => $return,
        );
        $params = $this->_getParams($method);
        $serviceInfo['params'] = $params;
        $serviceMap = $this->getServiceMap();
        if (false !== $serviceMap->getService($serviceInfo['name'])) {
            $serviceMap->removeService($serviceInfo['name']);
        }
        $serviceMap->addService($serviceInfo);
    }

    /**
     * Retrieve list of allowed SMD methods for proxying
     *
     * @return array
     */
    protected function _getSmdMethods()
    {
        if (null === $this->_smdMethods) {
            $this->_smdMethods = array();
            $methods = get_class_methods($this->getConfig('smd_class'));
            foreach ($methods as $key => $method) {
                if (!preg_match('/^(set|get)/', $method)) {
                    continue;
                }
                if (strstr($method, 'Service')) {
                    continue;
                }
                $this->_smdMethods[] = $method;
            }
        }
        return $this->_smdMethods;
    }

    /**
     * Internal method for handling request
     *
     * @return void
     */
    protected function _handle()
    {
        // If some error occurs prior to request handling
        // we should interrupt request processing
        if ($this->getResponse()->isError()) {
            $this->_notify->success = false;
            $this->_callHookPointsHandler(self::HOOK_INVALID_REQUEST);
            return;
        }
        // Validate RPC request method
        $request = $this->getRequest();
        $method = null;
        if (!$request->isMethodError()) {
            $method = $request->getMethod();
            if ($method !== null) {
                if (!$this->_table->hasMethod($method)) {
                    $this->fault('Method not found', Rx_Rpc_Error::ERROR_INVALID_METHOD);
                } elseif (!$this->isAllowed($method)) {
                    $this->fault('Access denied', Rx_Rpc_Error::ERROR_INVALID_METHOD);
                }
            } else {
                $this->fault('No requested method name is defined', Rx_Rpc_Error::ERROR_INVALID_METHOD);
            }
        } else {
            $this->fault('Invalid name of requested method', Rx_Rpc_Error::ERROR_INVALID_METHOD);
        }
        // If RPC method validation is failed - we should stop request processing
        if ($this->getResponse()->isError()) {
            $this->_notify->success = false;
            $this->_callHookPointsHandler(self::HOOK_INVALID_REQUEST);
            return;
        }
        // Prepare list of parameters for dispatching RPC request
        $params = $request->getParams();
        /** @var $invocable Zend_Server_Method_Definition */
        $invocable = $this->_table->getMethod($method);
        $serviceMap = $this->getServiceMap();
        $service = $serviceMap->getService($method);
        $serviceParams = $service->getParams();

        if (count($params) < count($serviceParams)) {
            $params = $this->_getDefaultParams($params, $serviceParams);
        }

        //Make sure named parameters are passed in correct order
        if (is_string(key($params))) {
            $callback = $invocable->getCallback();
            if ($callback->getType() == 'function') {
                $reflection = new ReflectionFunction($callback->getFunction());
            } else {
                $reflection = new ReflectionMethod($callback->getClass(), $callback->getMethod());
            }
            $refParams = $reflection->getParameters();
            $orderedParams = array();
            foreach ($refParams as $refParam) {
                $pName = $refParam->getName();
                if (array_key_exists($pName, $params)) {
                    $orderedParams[$pName] = $params[$pName];
                } elseif ($refParam->isOptional()) {
                    $orderedParams[$pName] = null;
                } else {
                    $this->fault(
                        'Required parameter is missing: ' . $refParam->getName(),
                        Rx_Rpc_Error::ERROR_INVALID_PARAMS
                    );
                    $this->_notify->success = false;
                    $this->_callHookPointsHandler(self::HOOK_INVALID_REQUEST);
                    return;
                }
            }
            $params = $orderedParams;
        }
        // Dispatch RPC request and set results to response
        if (!$this->_callHookPointsHandler(self::HOOK_PRE_DISPATCH)) {
            return;
        }
        $result = $this->_dispatch($invocable, $params);
        $this->getResponse()->setResult($result);
        $this->_callHookPointsHandler(self::HOOK_POST_DISPATCH);
    }

}
