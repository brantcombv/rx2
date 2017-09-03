<?php

class Rx_AppState
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_AppState $_instance
     */
    protected static $_instance = null;
    /**
     * Initialization state of the object
     *
     * @var boolean $_init
     */
    protected $_init = false;
    /**
     * Session object to use for storing application state variables
     *
     * @var Zend_Session_Namespace $_session
     */
    protected $_session = null;
    /**
     * true if session should be used for storing application state, false to avoid session use
     *
     * @var boolean $_useSession
     */
    protected $_useSession = null;
    /**
     * Application state variables
     *
     * @var array $_vars
     */
    protected $_vars = array();
    /**
     * Plugin loader that is used to load application state objects
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_loader = null;
    /**
     * Instances of already loaded application state objects
     *
     * @var array $_structs
     */
    protected $_structs = array();

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Check if object is already initialized
     *
     * @return boolean
     */
    public static function isInitialized()
    {
        return (null !== self::$_instance);
    }

    /**
     * Singleton instance
     *
     * @return Rx_AppState
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            // Session usage must be enabled by default and this method also creates object instance and performs initialization
            self::setSessionUse(true);
        }
        if (!self::$_instance->_init) {
            self::$_instance->init();
        }

        return self::$_instance;
    }

    /**
     * Object initialization
     */
    protected function init()
    {
        if ($this->_useSession === null) {
            $this->_useSession = (php_sapi_name() == 'cli');
        }
        if ($this->_useSession) {
            if (!Zend_Session::isStarted()) {
                Zend_Session::start();
            }
            $this->_session = new Zend_Session_Namespace(__CLASS__);
        }
        $this->_init = true;
    }

    /**
     * Get plugin loader that is used to load application state objects
     *
     * @return Zend_Loader_PluginLoader
     */
    public static function getPluginLoader()
    {
        $instance = self::getInstance();
        if (!$instance->_loader) {
            $instance->_loader = Rx_Loader::getPluginLoader('Rx_AppState_Struct');
        }
        return ($instance->_loader);
    }

    /**
     * Get application state structure object by given name
     *
     * @param string $name              Name of application state structure object to get
     * @param array $struct             OPTIONAL Structure fields to set on class creation
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     * @return Rx_AppState_Struct_Abstract|null
     * @throws Rx_Exception
     */
    public static function getStruct($name, $struct = null, $config = null)
    {
        $instance = self::getInstance();
        if (array_key_exists($name, $instance->_structs)) {
            return ($instance->_structs[$name]);
        }
        $object = null;
        $class = Rx_Loader::loadPlugin($name, $instance->getPluginLoader());
        if ($class) {
            if (!is_subclass_of($class, 'Rx_AppState_Struct_Abstract')) {
                throw new Rx_Exception('Application state structure class "' . $class . '" is not instance of Rx_AppState_Struct_Abstract');
            }
            $object = new $class($struct, $config);
        }
        $instance->_structs[$name] = $object;
        return ($object);
    }

    /**
     * Set session use for application state storage
     *
     * @param boolean $status true to allow application state storage in session, false to only use local storage
     * @return void
     */
    public static function setSessionUse($status = true)
    {
        if (null !== self::$_instance) {
            trigger_error(
                __METHOD__ . ' must only be called before first use of ' . __CLASS__ . ' methods',
                E_USER_WARNING
            );
        }
        self::$_instance = new self();
        self::$_instance->_useSession = $status;
        self::$_instance->init();
    }

    /**
     * Get value of application state variable with given name and return $default if there is no such variable is available set.
     *
     * @param string|object|array $name Application state variable name to get value of
     * @param mixed $default            Default value to return in a case if variable is not available
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        $instance = self::getInstance();
        $value = $instance->__get($instance->_getName($name));
        if ($value === null) {
            $value = $default;
        }
        return ($value);
    }

    /**
     * Check if application state variable with given name is available
     *
     * @param string|object|array $name Application state variable name to check
     * @return boolean
     */
    public static function exists($name)
    {
        $instance = self::getInstance();
        return ($instance->__isset($instance->_getName($name)));
    }

    /**
     * Set value of application state variable with given name
     *
     * @param string|object|array $name     Application state variable name to set.
     *                                      If object is passed as variable name - it is automatically treated as private setting
     * @param mixed $value                  New value
     * @param boolean $private              OPTIONAL true to disable notification event firing,
     *                                      false to force notification event to be fired
     * @return void
     * @event rx_appstate_changed
     */
    public static function set($name, $value, $private = null)
    {
        $instance = self::getInstance();
        $instance->_set($name, $value, $private);
    }

    /**
     * Remove application state variable with given name
     *
     * @param string|object|array $name Application state variable name to remove
     * @return void
     * @event rx_appstate_changed
     */
    public static function remove($name)
    {
        $instance = self::getInstance();
        $instance->__unset($instance->_getName($name));
    }

    /**
     * Remove all application state variables
     *
     * @return void
     */
    public static function removeAll()
    {
        $instance = self::getInstance();
        if ($instance->_useSession) {
            $instance->_session->unsetAll();
        } else {
            $instance->_vars = array();
        }
        $instance->_structs = array();
    }

    /**
     * Resolve given name into actual application state variable name
     *
     * @param string|object|array $name Application variable name
     * @return string
     */
    protected function _getName($name)
    {
        if (is_string($name)) {
            return ($name);
        }
        if (!is_array($name)) {
            $name = array($name);
        }
        foreach ($name as $k => $v) {
            if (is_object($v)) {
                // Add some noise for object-based key name to make it harder
                // to access private object's information by object name
                $v = get_class($v);
                $name[$k] = join('_', array($v, Rx_Uid::getUid($v)));
            }
        }
        $name = join('_', $name);
        return ($name);
    }

    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->_useSession) {
            return ($this->_session->__get($name));
        } else {
            return ((isset($this->_vars[$name])) ? $this->_vars[$name] : null);
        }
    }

    /**
     * Support isset() overloading on PHP 5.1
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        if ($this->_useSession) {
            return ($this->_session->__isset($name));
        } else {
            return (isset($this->_vars[$name]));
        }
    }

    /**
     * Actual implementation of setting application state variables
     *
     * @param string|object|array $name     Application state variable name to set.
     *                                      If object is passed as variable name - it is automatically treated as private setting
     * @param mixed $value                  New value
     * @param boolean $private              OPTIONAL true to disable notification event firing,
     *                                      false to force notification event to be fired
     * @return void
     * @event rx_appstate_changed
     */
    protected function _set($name, $value, $private = null)
    {
        if (($private === null) && (!is_string($name))) {
            $private = false;
            if (!is_array($name)) {
                $name = array($name);
            }
            foreach ($name as $k => $v) {
                if (is_object($v)) {
                    $private = true;
                    break;
                }
            }
        }
        $name = $this->_getName($name);
        $prev = $this->__get($name);
        if ($this->_useSession) {
            $this->_session->__set($name, $value);
        } else {
            $this->_vars[$name] = $value;
        }
        // If value was changed - fire notification event about application state change
        if (($value !== $prev) && (!$private)) {
            Rx_Notify::notify(
                'rx_appstate_changed',
                array(
                    'name'  => $name,
                    'value' => $value,
                ),
                $this
            );
        }
    }

    /**
     * Magic function for setting application state variables
     *
     * @param string $name Application state variable name to set
     * @param mixed $value New value
     * @return void
     * @event rx_appstate_changed
     */
    public function __set($name, $value)
    {
        $this->_set($name, $value);
    }

    /**
     * Support unset() overloading on PHP 5.1
     *
     * @param  string $name
     * @return void
     * @event rx_appstate_changed
     */
    public function __unset($name)
    {
        if ($this->_useSession) {
            $this->_session->__unset($name);
        } else {
            unset($this->_vars[$name]);
        }
        // Fire notification event about application state change
        // Use same event name to avoid confusion for external classes
        Rx_Notify::notify(
            'rx_appstate_changed',
            array(
                'name'  => $name,
                'value' => null,
            ),
            $this
        );
    }

}
