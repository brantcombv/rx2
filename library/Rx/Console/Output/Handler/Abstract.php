<?php

abstract class Rx_Console_Output_Handler_Abstract extends Rx_Configurable_Object
{
    /**
     * Logger object to use for console output
     *
     * @var Zend_Log $_log
     */
    private $_log = null;
    /**
     * List of known priorities for logger object
     *
     * @var array $_priorities
     */
    protected $_priorities = array();
    /**
     * Loader for console process output formatters
     *
     * @var Zend_Loader_PluginLoader $_formatterLoader
     */
    protected $_formatterLoader = null;
    /**
     * true if output handler is enabled, false if not
     *
     * @var boolean $_enabled
     */
    protected $_enabled = true;

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     * @return Rx_Console_Output_Handler_Abstract
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->_log = null;
        $this->_formatterLoader = Rx_Loader::getPluginLoader('Rx_Console_Output_Formatter');
    }

    /**
     * Handler of calls to Zend_Log methods implicitly defined by log priorities
     *
     * @param string $method Priority name
     * @param string $params Message to log
     * @return void
     * @throws Rx_Console_Exception
     */
    public function __call($method, $params)
    {
        if (!$this->_enabled) {
            return;
        }
        if (!in_array($method, $this->_priorities)) {
            throw new Rx_Console_Exception('Unknown priority for console process output: ' . $method);
        }
        call_user_func_array(array($this->getLog(), $method), $params);
    }

    /**
     * Write given message into output without line feed
     *
     * @param string $message Message to write
     * @return void
     */
    public function write($message)
    {
        if (!$this->_enabled) {
            return;
        }
        // This method should be overridden in output handlers
        // that can handle output of arbitrary strings
    }

    /**
     * Write given message into output with line feed
     *
     * @param string $message OPTIONAL Message to write
     * @return void
     */
    public function writeln($message = '')
    {
        if (!$this->_enabled) {
            return;
        }
        // This method should be overridden in output handlers
        // that can handle output of arbitrary strings
    }

    /**
     * Write given output message object into output
     *
     * @param Rx_Console_Output_Message $message Message to write
     * @return void
     */
    public function message($message)
    {
        if (!$this->_enabled) {
            return;
        }
        // @TODO Implement console process output messages handling
        // This method should be overridden in output handlers
        // that can handle output messages
    }

    /**
     * Get logger object that is used for storing console process output
     *
     * @return Zend_Log
     */
    public function getLog()
    {
        if (!$this->_log instanceof Zend_Log) {
            $this->_initLog();
        }
        return ($this->_log);
    }

    /**
     * Initialize logger object
     *
     * @return void
     * @throws Rx_Console_Exception
     */
    protected function _initLog()
    {
        // Initialize logger object
        $this->_log = new Zend_Log();
        $r = new ReflectionClass($this->_log);
        $this->_priorities = array_flip($r->getConstants());
        // Initialize additional common priorities
        $priorities = array('success', 'fail', 'exception');
        foreach ($priorities as $p => $id) {
            $this->_log->addPriority($id, $p + 100);
            $this->_priorities[] = $id;
        }
        foreach ($this->_priorities as $k => $v) {
            $this->_priorities[$k] = strtolower($v);
        }
    }

    /**
     * Get list of available log priorities
     *
     * @return array
     */
    public function getPriorities()
    {
        if (!$this->_log instanceof Zend_Log) {
            $this->_initLog();
        }
        return ($this->_priorities);
    }

    /**
     * Register new prefix path for console process output formatter loader
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Loader_PluginLoader
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     */
    public function addPrefixPath($prefix, $path)
    {
        return ($this->_formatterLoader->addPrefixPath($prefix, $path));
    }

    /**
     * Get formatter object by given Id
     *
     * @param string $id OPTIONAL Id of formatter object to load
     * @return Rx_Console_Output_Formatter_Base
     * @throws Rx_Console_Exception
     */
    protected function _getFormatter($id = null)
    {
        $oId = $id;
        $class = null;
        if (!$id) {
            $id = $this->getConfig('formatter');
        }
        if ($id) {
            $class = Rx_Loader::loadPlugin($id, $this->_formatterLoader);
        }
        if (!$class) {
            if ($oId !== null) {
                trigger_error('Unavailable console process output formatter Id: ' . $oId, E_USER_WARNING);
            }
            $class = Rx_Loader::loadPlugin('base', $this->_formatterLoader);
            if (!$class) {
                throw new Rx_Console_Exception('Failed to load default console process output formatter');
            }
        }
        $class = new $class();
        if (!in_array('Zend_Log_Formatter_Interface', class_implements($class))) {
            throw new Rx_Console_Exception('Console process output formatter must implement Zend_Log_Formatter_Interface interface');
        }
        return ($class);
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'formatter' => null, // Id of console process output formatter to use
            'enabled'   => true, // true to enable output handler, false to disable
        ));
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'formatter':
                if (!strlen($value)) {
                    $value = null;
                }
                break;
            case 'enabled':
                $value = (boolean)$value;
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Configuration option value
     * @param string $operation Current operation Id
     * @return void
     */
    protected function _onConfigChanged($name, $value, $operation)
    {
        switch ($name) {
            case 'enabled':
                $this->_enabled = $value;
                break;
            default:
                parent::_onConfigChanged($name, $value, $operation);
                break;
        }
    }

}
