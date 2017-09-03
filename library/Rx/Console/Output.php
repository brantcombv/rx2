<?php

class Rx_Console_Output implements Rx_ErrorsHandler_Listener_Interface
{
    /**
     * Loader for console process output handlers
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_loader = null;
    /**
     * List of available console process output handlers
     *
     * @var array $_handlers
     */
    protected $_handlers = array();
    /**
     * true if console process output handlers are already initialized, false if not
     *
     * @var boolean $_initialized
     */
    protected $_initialized = false;

    /**
     * Class constructor
     *
     * @return Rx_Console_Output
     */
    public function __construct()
    {
        $this->_loader = Rx_Loader::getPluginLoader('Rx_Console_Output_Handler');
        $this->_handlers = array();
        $this->_initialized = false;
        // Subscribe itself as errors and exceptions handler
        Rx_ErrorsHandler::addListener($this);
    }

    /**
     * Handler of calls to console process output handler methods
     * implicitly defined by log priorities
     *
     * @param string $method    Priority name
     * @param string $arguments Message to write
     * @return void
     * @throws Rx_Console_Exception
     */
    public function __call($method, $arguments)
    {
        if (!$this->havePriority($method)) {
            throw new Rx_Console_Exception('Call to unavailable method: ' . $method);
        }
        $handlers = $this->getHandlers();
        /* @var $handler Rx_Console_Output_Handler_Abstract */
        foreach ($handlers as $id => $handler) {
            if (!$this->havePriority($method, $id)) {
                continue;
            }
            call_user_func_array(array($handler, $method), $arguments);
        }
    }

    /**
     * Write message into console output without line feed
     *
     * @param string $message Message to write
     * @return void
     */
    public function write($message)
    {
        $handlers = $this->getHandlers();
        /* @var $handler Rx_Console_Output_Handler_Abstract */
        foreach ($handlers as $id => $handler) {
            $handler->write($message);
        }
    }

    /**
     * Write message into console output with line feed
     *
     * @param string $message OPTIONAL Message to write
     * @return void
     */
    public function writeln($message = '')
    {
        $handlers = $this->getHandlers();
        /* @var $handler Rx_Console_Output_Handler_Abstract */
        foreach ($handlers as $id => $handler) {
            $handler->writeln($message);
        }
    }

    /**
     * Write message into console process output
     *
     * @param Rx_Console_Output_Message|string $message Either message object or message Id
     * @param array $params                             OPTIONAL Additional message parameters (used only if message Id is passed as $message)
     * @param object $owner                             OPTIONAL Message owner (used only if message Id is passed as $message)
     * @return void
     * @throws Rx_Console_Exception
     */
    public function message($message, $params = null, $owner = null)
    {
        if (!is_object($message)) {
            if (!is_array($params)) {
                $params = array();
            }
            $message = new Rx_Console_Output_Message($message, $params, $owner);
        }
        if (!$message instanceof Rx_Console_Output_Message) {
            throw new Rx_Console_Exception('Console process output message must be instance of Rx_Console_Output_Message');
        }
        $handlers = $this->getHandlers();
        /* @var $handler Rx_Console_Output_Handler_Abstract */
        foreach ($handlers as $id => $handler) {
            $handler->message($message);
        }
    }

    /**
     * Check if output handlers have defined log priority with given name
     *
     * @param string $priority Priority name to check
     * @param string $handler  OPTIONAL Handler Id to check priority of (by default check all handlers)
     * @return boolean
     */
    public function havePriority($priority, $handler = null)
    {
        $handlers = $this->getHandlers();
        /* @var $hndObj Rx_Console_Output_Handler_Abstract */
        foreach ($handlers as $id => $hndObj) {
            if (($handler !== null) && ($handler != $id)) {
                continue;
            }
            $priorities = $hndObj->getPriorities();
            if (in_array($priority, $priorities)) {
                return (true);
            }
        }
        return (false);
    }

    /**
     * Register new prefix path for console process output handlers loader
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Loader_PluginLoader
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     */
    public function addPrefixPath($prefix, $path)
    {
        return ($this->_loader->addPrefixPath($prefix, $path));
    }

    /**
     * Add new console process output handler
     *
     * @param Rx_Console_Output_Handler_Abstract $handler Handler object instance
     * @param string $id                                  OPTIONAL Id of this handler
     * @return void
     * @throws Rx_Console_Exception
     */
    public function addHandler($handler, $id = null)
    {
        if (!$handler instanceof Rx_Console_Output_Handler_Abstract) {
            throw new Rx_Console_Exception('Console process output handler must be instance of Rx_Console_Output_Handler_Abstract');
        }
        if ($id === null) {
            $id = Rx_Uid::getRandomUid();
        }
        $this->_handlers[$id] = $handler;
    }

    /**
     * Get all available console process output handlers
     *
     * @return array
     */
    public function getHandlers()
    {
        if (!$this->_initialized) {
            $this->_initHandlers();
        }
        return ($this->_handlers);
    }

    /**
     * Get console process output handler by Id
     *
     * @param string $id Id of handler to get
     * @return Rx_Console_Output_Handler_Abstract|null
     */
    public function getHandler($id)
    {
        if (!$this->_initialized) {
            $this->_initHandlers();
        }
        if (array_key_exists($id, $this->_handlers)) {
            return ($this->_handlers[$id]);
        }
        return ($id);
    }

    /**
     * Initialize console process output handlers
     *
     * @return void
     * @throws Rx_Console_Exception
     */
    protected function _initHandlers()
    {
        if ($this->_initialized) {
            return;
        }
        // Read configuration for list of available output handlers
        // Configuration parameters structure:
        // rx.console.output.handler.<handlerId>.type = <type of handler object to use for handlers loader>
        // rx.console.output.handler.<handlerId>.config.<option name> = <option value as defined by handler configuration>
        $cfgPrefix = 'rx.console.output.handler';
        $types = array_unique(array_keys(Rx_Config::getArray($cfgPrefix)));
        foreach ($types as $typeId) {
            $options = Rx_Config::getConfig($cfgPrefix . '.' . $typeId);
            $handler = Rx_Config::get('type', null, $options);
            if (!strlen($handler)) {
                continue;
            }
            $class = Rx_Loader::loadPlugin($handler, $this->_loader);
            if (!$class) {
                trigger_error('Unavailable console process output handler type: ' . $handler, E_USER_WARNING);
                continue;
            }
            /* @var $class Rx_Console_Output_Handler_Abstract */
            $class = new $class(Rx_Config::getArray('config', $options));
            if (!$class instanceof Rx_Console_Output_Handler_Abstract) {
                throw new Rx_Console_Exception('Console process output handler type "' . $handler . '" must be instance of Rx_Console_Output_Handler_Abstract');
            }
            $this->addHandler($class, $typeId);
        }
        $this->_initialized = true;
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
        $this->message(
            'error',
            array(
                'level'     => $error['level'],
                'error'     => Rx_ErrorsHandler::formatError($error, false),
                'backtrace' => Rx_ErrorsHandler::formatBacktrace($error['backtrace'], false),
            )
        );
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
        $this->message(
            'exception',
            array(
                'error'     => Rx_ErrorsHandler::formatError($exception, false),
                'backtrace' => Rx_ErrorsHandler::formatBacktrace($exception['backtrace'], false),
            )
        );
    }

}
