<?php

/**
 * Base implementation of structure
 * with transparent storage of its contents
 * into application state
 */
abstract class Rx_AppState_Struct_Abstract extends Rx_Struct_Abstract
{
    /**
     * Nesting level of structure fields setting process
     * Required for correct handling of nested calls to set() from custom setters
     *
     * @var int $_settingLevel
     */
    protected $_settingLevel = 0;

    /**
     * Class constructor
     *
     * @param array $struct             OPTIONAL Structure fields to set on class creation
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     */
    public function __construct($struct = null, $config = null)
    {
        parent::__construct(null, $config);
        if ($struct !== null) {
            $this->set($struct);
        }
        if (!Rx_AppState::exists($this->_getStateKey())) {
            $this->_saveState();
            $this->_initState();
        }
    }


    /**
     * Initialize list of configuration options
     *
     * @return void
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'state_key' => null, // Key name for storing this structure in application state
            'private'   => true, // @see Rx_AppState::set() for description
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
            case 'state_key':
                $value = trim($value);
                if (!strlen($value)) {
                    $value = $this->_generateStateKey();
                }
                break;
            case 'private':
                $value = (boolean)$value;
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
        if ($name == 'state_key') {
            // Reload structure's application state upon state key change
            $this->_settingLevel = 0;
            $this->_loadState();
        }
    }

    /**
     * Retrieve value of structure field with given name and return $default if there is no element set.
     *
     * @param string $name                   Structure element name to get value of
     * @param mixed $default                 OPTIONAL Default value to return in a case if element is not available
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return mixed
     */
    public function get($name, $default = null, $config = null)
    {
        if ($this->_settingLevel == 0) {
            $this->_loadState();
        }
        return (parent::get($name, $default, $config));
    }

    /**
     * Set value of structure field with given name
     *
     * @param string|array $name             Either structure element name to set value of or array of structure fields to set
     * @param mixed $value                   OPTIONAL New value for this element (only if $name is a string)
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function set($name, $value = null, $config = null)
    {
        if ($this->_settingLevel == 0) {
            $this->_loadState();
        }
        $this->_settingLevel++;
        parent::set($name, $value, $config);
        $this->_settingLevel--;
        if ($this->_settingLevel == 0) {
            $this->_saveState();
        }
    }

    /**
     * Return structure as associative array
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->_settingLevel == 0) {
            $this->_loadState();
        }
        return (parent::toArray());
    }

    /**
     * Implementation of Serializable interface
     *
     * @return string
     */
    public function serialize()
    {
        if ($this->_settingLevel == 0) {
            $this->_loadState();
        }
        return (parent::serialize());
    }

    /**
     * Implementation of Serializable interface
     *
     * @param array $data Serialized object data
     * @return void
     */
    public function unserialize($data)
    {
        parent::unserialize($data);
        $this->_saveState();
    }

    /**
     * Reset structure to its initial state
     *
     * @return void
     */
    public function reset()
    {
        parent::reset();
        $this->_saveState();
    }

    /**
     * Perform initialization of application state structure
     * Unlike init() which initializes structure itself this method
     * means to be called to initialize structure contents in a case
     * if there is no stored contents available
     *
     * @return void
     */
    protected function _initState()
    {

    }

    /**
     * Load state from AppState storage
     *
     * @return void
     */
    protected function _loadState()
    {
        $this->_struct = Rx_AppState::get($this->_getStateKey(), $this->_struct);
    }

    /**
     * Save state into AppState storage
     *
     * @return void
     */
    protected function _saveState()
    {
        Rx_AppState::set($this->_getStateKey(), $this->_struct, $this->getConfig('private'));
    }

    /**
     * Get application state key to access state information with
     *
     * @return string
     */
    protected function _getStateKey()
    {
        $key = $this->getConfig('state_key');
        if (!$key) {
            $key = $this->_generateStateKey();
            $this->setConfig('state_key', $key);
        }
        return ($key);
    }

    /**
     * Generate application state key to access state information with
     *
     * @return string
     */
    protected function _generateStateKey()
    {
        $key = get_class($this);
        if ($this->getConfig('private')) {
            $key = join('_', array($key, Rx_Uid::getUid($key)));
        }
        return ($key);
    }

}
