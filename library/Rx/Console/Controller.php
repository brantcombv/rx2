<?php

class Rx_Console_Controller implements Rx_Notify_Observer
{
    /* Constants for defining types of class loaders available in this class */
    const LDR_ROUTER = 'router'; // Console process router class loader
    const LDR_PROCESS = 'process'; // Console process class loader

    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Console_Controller $_instance
     */
    protected static $_instance = null;
    /**
     * Console controller configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;
    /**
     * Loader for console process router class
     *
     * @var Zend_Loader_PluginLoader $_routerLoader
     */
    protected $_routerLoader = null;
    /**
     * Console process router
     *
     * @var Rx_Console_Router_Abstract $_router
     */
    protected $_router = null;
    /**
     * Loader for console process classes
     *
     * @var Zend_Loader_PluginLoader $_processLoader
     */
    protected $_processLoader = null;
    /**
     * Instance of console process object to run
     *
     * @var Rx_Console_Process_Abstract $_process
     */
    protected $_process = null;
    /**
     * Current console process task to run
     *
     * @var string $_task
     */
    protected $_task = null;
    /**
     * Console process output handler object
     *
     * @var Rx_Console_Output $_output
     */
    protected $_output = null;

    protected function __construct()
    {
        $this->_config = new Rx_Configurable_Embedded($this, array(
            'pids' => null, // Path to directory where to store PID files of console processes
        ), array(
            'checkConfig' => 'checkConfig',
        ), Rx_Config::getArray('rx.console.controller.config'));
        $this->_routerLoader = Rx_Loader::getPluginLoader('Rx_Console_Router');
        $this->_processLoader = Rx_Loader::getPluginLoader('Rx_Console_Process');
        Rx_Notify::subscribe($this, 'rx_console_route_changed');
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Console_Controller
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Run console process
     *
     * @return void
     * @throws Rx_Console_Exception
     */
    public function run()
    {
        // Get current console process object, it will be determined by routing request if necessary
        $process = $this->getProcess();
        // Singleton processes should not be have more then one copy launched at once
        if ($process->isSingleton()) {
            if ($this->isRunning($process)) {
                $keepRunning = $process->handleProblem(Rx_Console_Process_Abstract::PROBLEM_ALREADY_RUNNING);
                if (!$keepRunning) {
                    return;
                }
            }
            $this->setPid($process);
        }
        // Setup command-line options
        try {
            $options = $this->getRouter()->getOptions();
            // Check if command-line options are required but not defined
            $noOptions = false;
            if ($options->haveCommands()) {
                $noOptions = ($options->getCommand() === null);
            } elseif ($options->haveRules()) {
                $noOptions = (!sizeof($options->getOptions()));
            }
            if ($noOptions) {
                $keepRunning = $process->handleProblem(Rx_Console_Process_Abstract::PROBLEM_NO_OPTIONS);
                if (!$keepRunning) {
                    return;
                }
            }
        } catch (Zend_Console_Getopt_Exception $e) {
            $keepRunning = $process->handleProblem(
                Rx_Console_Process_Abstract::PROBLEM_INVALID_OPTION,
                $e->getMessage()
            );
            if (!$keepRunning) {
                return;
            }
        }
        // Initialize console process
        $keepRunning = $process->init();
        if ($keepRunning !== false) {
            // Determine console process task to run
            $method = $this->getTask(true);
            if ($method !== null) {
                try {
                    $process->$method();
                } catch (Exception $e) {
                    $process->handleProblem(Rx_Console_Process_Abstract::PROBLEM_EXCEPTION, $e);
                }
            } else {
                $process->handleProblem(Rx_Console_Process_Abstract::PROBLEM_NO_TASK);
            }
        }
        // Finalize console process
        $process->shutdown();
        if ($process->isSingleton()) {
            $this->removePid($process);
        }
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
     * Register new prefix path for one of available class loaders
     *
     * @param string $type   Class loader type (one of LDR_xxxx constants)
     * @param string $prefix Class prefix
     * @param string $path   Path prefix
     * @return void
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     * @throws Rx_Console_Exception
     */
    public function addPrefixPath($type, $prefix, $path)
    {
        switch ($type) {
            case self::LDR_ROUTER:
                $this->_routerLoader->addPrefixPath($prefix, $path);
                break;
            case self::LDR_PROCESS:
                $this->_processLoader->addPrefixPath($prefix, $path);
                break;
            default:
                throw new Rx_Console_Exception('Unknown type of class loader: ' . $type);
                break;
        }
    }

    /**
     * Get console process router object
     *
     * @return Rx_Console_Router_Abstract
     * @throws Rx_Console_Exception
     */
    public function getRouter()
    {
        if (!$this->_router) {
            // Initialize router object based on configuration
            $config = Rx_Config::getConfig('rx.console.router');
            $type = Rx_Config::get('type', null, $config);
            if (!strlen($type)) {
                throw new Rx_Console_Exception('No console process router type is defined in configuration');
            }
            $router = Rx_Loader::loadPlugin($type, $this->_routerLoader);
            if (!$router) {
                throw new Rx_Console_Exception('Unavailable console process router type: ' . $type);
            }
            /* @var $router Rx_Console_Router_Abstract */
            $router = new $router(Rx_Config::getArray('config', $config));
            $this->setRouter($router);
        }
        return ($this->_router);
    }

    /**
     * Set console process router object
     *
     * @param Rx_Console_Router_Abstract $router Console process router object
     * @return void
     * @throws Rx_Console_Exception
     */
    public function setRouter($router)
    {
        if (!$router instanceof Rx_Console_Router_Abstract) {
            throw new Rx_Console_Exception('Console process router must be instance of Rx_Console_Router_Abstract');
        }
        $this->_router = $router;
        $this->_router->setController($this);
    }

    /**
     * Check is console process object is already defined
     *
     * @return boolean
     */
    public function haveProcess()
    {
        return ($this->_process instanceof Rx_Console_Process_Abstract);
    }

    /**
     * Get current console process object
     *
     * @return Rx_Console_Process_Abstract
     * @throws Rx_Console_Exception
     */
    public function getProcess()
    {
        if (!$this->_process) {
            // If we have no process - we should get process information from router,
            // load and instantiate process object
            $this->getRouter()->route();
            $processId = $this->getRouter()->getProcess();
            if (!$processId) {
                throw new Rx_Console_Exception('No process Id is determined by console process router');
            }
            $process = Rx_Loader::loadPlugin($processId, $this->_processLoader);
            if (!$process) {
                throw new Rx_Console_Exception('Unavailable console process object Id: ' . $processId);
            }
            /* @var $process Rx_Console_Process_Abstract */
            $process = new $process();
            $this->setProcess($process);
        }
        return ($this->_process);
    }

    /**
     * Set current console process object
     *
     * @param Rx_Console_Process_Abstract $process Console process object
     * @return void
     * @throws Rx_Console_Exception
     */
    public function setProcess($process)
    {
        if (!$process instanceof Rx_Console_Process_Abstract) {
            throw new Rx_Console_Exception('Console process object must be instance of Rx_Console_Process_Abstract');
        }
        $this->_process = $process;
    }

    /**
     * Get console process output object
     *
     * @return Rx_Console_Output
     */
    public function getOutput()
    {
        if (!$this->_output) {
            $this->_output = new Rx_Console_Output();
        }
        return ($this->_output);
    }

    /**
     * Get current console process task
     *
     * @param boolean $asMethod OPTIONAL true to return console process method name, false to return task Id
     * @return string
     */
    public function getTask($asMethod = false)
    {
        if (!$this->_task) {
            // Determine task Id. We should be sure that console process
            // is determined by this time because task is related to process
            $process = $this->getProcess();
            $task = $this->getRouter()->getTask();
            if ($task === null) {
                // If no task is found by router - check if task is found by command line options parser
                $task = $this->getRouter()->getOptions()->getCommand();
                if ($task === null) // Last chance - check if we have some default task to run
                {
                    $task = $process->getDefaultTask();
                }
            }
            if ($task !== null) {
                $this->_task = $task;
            }
        }
        $task = $this->_task;
        if (($task !== null) && ($asMethod)) {
            // Generate console process task method name
            $task = $this->toCamelCase($task, false);
            $task .= 'Task';
        }
        return ($task);
    }

    /**
     * Handle given notification event
     *
     * @param Rx_Notify_Event $event Notification event object
     * @return void
     */
    public function handleNotify($event)
    {
        switch ($event->getType()) {
            case 'rx_console_route_changed':
                // Reset current process and task information
                // so they will be recalculated upon request
                $this->_process = null;
                $this->_task = null;
                break;
        }
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @throws Rx_Console_Exception
     * @return boolean
     */
    public function checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'pids':
                if (!strlen($value)) {
                    $value = ':temp';
                }
                $path = Rx_Path::normalize($value, true);
                if (!is_dir($path)) {
                    throw new Rx_Console_Exception('Path to PID files directory is not available: ' . $value);
                }
                if (!is_writable($path)) {
                    throw new Rx_Console_Exception('Path to PID files directory is not writable: ' . $value);
                }
                $value = $path;
                break;
        }
        return (true);
    }

    /**
     * Check if another instance of console process is already running
     *
     * @param Rx_Console_Process_Abstract|string $process OPTIONAL Console process object or process Id
     * @return boolean                                      true if another process instance is running, false if not
     */
    protected function isRunning($process = null)
    {
        $path = $this->getPidFile($process);
        if (!file_exists($path)) {
            return (false);
        }
        $fPid = file_get_contents($path);
        if (!preg_match('/^\d+$/', $fPid)) {
            trigger_error('Invalid contents of PID file', E_USER_WARNING);
            return (true);
        }
        // Check if we have process running
        $pid = null;
        if (Rx_Path::isUnix()) {
            // Unix-specific check
            $fp = popen('ps -p ' . escapeshellarg($fPid) . ' -o pid=', 'r');
            if (!is_resource($fp)) {
                trigger_error('Failed to check console process existence' . $fPid, E_USER_WARNING);
                return (true);
            }
            $pid = '';
            while (!feof($fp)) {
                $pid .= fread($fp, 4096);
            }
            pclose($fp);
        } else {
            // Windows-specific check
            $fp = popen('tasklist /fi "pid eq ' . $fPid . '" /fo csv /nh', 'r');
            if (!is_resource($fp)) {
                trigger_error('Failed to check console process existence: ' . $fPid, E_USER_WARNING);
                return (true);
            }
            $result = '';
            while (!feof($fp)) {
                $result .= fread($fp, 4096);
            }
            pclose($fp);
            if (preg_match('/^\"[^\"]*\",\"(\d+)\"/', $result, $m)) {
                $pid = $m[1];
            } else {
                return (false);
            } // Windows outputs error message in a case if no matching process is found
        }
        if ($pid == $fPid) {
            return (true);
        }
        return (false);
    }

    /**
     * Set PID file for given console process
     *
     * @param Rx_Console_Process_Abstract|string $process OPTIONAL Console process object or process Id
     * @return boolean
     */
    protected function setPid($process = null)
    {
        $path = $this->getPidFile($process);
        if (file_exists($path)) {
            $r = @unlink($path);
            if (!$r) {
                trigger_error('Failed to remove PID file: ' . $path, E_USER_WARNING);
                return (false);
            }
        }
        $r = @file_put_contents($path, getmypid());
        if (!$r) {
            trigger_error('Failed to create PID file: ' . $path, E_USER_WARNING);
            return (false);
        }
        return (true);
    }

    /**
     * Remove PID file for given console process
     *
     * @param Rx_Console_Process_Abstract|string $process OPTIONAL Console process object or process Id
     * @return boolean
     */
    protected function removePid($process = null)
    {
        $path = $this->getPidFile($process);
        if (!file_exists($path)) {
            return (true);
        }
        $r = @unlink($path);
        if (!$r) {
            trigger_error('Failed to remove PID file: ' . $path, E_USER_WARNING);
            return (false);
        }
        return (true);
    }

    /**
     * Get path to PID file for given console process
     *
     * @param Rx_Console_Process_Abstract|string $process OPTIONAL Console process object or process Id
     * @return string
     */
    protected function getPidFile($process = null)
    {
        if ($process === null) {
            $process = $this->getProcess();
        }
        if ($process instanceof Rx_Console_Process_Abstract) {
            $process = $process->getId();
        }
        $path = Rx_Path::build($this->getConfig('pids'), $process . '.pid', false);
        return ($path);
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

}
