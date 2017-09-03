<?php

class Rx_ErrorsHandler
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_ErrorsHandler $_instance
     */
    protected static $_instance = null;
    /**
     * Object's configuration options
     *
     * @var array $_config
     */
    protected $_config = array(
        'logPath'       => null,
        'dumpPath'      => null,
        'errorPage'     => null,
        'errorUrl'      => null,
        'logErrors'     => true,
        'displayErrors' => true,
        'saveDumps'     => false,
    );
    /**
     * Mapping between PHP error level codes and their textual representation
     *
     * @var array $map
     */
    protected $map = array(
        E_ERROR             => 'ERRR',
        E_WARNING           => 'WARN',
        E_NOTICE            => 'NOTC',
        E_USER_ERROR        => 'UERR',
        E_USER_WARNING      => 'UWRN',
        E_USER_NOTICE       => 'UNTC',
        E_STRICT            => 'STCT',
        E_RECOVERABLE_ERROR => 'RCVR',
        'exception'         => 'EXCP',
    );
    /**
     * Error levels which should be treated as fatal
     *
     * @var array $fatal
     */
    protected $fatal = array(E_ERROR, 'exception');
    /**
     * Registered errors and exceptions listeners
     *
     * @var array $_listeners
     */
    protected $_listeners = array();
    /**
     * List of Ids of already dumped errors
     *
     * @var array $_dumped
     */
    protected $_dumped = array();
    /**
     * true if object was initialized already, false if not
     *
     * @var boolean $_initialized
     */
    protected $_initialized = false;
    /**
     * true during error handling process, false otherwise
     *
     * @var boolean $_handling
     */
    protected $_handling = false;

    private function __construct()
    {
        $this->_initialized = false;
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_ErrorsHandler
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::bootstrap();
        }
        return (self::$_instance);
    }

    /**
     * Class bootstrap
     */
    public static function bootstrap()
    {
        $instance = self::getInstance();
        if ($instance->_initialized) {
            return;
        }
        $instance->setConfig(Rx_Config::getArray('rx.errors'));
        // Install errors handlers
        set_error_handler(array($instance, 'errorsHandler'));
        set_exception_handler(array($instance, 'exceptionsHandler'));
    }

    /**
     * Set configuration options for errors handler
     *
     * @param Zend_Config|array $config Configuration options
     * @return void
     */
    public static function setConfig($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        if (!is_array($config)) {
            return;
        }
        $instance = self::getInstance();
        foreach ($instance->_config as $k => $v) {
            if (!array_key_exists($k, $config)) {
                continue;
            }
            $value = $config[$k];
            switch ($k) {
                case 'logPath':
                case 'dumpPath':
                    if (strlen($value)) {
                        $value = Rx_Path::normalize($value, true);
                        if (!file_exists($value)) {
                            Rx_Path::create($value);
                        }
                        if ((!file_exists($value)) || (!is_dir($value)) || (!is_writeable($value))) {
                            trigger_error(
                                'Unavailable or not writable path for "' . $k . '": ' . $value,
                                E_USER_WARNING
                            );
                            break;
                        }
                    }
                    $instance->_config[$k] = $value;
                    break;
                case 'errorPage':
                    if (strlen($value)) {
                        $value = Rx_Path::normalize($value, false);
                        if ((!file_exists($value)) || (is_dir($value)) || (!is_readable($value))) {
                            trigger_error('Unavailable or unreadable path for "' . $k . '": ' . $value, E_USER_WARNING);
                            break;
                        }
                    }
                    $instance->_config[$k] = $value;
                    break;
                case 'errorUrl':
                    $instance->_config[$k] = $value;
                    break;
                case 'displayErrors':
                    $instance->_config[$k] = (boolean)$value;
                    ini_set('display_errors', (boolean)$value);
                    break;
                case 'logErrors':
                    $instance->_config[$k] = (boolean)$value;
                    ini_set('log_errors', (boolean)$value);
                    break;
                case 'saveDumps':
                    $instance->_config[$k] = (boolean)$value;
                    break;
            }
        }
    }

    /**
     * Add errors/exceptions listener object
     *
     * @param Rx_ErrorsHandler_Listener_Interface $listener Errors/exceptions listener
     * @return void
     * @throws Rx_Exception
     */
    public static function addListener($listener)
    {
        if (!in_array('Rx_ErrorsHandler_Listener_Interface', class_implements($listener))) {
            throw new Rx_Exception('Errors listener should implement Rx_ErrorsHandler_Listener_Interface interface');
        }
        self::getInstance()->_listeners[] = $listener;
    }

    /**
     * Remove errors/exceptions listener object
     *
     * @param Rx_ErrorsHandler_Listener_Interface $listener Errors/exceptions listener to remove
     * @return void
     */
    public static function removeListener($listener)
    {
        $instance = self::getInstance();
        foreach ($instance->_listeners as $key => $object) {
            if ($object === $listener) {
                unset($instance->_listeners[$key]);
                return;
            }
        }
    }

    /**
     * Format error message from given error details
     *
     * @param array $details   Error/exception details as passed to Rx_ErrorsHandler_Listener_Interface
     * @param boolean $addDate OPTIONAL true to add date to error message, false to skip it (default)
     * @return string
     * @see Rx_ErrorsHandler_Listener_Interface
     */
    public static function formatError($details, $addDate = false)
    {
        $instance = self::getInstance();
        $type = (array_key_exists('type', $details)) ? $details['type'] : null;
        $level = (array_key_exists('level', $details)) ? $details['level'] : null;
        $message = (array_key_exists('message', $details)) ? $details['message'] : null;
        $filename = (array_key_exists('filename', $details)) ? $details['filename'] : null;
        $line = (array_key_exists('line', $details)) ? $details['line'] : null;
        $date = (array_key_exists('date', $details)) ? $details['date'] : time();
        if (($level == 'exception') && ($type)) {
            $message .= ' [' . $type . ']';
        }
        $level = (isset($instance->map[$level])) ? $instance->map[$level] : $level;
        $message = @sprintf('[%s] %s (%s:%s)', $level, $message, Rx_Path::getLocalPath($filename), $line);
        if ($addDate) {
            $message = '[' . strftime('%Y-%m-%d %H:%M:%S', $date) . '] ' . $message;
        }
        return ($message);
    }

    /**
     * Format given backtrace log
     *
     * @param array $backtrace     Backtrace log to format (either from debug_backtrace() or from formatBacktrace() itself)
     * @param array|bool $asString OPTIONAL true to return each element as string, false to return as array
     * @return array|string
     * @see debug_backtrace
     */
    public static function formatBacktrace($backtrace, $asString = false)
    {
        $result = array();
        if (!is_array($backtrace)) {
            return ($result);
        }
        foreach ($backtrace as $pos => $v) {
            if (!is_array($v)) {
                continue;
            }
            if (join('|', array_keys($v)) == 'scope|file') {
                $info = $v;
            } else {
                if ((isset($v['class'])) && ($v['class'])) {
                    $scope = $v['class'] . $v['type'] . $v['function'] . '()';
                } elseif ($v['function']) {
                    $scope = $v['function'] . '()';
                } else {
                    $scope = '** Global scope **';
                }
                $file = '*evaluated*';
                if (isset($v['file'])) {
                    $file = Rx_Path::getLocalPath($v['file']);
                    if (isset($v['line'])) {
                        $file .= ':' . $v['line'];
                    }
                }
                $info = array(
                    'scope' => $scope,
                    'file'  => $file,
                );
            }
            if ($asString) {
                $info = sprintf('#%-2d %s (%s)', $pos, $info['scope'], $info['file']);
            }
            $result[] = $info;
        }
        if ($asString) {
            $result = join("\n", $result) . "\n";
        }
        return ($result);
    }

    /**
     * Errors handler
     *
     * @param int $level       Error level (E_xxx constant)
     * @param string $message  Error message text
     * @param string $filename OPTIONAL Path to file where error occurs
     * @param int $line        OPTIONAL Line number where error occurs
     * @param array $context   OPTIONAL Error context
     * @return boolean
     * @see set_error_handler
     */
    public function errorsHandler($level, $message, $filename = null, $line = null, $context = null)
    {
        // First of all check, if erroneous expression was called with @ operator
        // If it is so, we should not handle this error
        $erLevel = error_reporting(0);
        error_reporting($erLevel);
        if (($erLevel == 0) || (($erLevel & $level) == 0)) {
            return (true);
        }
        if ($this->_handling) {
            // Error during error handling, write information in log and exit
            $info = sprintf(
                '[%s] %s (%s:%s)' . "\n" . '%s' . "\n",
                strftime('%d.%m.%Y %H:%M:%S'),
                $message,
                Rx_Path::getLocalPath($filename),
                $line,
                self::formatBacktrace(debug_backtrace(), true)
            );
            if ($this->_config['logPath']) {
                $path = $this->_config['logPath'];
            } elseif ($this->_config['dumpPath']) {
                $path = $this->_config['dumpPath'];
            } else {
                $path = null;
            }
            if ($path) {
                $path = dirname($path) . '/fatal_errors.log';
                if (!file_exists($path)) {
                    // Avoid problems with accessing log files created by different users
                    // e.g. if parts of application are run from console/crontab
                    touch($path);
                    chmod($path, 0666);
                }
                @error_log($info, 3, $path);
            }
            if ((!headers_sent()) && (php_sapi_name() != 'cli')) {
                header('HTTP/1.1 500 Internal Server Error', true, 500);
            }
            exit();
        }
        $this->_handling = true;
        $details = array(
            'date'      => time(),
            'level'     => $level,
            'message'   => $message,
            'filename'  => $filename,
            'line'      => $line,
            'backtrace' => null,
            'url'       => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null,
        );
        $trace = debug_backtrace();
        array_shift($trace);
        $details['backtrace'] = $trace;
        $this->displayError($details);
        $this->writeLog($details, 'errors.log');
        $this->saveDump($details);
        unset($details['url']);
        /* @var $listener Rx_ErrorsHandler_Listener_Interface */
        foreach ($this->_listeners as $listener) {
            $listener->handleError($details);
        }
        if (in_array($level, $this->fatal)) {
            $this->showErrorPage();
        }
        $this->_handling = false;
        return (true);
    }

    /**
     * Exceptions handler
     *
     * @param Exception $exception Exception object
     * @return void
     * @see set_exception_handler
     */
    public function exceptionsHandler($exception)
    {
        $this->_handling = true;
        $level = 'exception';
        $message = $exception->getMessage();
        $filename = $exception->getFile();
        $line = $exception->getLine();
        $backtrace = $exception->getTrace();
        $details = array(
            'date'      => time(),
            'level'     => $level,
            'type'      => get_class($exception),
            'message'   => $message,
            'code'      => $exception->getCode(),
            'filename'  => $filename,
            'line'      => $line,
            'backtrace' => $backtrace,
            'url'       => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null,
        );
        $this->displayError($details);
        $this->writeLog($details, 'exceptions.log');
        $this->saveDump($details);
        unset($details['url']);
        /* @var $listener Rx_ErrorsHandler_Listener_Interface */
        foreach ($this->_listeners as $listener) {
            $listener->handleException($details);
        }
        if (in_array($level, $this->fatal)) {
            $this->showErrorPage();
        }
        $this->_handling = false;
    }

    /**
     * Write log message about error
     *
     * @param array $details Error/exception details structure
     * @param string $log    Path to log file to write message in
     * @return void
     */
    protected function writeLog($details, $log)
    {
        if ((!$this->_config['logPath']) ||
            (!$this->_config['logErrors'])
        ) {
            return;
        }
        $message = $this->formatError($details, true) . "\n";
        $log = $this->_config['logPath'] . $log;
        $fp = @fopen($log, 'a');
        if (is_resource($fp)) {
            flock($fp, LOCK_EX);
            fwrite($fp, $message);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Display error on output device (browser or screen)
     *
     * @param array $details Error/exception details structure
     * @return void
     */
    protected function displayError($details)
    {
        if (!$this->_config['displayErrors']) {
            return;
        }
        $message = $this->formatError($details);
        if (php_sapi_name() == 'cli') {
            echo $message . "\n";
            echo $this->formatBacktrace($details['backtrace'], true);
        } else {
            $_id = 'err' . dechex(crc32(microtime()));
            echo '<div style="background-color: ' . ((in_array($details['level'], $this->fatal)) ? '#f99' : '#ff6') .
                '; color: #000; font-family: Tahoma, sans-serif; font-size: 10pt; padding: 1px 3px; margin: 1px">' .
                ((is_array(
                    $details['backtrace']
                )) ? '<a href="#" onclick="var e=document.getElementById(\'' . $_id . '\');e.style.display=(e.style.display==\'none\')?\'block\':\'none\';return false;">[+]</a> ' : '') .
                $message;
            $trace = $this->formatBacktrace($details['backtrace']);
            if (sizeof($trace)) {
                echo '<div id="' . $_id . '" style="margin: 5px; margin-left: 20px; display: none">';
                foreach ($trace as $v) {
                    echo sprintf(
                        '<div style="padding: 1px; font-size: 8pt">%s (%s)</div>' . "\n",
                        $v['scope'],
                        $v['file']
                    );
                }
                echo '</div>' . "\n";
            }
            echo '</div>' . "\n";
        }
    }

    /**
     * Save dump information about error
     *
     * @param array $dump Dump information about error
     * @return void
     */
    protected function saveDump($dump)
    {
        if ((!$this->_config['dumpPath']) ||
            (!$this->_config['saveDumps'])
        ) {
            return;
        }
        $fn = strtolower($this->map[$dump['level']]) . '_' . Rx_Uid::getUid(
                $dump['level'] . $dump['message'] . $dump['filename'],
                $dump['line'],
                true
            ) . '.dat';
        if (in_array($fn, $this->_dumped)) {
            return;
        }
        $this->_dumped[] = $fn;
        $path = $this->_config['dumpPath'] . $fn;
        if (file_exists($path)) {
            $this->_dumped[] = $fn;
            return;
        }
        $dump['filename'] = Rx_Path::getLocalPath($dump['filename']);
        $dump['backtrace'] = $this->formatBacktrace($dump['backtrace']);
        $fp = @fopen($path, 'a');
        if (is_resource($fp)) {
            flock($fp, LOCK_EX);
            fwrite($fp, serialize($dump));
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Display error page into browser
     *
     * @return void
     */
    protected function showErrorPage()
    {
        if (($this->_config['errorUrl']) && (!headers_sent())) {
            header('Location: ' . $this->_config['errorUrl']);
            exit();
        } elseif ($this->_config['errorPage']) {
            header('Content-Type: text/html');
            echo file_get_contents($this->_config['errorPage']);
            exit();
        }
    }

}
