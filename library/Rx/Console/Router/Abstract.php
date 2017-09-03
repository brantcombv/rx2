<?php

abstract class Rx_Console_Router_Abstract extends Rx_Configurable_Object
{
    /**
     * Instance of console process controller
     *
     * @var Rx_Console_Controller $_controller
     */
    protected $_controller = null;
    /**
     * Command-line options parser
     *
     * @var Rx_Console_Options $_options
     */
    protected $_options = null;
    /**
     * true if request was already routed, false otherwise
     *
     * @var boolean $_routed
     */
    protected $_routed = false;
    /**
     * Id of console process to run
     *
     * @var string $_process
     */
    protected $_process = null;
    /**
     * Id of console process task to run
     *
     * @var string $_task
     */
    protected $_task = null;

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     * @return Rx_Console_Router_Abstract
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->_process = null;
        $this->_task = null;
        $this->_routed = false;
    }

    /**
     * Route current request
     *
     * @return boolean
     */
    public function route()
    {
        if ($this->_routed) {
            return (true);
        }
        $process = null;
        $task = null;
        $result = $this->_route($process, $task);
        if ($result) {
            if (($process !== null) && ($this->getConfig('process_camelcase'))) {
                $process = $this->toCamelCase($process, true);
            }
            if (($task !== null) && ($this->getConfig('task_camelcase'))) {
                $task = $this->toCamelCase($task, false);
            }
            $this->setProcess($process, $task);
            $this->_routed = true;
        }
        return ($result);
    }

    /**
     * Perform routing of current request
     *
     * @param $processId
     * @param $taskId
     * @return boolean
     */
    protected function _route(&$processId, &$taskId)
    {
        // This method should be overridden to provide actual implementation
        // of request routing
        // Method should determine current process and task Ids
        // and set values of given $processId and $taskId arguments
        return (false);
    }

    /**
     * Get instance of console process controller
     *
     * @return Rx_Console_Controller
     */
    public function getController()
    {
        if (!$this->_controller) {
            $this->_controller = Rx_Console_Controller::getInstance();
        }
        return ($this->_controller);
    }

    /**
     * Set instance of console process controller to use
     *
     * @param Rx_Console_Controller $controller
     * @return Rx_Console_Router_Abstract
     * @throws Rx_Console_Exception
     */
    public function setController($controller)
    {
        if (!$controller instanceof Rx_Console_Controller) {
            throw new Rx_Console_Exception('Console process controller must be instance of Rx_Console_Controller');
        }
        $this->_controller = $controller;
        return ($this);
    }

    /**
     * Get command-line options object
     *
     * @return Rx_Console_Options
     */
    public function getOptions()
    {
        if (!$this->_options) {
            $options = new Rx_Console_Options(array(), $this->_getArgs(false));
            // If console process is not yet available - no real options are available too
            if (!$this->getController()->haveProcess()) {
                return ($options);
            }
            // Console process is already available - we can setup options for it
            $this->getController()->getProcess()->setupConsoleOptions($options);
            $options->parse();
            $this->_options = $options;
        }
        return ($this->_options);
    }

    /**
     * Get list of command-line arguments for command-line options object
     *
     * @param boolean $keepProgName OPTIONAL true to keep program name into list of arguments
     * @return array
     */
    protected function _getArgs($keepProgName = true)
    {
        // This method can be overridden in a case if it is necessary
        // to somehow filter list of command-line arguments
        // before passing them to options object

        // "argv" is required by Zend_Console_Getopt so recreate it if missed
        if (!array_key_exists('argv', $_SERVER)) {
            $argv = array();
            if (array_key_exists('PHP_SELF', $_SERVER)) {
                $argv[] = basename($_SERVER['PHP_SELF']);
            } elseif (array_key_exists('SCRIPT_NAME', $_SERVER)) {
                $argv[] = basename($_SERVER['SCRIPT_NAME']);
            } elseif (array_key_exists('SCRIPT_FILENAME', $_SERVER)) {
                $argv[] = basename($_SERVER['SCRIPT_FILENAME']);
            } elseif (array_key_exists('PATH_TRANSLATED', $_SERVER)) {
                $argv[] = basename($_SERVER['PATH_TRANSLATED']);
            }
            $_SERVER['argv'] = $argv;
            $_SERVER['argc'] = sizeof($_SERVER['argv']);
        }
        $argv = $_SERVER['argv'];
        if (!$keepProgName) {
            array_shift($argv);
        }
        return ($argv);
    }

    /**
     * Get name of console process to run
     *
     * @return string
     */
    public function getProcess()
    {
        return ($this->_process);
    }

    /**
     * Set name of console process to run
     *
     * @param string $process Name of console process or complete task Id in a form: process.task
     * @param string $task    OPTIONAL Name of console process task
     * @return Rx_Console_Router_Abstract
     * @event rx_console_route_changed
     */
    public function setProcess($process, $task = null)
    {
        if (!strlen($process)) {
            $process = null;
        }
        if (!strlen($task)) {
            $task = null;
        }
        if (strpos($process, '.') !== false) {
            $t = explode('.', $process, 2);
            $process = array_shift($t);
            $task = array_shift($t);
        }
        $this->_process = $process;
        $this->_task = $task;
        if ($process !== null) {
            $this->_routed = true;
        } // If we know process name - routing can be treated as completed
        $this->_options = null; // Reset console options since their initialization is process-specific
        Rx_Notify::notify(
            'rx_console_route_changed',
            array(
                'process' => $this->getProcess(),
                'task'    => $this->getTask(),
            ),
            $this
        );
        return ($this);
    }

    /**
     * Get name of console process task to run
     *
     * @return string|null
     */
    public function getTask()
    {
        return ($this->_task);
    }

    /**
     * Set name of console process task to run
     *
     * @param string $task Name of console process task
     * @return Rx_Console_Router_Abstract
     * @event rx_console_route_changed
     */
    public function setTask($task)
    {
        if (!strlen($task)) {
            $task = null;
        }
        $this->_task = $task;
        Rx_Notify::notify(
            'rx_console_route_changed',
            array(
                'process' => $this->getProcess(),
                'task'    => $this->getTask(),
            ),
            $this
        );
        return ($this);
    }

    /**
     * Convert given string to camel case
     *
     * @param string $string      String to convert
     * @param boolean $firstUpper true if first char needs to be in upper case
     * @return string
     */
    protected function toCamelCase($string, $firstUpper = false)
    {
        $string = trim($string);
        if (strpos($string, '_') !== false) {
            $string = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($string))));
        }
        if ($firstUpper) {
            $string = strtoupper(substr($string, 0, 1)) . substr($string, 1);
        } else {
            $string = strtolower(substr($string, 0, 1)) . substr($string, 1);
        }
        return ($string);
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'process_camelcase' => true, // true to convert process name from under_score notation to CamelCase
            'task_camelcase'    => true, // true to convert task name from under_score notation to camelCase
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
            case 'process_camelcase':
            case 'task_camelcase':
                $value = (boolean)$value;
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

}
