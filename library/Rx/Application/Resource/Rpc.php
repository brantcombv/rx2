<?php

class Rx_Application_Resource_Rpc extends Rx_Application_Resource_Abstract implements Rx_ErrorsHandler_Listener_Interface
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('errors');
    /**
     * Options for the resource
     *
     * @var array $_options
     */
    protected $_options = array(
        'class'    => array(
            'server'   => 'Rx_Rpc_Server', // Name of class to use as RPC server (Rx_Rpc_Server)
            'rpc'      => null, // Name of class that will handle RPC requests
            'request'  => 'Rx_Rpc_Request', // Name of class to use for JSON-RPC request (Rx_Rpc_Request)
            'response' => 'Rx_Rpc_Response', // Name of class to use for JSON-RPC response (Rx_Rpc_Response)
            'smd'      => 'Rx_Rpc_Smd', // Name of class to use for service mapping description (Rx_Rpc_Smd)
            'json'     => null, // Name of class that need to be used as JSON handler (Rx_Json_Handler_Interface)
        ),
        'smd'      => array(
            'show'   => false,
            // true to display Service Mapping Description on GET request, false to not show SMD at all
            'target' => null,
            // Path part of URL that points to RPC server, null to take it from "REQUEST_URI"
            'v2'     => true,
            // true to identify RPC server as JSON-RPC v2 server, false to identify as v1 server
        ),
        'useAcl'   => null, // Name of application resource that provides ACL that should be used by RPC server
        'useCache' => null, // Name of application resource that provides cache that should be used by RPC server
    );
    /**
     * RPC server instance
     *
     * @var Rx_Rpc_Server $_rpc
     */
    protected $_rpc = null;

    /**
     * Perform resource initialization
     *
     * @return Rx_Rpc_Server
     */
    protected function _init()
    {
        $options = $this->getOptions();
        // Register ourselves as errors listener
        Rx_ErrorsHandler::addListener($this);
        // Force errors handler to not produce any output
        Rx_ErrorsHandler::setConfig(array(
            'displayErrors' => false,
            'errorPage'     => null,
            'errorUrl'      => null,
        ));
        return ($this->getRpcServer());
    }

    /**
     * Get instance of RPC server used by controller
     *
     * @return Rx_Rpc_Server
     * @throws Zend_Application_Resource_Exception
     */
    public function getRpcServer()
    {
        if (!$this->_rpc) {
            $options = $this->getOptions();
            $rpcServer = $options['class']['server'];
            if (!class_exists($rpcServer, true)) {
                throw new Zend_Application_Resource_Exception('RPC server class is not found: ' . $rpcServer . ', please check "class.server" option value of RPC application resource');
            }
            // Construct configuration options passed to constructor only from defined options
            // to avoid resetting RPC server components possibly defined directly in class
            $config = array();
            $classMap = array(
                'rpc'      => 'rpc_class',
                'request'  => 'request_class',
                'response' => 'response_class',
                'smd'      => 'smd_class',
                'json'     => 'json_handler',
            );
            foreach ($classMap as $src => $target) {
                if (!array_key_exists($src, $options['class'])) {
                    continue;
                }
                $class = $options['class'][$src];
                if (($class) && (!class_exists($class, true))) {
                    throw new Zend_Application_Resource_Exception('RPC-related class "' . $class . '" defined in "class.' . $src . '" option RPC application resource is not found');
                }
                $config[$target] = $class;
            }
            if ($options['useAcl']) {
                $resource = $options['useAcl'];
                $acl = $this->getBootstrap()->bootstrap($resource)->getResource($resource);
                if ($acl instanceof Zend_Acl) {
                    $config['acl'] = $acl;
                } else {
                    throw new Zend_Application_Resource_Exception('Invalid ACL application resource "' . $resource . '" is defined for RPC server');
                }
            }
            if ($options['useCache']) {
                $resource = $options['useCache'];
                $cache = $this->getBootstrap()->bootstrap($resource)->getResource($resource);
                if ($cache instanceof Zend_Cache_Core) {
                    $config['cache'] = $cache;
                } else {
                    throw new Zend_Application_Resource_Exception('Invalid cache application resource "' . $resource . '" is defined for RPC server');
                }
            }
            $config['emit_response'] = true;
            // Create RPC server instance
            $rpcServer = new $rpcServer($config);
            if (!$rpcServer instanceof Rx_Rpc_Server) {
                throw new Zend_Application_Resource_Exception('RPC server class must be instance of Rx_Rpc_Server');
            }
            $this->_rpc = $rpcServer;
        }
        return ($this->_rpc);
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
