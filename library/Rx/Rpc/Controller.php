<?php

/**
 * JSON-RPC server implemented as MVC controller
 */
class Rx_Rpc_Controller extends Zend_Controller_Action implements Rx_ErrorsHandler_Listener_Interface
{
    /**
     * Handler of additional JSON processing for RPC server
     *
     * @var Rx_Json_Handler_Interface $_jsonHandler
     */
    protected $_jsonHandler = null;
    /**
     * Cache to use for storing server definition
     *
     * @var Zend_Cache_Core $_cache
     */
    protected $_cache = null;
    /**
     * Class instance or name of class that will handle RPC requests
     *
     * @var string|object $_rpcClass
     */
    protected $_rpcClass = null;
    /**
     * RPC server instance
     *
     * @var Rx_Rpc_Server $_server
     */
    protected $_server = null;
    /**
     * true to show service SMD, false to disable it
     *
     * @var boolean $_showSmd
     */
    protected $_showSmd = false;
    /**
     * true to render response from RPC server, false to skip it
     *
     * @var boolean $_renderRpc
     */
    protected $_renderRpc = true;

    /**
     * Initialize object
     *
     * @return void
     */
    public function init()
    {
        // Register ourselves as errors listener
        Rx_ErrorsHandler::addListener($this);
        // Force errors handler to not produce any output
        Rx_ErrorsHandler::setConfig(array(
            'displayErrors' => false,
            'errorPage'     => null,
            'errorUrl'      => null,
        ));
        // Disable layout and view rendering
        if ($this->_helper->hasHelper('viewRenderer')) {
            /** @var $vr Zend_Controller_Action_Helper_ViewRenderer */
            $vr = $this->_helper->viewRenderer;
            $vr->setNoRender();
        }
        if ($this->_helper->hasHelper('layout')) {
            /** @var $layout Zend_Layout_Controller_Action_Helper_Layout */
            $layout = $this->_helper->layout;
            $layout->getLayoutInstance()->disableLayout();
        }
        $this->_renderRpc = true;
    }

    /**
     * Default handler of RPC methods calls
     *
     * @return void
     */
    public function indexAction()
    {
        /** @var $request Zend_Controller_Request_Http */
        $request = $this->getRequest();
        if (($request->isPost()) || ($this->getRpcServer()->haveRequest())) {
            $this->getRpcServer()->handle();
        } // Handle RPC request
        elseif ($this->_showSmd) {
            // Output service SMD
            $this->getRpcServer()
                ->getServiceMap()
                ->setTarget(
                    Rx_Url::url(
                        join(
                            '.',
                            array(
                                $request->getModuleName(),
                                $request->getControllerName(),
                                $request->getActionName(),
                            )
                        )
                    )
                )
                ->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);
            $this->getResponse()->setHeader('Content-Type', 'application/json', true);
            $this->getResponse()->setBody($this->getRpcServer()->getServiceMap()->toJson());
            $this->_renderRpc = false;
        } else // Display error about undefined RPC method
        {
            $this->getRpcServer()->fault('No method is defined', Rx_Rpc_Error::ERROR_INVALID_REQUEST);
        }
    }

    /**
     * Proxy for undefined methods.
     *
     * @param string $method Method name to call
     * @param array $args    Additional arguments for method invocation
     * @return void
     */
    public function __call($method, $args)
    {
        // Implement support for calling RPC methods via GET requests:
        // http://example.com/rpc_controller/rpc_method/?rpcid=<RPC request Id>&param=value
        if (substr($method, -6) == 'Action') {
            $params = $this->getRequest()->getParams();
            if (array_key_exists('rpcid', $params)) {
                $request = $this->getRpcServer()->getRequest();
                $request->setId($params['rpcid']);
                $request->setMethod($this->getRequest()->getActionName()); // To avoid losing "_" chars from method name
                $request->setVersion('2.0');
                unset($params['rpcid']);
                $request->setParams($params);
                $this->getRpcServer()->handle($request);
                return;
            }
        }
        parent::__call($method, $args);
    }

    /**
     * Post-dispatch routines
     *
     * @return void
     */
    public function postDispatch()
    {
        if (!$this->_renderRpc) {
            return;
        }
        $httpResponse = $this->getResponse();
        $rpcResponse = $this->getRpcServer()->getResponse()->getResponse();
        $httpResponse->setHttpResponseCode($rpcResponse['code'])
            ->setBody($rpcResponse['body']);
        foreach ($rpcResponse['headers'] as $name => $value) {
            $httpResponse->setHeader($name, $value, true);
        }
    }

    /**
     * Get instance of RPC server used by controller
     *
     * @return Rx_Rpc_Server
     */
    public function getRpcServer()
    {
        if (!$this->_server) {
            // Create RPC server instance
            $this->_server = new Rx_Rpc_Server(array(
                'json_handler'  => $this->_jsonHandler,
                'cache'         => $this->_cache,
                'rpc_class'     => $this->_rpcClass,
                'emit_response' => false,
            ));
        }
        return ($this->_server);
    }

    /**
     * Handle application error
     *
     * @param array $error      Error details:
     *                          date        - Date when error occurs (timestamp)
     *                          level       - Error level (one of E_xxx constants)
     *                          message     - Error message text
     *                          filename    - Name of file, error occurs in
     *                          line        - Line number where error occurs
     *                          backtrace   - Backtrace for error
     * @return void
     */
    public function handleError($error)
    {
        // There is nothing to do about errors since we normally
        // should not disclose information about internal errors to remote clients
    }

    /**
     * Handle application exception
     *
     * @param array $exception  Exception details:
     *                          date        - Date when exception occurs (timestamp)
     *                          type        - Exception type
     *                          level       - Error level ("exception" string)
     *                          message     - Exception message text
     *                          code        - Exception code
     *                          filename    - Name of file, exception occurs in
     *                          line        - Line number where exception occurs
     *                          backtrace   - Backtrace for exception
     * @return void
     */
    public function handleException($exception)
    {
        $data = null;
        if (APPLICATION_ENV == 'development') {
            $data = array(
                'exception' => Rx_ErrorsHandler::formatError($exception),
                'backtrace' => Rx_ErrorsHandler::formatBacktrace($exception['backtrace'], true),
            );
        }
        $this->getRpcServer()->fault(
            'Exception occurs while processing RPC request',
            Rx_Rpc_Error::ERROR_INTERNAL,
            $data
        );
        $this->getRpcServer()->getResponse()->sendResponse();
    }

}
