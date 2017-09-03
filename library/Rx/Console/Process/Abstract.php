<?php

abstract class Rx_Console_Process_Abstract
{
    // Constants for problematic situations that should be handled by handleProblem()
    const PROBLEM_NO_OPTIONS = 'noOptions'; // No options are passed for process from command line
    const PROBLEM_INVALID_OPTION = 'invalidOption'; // Some problem occurs while parsing list of command line options
    const PROBLEM_NO_TASK = 'noTask'; // No task is determined by router
    const PROBLEM_INVALID_TASK = 'invalidTask'; // Unknown task is being called
    const PROBLEM_ALREADY_RUNNING = 'alreadyRunning'; // Singleton process is already running
    const PROBLEM_EXCEPTION = 'exception'; // Some unhandled exception was thrown while process task running

    /**
     * Instance of console process controller
     *
     * @var Rx_Console_Controller
     */
    protected $_controller = null;
    /**
     * true if only one instance of console process should be run at a time
     *
     * @var boolean $_singleton
     */
    protected $_singleton = false;

    /**
     * Magic method to handler calls to undeclared task methods
     *
     * @param string $method Name of called method
     * @param array $args    Arguments passed to method
     * @return void
     * @throws Rx_Console_Exception
     */
    public function __call($method, $args)
    {
        if (preg_match('/(.+)Task$/', $method, $t)) // Call to console process task method
        {
            $this->handleProblem(self::PROBLEM_INVALID_TASK, $t[1]);
        } elseif ($this->getOutput()->havePriority($method)) // Call to console output
        {
            call_user_func_array(array($this->getOutput(), $method), $args);
        } else // Call to some other method
        {
            throw new Rx_Console_Exception('Call to unavailable console process method: ' . $method);
        }
    }

    /**
     * Setup command-line options configuration in given console options object
     * as needed by console process
     *
     * @param Rx_Console_Options $options
     * @return void
     */
    public function setupConsoleOptions($options)
    {
        // This method should be overridden in a case if console process
        // accepts some options from command-line
    }

    /**
     * Initialization hook for console process run
     * Called before calling console process task handling method
     *
     * @return boolean      false to prevent task to be running
     */
    public function init()
    {
        return (true);
    }

    /**
     * Shutdown hook for console process run
     * Called after calling console process task handling method
     *
     * @return void
     */
    public function shutdown()
    {

    }

    /**
     * Handle problematic situation
     *
     * @param string $problem Problem Id (one of PROBLEM_xxxx constants)
     * @param mixed $params   OPTIONAL Additional parameters for problem
     * @return boolean          true to keep process running, false to shutdown it
     */
    public function handleProblem($problem, $params = null)
    {
        switch ($problem) {
            case self::PROBLEM_NO_OPTIONS:
                $this->getOutput()->writeln($this->getOptions()->getUsageMessage());
                break;
            case self::PROBLEM_INVALID_OPTION:
                $this->getOutput()->err($params);
                break;
            case self::PROBLEM_NO_TASK:
                $this->getOutput()->err('No current task is defined');
                break;
            case self::PROBLEM_INVALID_TASK:
                $this->getOutput()->err('Unknown task Id: ' . $params);
                break;
            case self::PROBLEM_ALREADY_RUNNING:
                $this->getOutput()->err('Another instance of this console process is already running');
                break;
            case self::PROBLEM_EXCEPTION:
                if ($params instanceof Exception) {
                    Rx_ErrorsHandler::getInstance()->exceptionsHandler($params);
                } else {
                    $this->getOutput()->exception('Unknown exception occurs');
                }
                break;
            default:
                $this->getOutput()->err('Unknown problem occurs: ' . $problem);
                break;
        }
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
     * @param Rx_Console_Controller $controller Console process controller object
     * @return Rx_Console_Process_Abstract
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
        return ($this->getController()->getRouter()->getOptions());
    }

    /**
     * Get console process output object
     *
     * @return Rx_Console_Output
     */
    public function getOutput()
    {
        return ($this->getController()->getOutput());
    }

    /**
     * Get Id of this console process
     *
     * @return string
     */
    public function getId()
    {
        $id = explode('_', get_class($this));
        $id = array_pop($id);
        return ($id);
    }

    /**
     * Get default task Id
     *
     * @return string|null      Id of default task or null if there is no default task
     */
    public function getDefaultTask()
    {
        if ($this->getOptions()->haveCommands()) {
            return (null);
        } else {
            return ('default');
        }
    }

    /**
     * Check if only one instance of this console process can be run at a time
     *
     * @return boolean
     */
    public function isSingleton()
    {
        return ($this->_singleton);
    }

    /**
     * Write given message into output without line feed
     *
     * @param string $message Message to write
     * @return void
     */
    public function write($message)
    {
        $this->getOutput()->write($message);
    }

    /**
     * Write given message into output with line feed
     *
     * @param string $message OPTIONAL Message to write
     * @return void
     */
    public function writeln($message = '')
    {
        $this->getOutput()->writeln($message);
    }

    /**
     * Write message into output
     *
     * @param Rx_Console_Output_Message|string $message Either message object or message Id
     * @param array $params                             OPTIONAL Additional message parameters (used only if message Id is passed as $message)
     * @return void
     * @throws Rx_Console_Exception
     */
    public function message($message, $params = null)
    {
        $this->getOutput()->message($message, $params, $this);
    }

}
