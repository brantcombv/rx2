<?php

abstract class Rx_Struct_Model_Abstract extends Rx_Struct_Abstract
{
    /**
     * Name of corresponding Rx_Model_Entity based class (named Id to use for Rx_ModelManager)
     *
     * @var string $_entityClassName
     */
    protected $_entityClassName = null;
    /**
     * Entity model object for this structure object
     *
     * @var Rx_Model_Entity $_entity
     */
    protected $_entity = null;
    /**
     * true to allow to get/set structure fields that are not explicitly defined in structure array
     *
     * @var boolean $_virtualFieldsAllowed
     */
    protected $_virtualFieldsAllowed = false;
    /**
     * Meanings of certain structure fields
     *
     * @var array $_meaning
     */
    protected $_meanings = array();
    /**
     * List of read-only fields in structure
     *
     * @var array $_readOnly
     */
    protected $_readOnly = array();
    /**
     * List of write-only fields in structure
     *
     * @var array $_writeOnly
     */
    protected $_writeOnly = array();
    /**
     * List of calculated fields in structure
     *
     * @var array $_calculated
     */
    protected $_calculated = array();
    /**
     * List of linkable fields in structure
     *
     * @var array $_linkable
     */
    protected $_linkable = array();
    /**
     * List of array fields in structure
     *
     * @var array $_arrayFields
     */
    protected $_arrayFields = array();
    /**
     * List of transient structure fields (they will not be saved during object serialization)
     *
     * @var array $_transient
     */
    protected $_transient = array();
    /**
     * List of initialized structure fields
     *
     * @var array $_initialized
     */
    protected $_initialized = array();
    /**
     * List of modified structure fields
     *
     * @var array $_modified
     */
    protected $_modified = array();
    /**
     * List of structure fields that have objects that links to this structure
     *
     * @var array $_linked
     */
    protected $_linked = array();
    /**
     * Link to parent structure object to allow organization of multi-level structures
     *
     * @var Rx_Struct_Model_Abstract $_parent
     */
    protected $_parent = null;
    /**
     * Name of structure field of parent structure object, this structure is linked to
     *
     * @var string $_parentName
     */
    protected $_parentName = null;

    /**
     * Class constructor
     *
     * @param array $struct             OPTIONAL Structure fields to set on class creation
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     */
    public function __construct($struct = null, $config = null)
    {
        // Don't pass given structure to parent constructor because of calling to set()
        // which we need to be called with different configuration options
        parent::__construct(null, $config);
        if ($struct !== null) {
            $this->set($struct, null, $this->getConfig(array(
                'constructor' => true,
            )));
        }
    }

    /**
     * Perform pre-checks for operations with structure fields
     *
     * @param string $name                   Structure field name
     * @param boolean $array                 true if array operation is performed, false for plain structure field operation
     * @param boolean $write                 true if write operation will be performed, false for read operation
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return boolean
     */
    protected function _check($name, $array, $write, $config)
    {
        static $stack = array();

        if ((!array_key_exists($name, $this->_struct)) && (!$this->_virtualFieldsAllowed)) {
            trigger_error(
                'Attempt to ' . (($write) ? 'write to' : 'read from') . ' unavailable property "' . $name . '" for structure "' . get_class(
                    $this
                ) . '"',
                E_USER_WARNING
            );
            return (false);
        }
        if ($array) {
            if (!array_key_exists($name, $this->_arrayFields)) {
                trigger_error('Attempt to array access to non-array structure field: ' . $name, E_USER_NOTICE);
                return (false);
            }
            if (!is_array($this->_struct[$name])) {
                trigger_error('Uninitialized array structure field: ' . $name, E_USER_WARNING);
                $this->_struct[$name] = array();
            }
        }
        $config = $this->getConfig($config);
        if ($write) {
            if ((in_array($name, $this->_readOnly)) && (!$config['force_write'])) {
                trigger_error('Attempt to write into read-only structure field: ' . $name, E_USER_NOTICE);
                return (false);
            }
        } else {
            if ((in_array($name, $this->_writeOnly)) && (!$config['force_read'])) {
                trigger_error('Attempt to read write-only structure field: ' . $name, E_USER_NOTICE);
                return (false);
            }
            // If uninitialized structure field is being read - allow it to get initialized
            // However we should avoid infinite recursion in a case if _initField() will use getter of same field
            if ((!$this->isInitialized($name)) &&
                (!in_array($name, $stack)) &&
                (!$config['skip_init'])
            ) {
                array_push($stack, $name);
                $this->_initField($name, $config);
                array_pop($stack);
            }
        }
        return (true);
    }

    /**
     * Perform initialization of structure field upon access
     *
     * @param string $name                   Structure field name
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return void
     */
    protected function _initField($name, $config)
    {
        // This method can be overridden if some custom actions need to be performed
        // to initialize structure field before first access to it will occur
    }

    /**
     * Retrieve value of structure field with given name and return $default if there is no element set.
     *
     * @param string $name                      Structure element name to get value of
     * @param mixed $default                    OPTIONAL Default value to return in a case if element is not available
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param array|boolean $modify             OPTIONAL Modification for given configuration options
     *                                          Boolean values are treated as shortcut for "force_read" option
     * @return mixed
     */
    public function get($name, $default = null, $config = null, $modify = null)
    {
        $config = $this->getConfig($config);
        if (is_bool($modify)) {
            $modify = array('force_read' => $modify);
        }
        if (is_array($modify)) {
            $config = $this->modifyConfig($config, $modify);
        }
        if (!$this->_check($name, false, false, $config)) {
            return ($default);
        }
        if ($config['get_raw_value']) { // We should skip custom getters but still keep logic of _get()
            $result = self::_get($name, $default, $config);
        } else {
            $result = $this->_get($name, $default, $config);
        }
        return ($result);
    }

    /**
     * Set value of structure field with given name
     *
     * @param string|array $name                Either structure element name to set value of or array of structure fields to set
     * @param mixed $value                      OPTIONAL New value for this element (only if $name is a string)
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param array|boolean $modify             OPTIONAL Modification for given configuration options
     *                                          Boolean values are treated as shortcut for "force_write" option
     * @return void
     */
    public function set($name, $value = null, $config = null, $modify = null)
    {
        if ((is_object($name)) && (is_callable(array($name, 'toArray')))) {
            $name = $name->toArray();
        }
        $config = $this->getConfig($config);
        if (is_bool($modify)) {
            $modify = array('force_write' => $modify);
        }
        if (is_array($modify)) {
            $config = $this->modifyConfig($config, $modify);
        }
        if (!is_array($name)) {
            $name = array($name => $value);
        }
        foreach ($name as $k => $v) {
            if (!$this->_check($k, false, true, $config)) {
                continue;
            }
            if (array_key_exists($k, $this->_arrayFields)) {
                $this->arraySet($k, $v, null, $config);
            } else {
                if (in_array($k, $this->_calculated)) {
                    $this->_setCalculated($k, $v, $config);
                } else {
                    $this->_set($k, $v, $config);
                }
                if (in_array($k, $this->_linkable)) {
                    $this->_linkParent($k, $config);
                }
                $this->setInitialized($k, $config);
                if ($config['set_modified']) {
                    $this->setModified($k, $config);
                }
            }
        }
    }

    /**
     * Handler of attempt to set calculated structure field value.
     *
     * @param string $name  Structure element name to set value of
     * @param mixed $value  New value for this element
     * @param array $config Configuration options
     * @return void
     */
    protected function _setCalculated($name, $value, $config)
    {
        // This method is mean to be overridden in a case if it is useful
        // to allow setting of calculated structure field.
        // Example usage: setting of multiple structure fields by parsing value
        // that was "set" to calculated structure field
    }

    /**
     * Check if given structure field name is virtual
     *
     * @param string $name                   Structure field name to check
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function isVirtual($name, $config = null)
    {
        return (!array_key_exists($name, $this->_struct));
    }

    /**
     * Get name of structure field for given meaning
     *
     * @param string $meaning                Structure field meaning Id
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return string|null                      Structure field name or null if no structure field name is assigned for given meaning
     */
    public function getMeaningField($meaning, $config = null)
    {
        if (array_key_exists($meaning, $this->_meanings)) {
            return ($this->_meanings[$meaning]);
        } else {
            return (null);
        }
    }

    /**
     * Get structure field value by given meaning
     *
     * @param string $meaning                   Structure field meaning Id
     * @param mixed $default                    OPTIONAL Default value to return in a case if element is not available
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param array|boolean $modify             OPTIONAL Modification for given configuration options
     *                                          Boolean values are treated as shortcut for "force_read" option
     * @return mixed
     */
    public function getByMeaning($meaning, $default = null, $config = null, $modify = null)
    {
        $name = $this->getMeaningField($meaning, $config);
        if ($name !== null) {
            return ($this->get($name, $default, $config, $modify));
        } else {
            return ($default);
        }
    }

    /**
     * Set structure field value with given meaning
     *
     * @param string $meaning                   Structure field meaning Id
     * @param mixed $value                      OPTIONAL New value for this element (only if $name is a string)
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param array|boolean $modify             OPTIONAL Modification for given configuration options
     *                                          Boolean values are treated as shortcut for "force_write" option
     * @return void
     */
    public function setByMeaning($meaning, $value = null, $config = null, $modify = null)
    {
        $name = $this->getMeaningField($meaning, $config);
        if ($name !== null) {
            $this->set($name, $value, $config, $modify);
        } else {
            trigger_error(
                'Attempt to set unavailable meaning property "' . $meaning . '" for structure "' . get_class(
                    $this
                ) . '"',
                E_USER_NOTICE
            );
        }
    }

    /**
     * Get list of initialized structure fields
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getInitialized($config = null)
    {
        return ($this->_initialized);
    }

    /**
     * Mark structure field with given name as initialized
     *
     * @param string|array $name             Structure field name(s) to mark as initialized
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function setInitialized($name, $config = null)
    {
        $config = $this->getConfig($config);
        if ($config['skip_init']) {
            return;
        }
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->_struct)) {
                if (!$this->_virtualFieldsAllowed) {
                    trigger_error(
                        'Attempt to mark unavailable structure field as initialized: ' . $name,
                        E_USER_NOTICE
                    );
                }
                continue;
            }
            if ((!in_array($name, $this->_initialized)) &&
                (!in_array($name, $this->_calculated))
            ) {
                $this->_initialized[] = $name;
            }
        }
        // If we're linked to parent structure - notify it about our initialization
        if ($this->_parent) {
            $this->_parent->setInitialized($this->_parentName);
        }
    }

    /**
     * Mark structure field with given name as not initialized
     *
     * @param string|array $name             Structure field name(s) to mark as not initialized
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    protected function setNotInitialized($name, $config = null)
    {
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->_struct)) {
                if (!$this->_virtualFieldsAllowed) {
                    trigger_error(
                        'Attempt to mark unavailable structure field as not initialized: ' . $name,
                        E_USER_NOTICE
                    );
                }
                continue;
            }
            if (in_array($name, $this->_calculated)) {
                continue;
            }
            $key = array_search($name, $this->_initialized);
            if ($key !== false) {
                unset($this->_initialized[$key]);
            }
        }
        // Clearing "initialized" status of linked structures is not supported
        // because linked structure is initialized with current structure object
    }

    /**
     * Check structure fields initialization status
     *
     * @param string|array|boolean $name        OPTIONAL Structure field name(s) to check as initialization status of
     *                                          TRUE to check if structure is initialized at all
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function isInitialized($name = true, $config = null)
    {
        if ($name === true) {
            return ((boolean)sizeof($this->_initialized));
        }
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->_struct)) {
                if (!$this->_virtualFieldsAllowed) {
                    trigger_error(
                        'Attempt to check initialization status of unavailable structure field: ' . $name,
                        E_USER_NOTICE
                    );
                }
                continue;
            }
            if (in_array($name, $this->_initialized)) {
                return (true);
            }
        }
        return (false);
    }

    /**
     * Get list of modified structure fields
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getModified($config = null)
    {
        return ($this->_modified);
    }

    /**
     * Mark structure field with given name as modified
     *
     * @param string|array $name             Structure field name(s) to mark as modified
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function setModified($name, $config = null)
    {
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->_struct)) {
                if (!$this->_virtualFieldsAllowed) {
                    trigger_error(
                        'Attempt to mark unavailable structure field as modified: ' . $name,
                        E_USER_NOTICE
                    );
                }
                continue;
            }
            if ((!in_array($name, $this->_modified)) &&
                (!in_array($name, $this->_calculated))
            ) {
                $this->_modified[] = $name;
            }
        }
        // If we're linked to parent structure - notify it about our modification
        if ($this->_parent) {
            $this->_parent->setModified($this->_parentName);
        }
    }

    /**
     * Mark structure field with given name as not modified
     *
     * @param string|array $name             Structure field name(s) to mark as not modified
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function setNotModified($name, $config = null)
    {
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->_struct)) {
                if (!$this->_virtualFieldsAllowed) {
                    trigger_error(
                        'Attempt to mark unavailable structure field as not modified: ' . $name,
                        E_USER_NOTICE
                    );
                }
                continue;
            }
            $key = array_search($name, $this->_modified);
            if ($key !== false) {
                unset($this->_modified[$key]);
            }
        }
    }

    /**
     * Check structure fields modification status
     *
     * @param string|array|true $name           OPTIONAL Structure field name(s) to check as modification status of
     *                                          true to check if structure is modified at all
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function isModified($name = true, $config = null)
    {
        if ($name === true) {
            return ((boolean)sizeof($this->_modified));
        }
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->_struct)) {
                if (!$this->_virtualFieldsAllowed) {
                    trigger_error(
                        'Attempt to check modification status of unavailable structure field: ' . $name,
                        E_USER_NOTICE
                    );
                }
                continue;
            }
            if (in_array($name, $this->_modified)) {
                return (true);
            }
        }
        return (false);
    }

    /**
     * Clear list of modified structure fields
     *
     * @param boolean $clearLinked           OPTIONAL true to clear modified status of linked structures, false to not touch them (default)
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function clearModified($clearLinked = false, $config = null)
    {
        $this->_modified = array();
        if (($clearLinked) && (sizeof($this->_linked))) {
            foreach ($this->_linked as $name) {
                $value = $this->_struct[$name];
                if ($value instanceof Rx_Struct_Model_Abstract) {
                    $value->clearModified($clearLinked, $config);
                } elseif (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $v->clearModified($clearLinked, $config);
                    }
                }
            }
        }
    }

    /**
     * Set link to parent structure object
     *
     * @param Rx_Struct_Model_Abstract|null $parent Parent structure object or null to unlink parent object
     * @param string $name                          OPTIONAL Field name of parent structure object, this structure is linked to
     * @param array|Zend_Config|null $config        OPTIONAL Configuration options to override default object's configuration
     * @return void
     * @throws Rx_Model_Exception
     */
    public function setParent($parent = null, $name = null, $config = null)
    {
        if ($parent instanceof Rx_Struct_Model_Abstract) {
            if ($name === null) {
                throw new Rx_Model_Exception('Linked structure field name should be defined');
            }
            $this->_parent = $parent;
            $this->_parentName = $name;
        } elseif ($parent === null) {
            $this->_parent = null;
            $this->_parentName = null;
        } else {
            throw new Rx_Model_Exception('Parent structure object should be instance of Rx_Struct_Model_Abstract');
        }
    }

    /**
     * Get link to parent structure object
     *
     * @return Rx_Struct_Model_Abstract|null
     */
    public function getParent()
    {
        return ($this->_parent);
    }

    /**
     * Link value of structure field with given name to this structure
     *
     * @param string $name                   Name of structure field
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return boolean                          true if field was linked, false if not
     */
    protected function _linkParent($name, $config = null)
    {
        if (!array_key_exists($name, $this->_struct)) {
            trigger_error('Attempt to link unavailable structure field: ' . $name, E_USER_NOTICE);
            return (false);
        }
        if (!in_array($name, $this->_linkable)) {
            trigger_error('Attempt to link not linkable structure field: ' . $name, E_USER_NOTICE);
            return (false);
        }
        $value = $this->_struct[$name];
        if ($value instanceof Rx_Struct_Model_Abstract) {
            $value->setParent($this, $name, $config);
            if (!in_array($name, $this->_linked)) {
                $this->_linked[] = $name;
            }
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($v instanceof Rx_Struct_Model_Abstract) {
                    $v->setParent($this, $name, $config);
                }
            }
            if (!in_array($name, $this->_linked)) {
                $this->_linked[] = $name;
            }
        }
        return (true);
    }

    /**
     * Get element from array structure field
     *
     * @param string $name                   Array structure field name
     * @param string $key                    Key of element within array structure field to get
     * @param mixed $default                 OPTIONAL Default value to return in a case if no such key is available
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param array $modify                  OPTIONAL Modification for given configuration options
     * @return mixed
     */
    public function arrayGet($name, $key, $default = null, $config = null, $modify = null)
    {
        $config = $this->getConfig($config);
        if (is_array($modify)) {
            $config = $this->modifyConfig($config, $modify);
        }
        if (!$this->_check($name, true, false, $config)) {
            return ($default);
        }
        if ($config['get_raw_value']) // We should skip custom getters but still keep logic of _arrayGet()
        {
            $result = self::_arrayGet($name, $key, $default, $config);
        } else {
            $result = $this->_arrayGet($name, $key, $default, $config);
        }
        return ($result);
    }

    /**
     * Actual implementation of retrieving element from array structure field
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name   Array structure field name
     * @param string $key    Key of element within array structure field to get
     * @param mixed $default Default value to return in a case if no such key is available
     * @param array $config  Configuration options
     * @return mixed
     */
    protected function _arrayGet($name, $key, $default, $config)
    {
        $result = $default;
        if (array_key_exists($key, $this->_struct[$name])) {
            $result = $this->_struct[$name][$key];
        }
        return ($result);
    }

    /**
     * Set element of array structure field
     *
     * @param string $name                   Array structure field name
     * @param string|array $key              Either name of key within array structure element to set or array of elements to set
     * @param mixed $value                   OPTIONAL Element value to set (only if $key is a string)
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param array $modify                  OPTIONAL Modification for given configuration options
     * @throws Rx_Model_Exception
     * @return void
     */
    public function arraySet($name, $key, $value = null, $config = null, $modify = null)
    {
        if ((is_object($key)) && (is_callable(array($key, 'toArray')))) {
            $key = $key->toArray();
        }
        if (($key === null) && ($value === null)) // No information for setting is given
        {
            return;
        }
        $config = $this->getConfig($config);
        if (is_array($modify)) {
            $config = $this->modifyConfig($config, $modify);
        }
        if (!$this->_check($name, true, true, $config)) {
            return;
        }
        $givenKey = false;
        if (!is_array($key)) {
            $givenKey = $key;
            $key = array($key => $value);
        }
        $class = $this->_arrayFields[$name];
        foreach ($key as $k => $v) {
            if ($class) {
                if (!$v instanceof $class) {
                    throw new Rx_Model_Exception('Value for "' . $name . '" field of "' . get_class(
                            $this
                        ) . '" structure must be instance of ' . $class);
                }
                if (($v instanceof Rx_Struct_Model_Abstract) && ($givenKey === false) && (!$config['use_given_keys'])) {
                    $id = $v->getByMeaning('array_key');
                    if ($id !== null) {
                        $k = $id;
                    }
                }
            }
            $this->_arraySet($name, $k, $v, $config);
        }
        if (in_array($name, $this->_linkable)) {
            $this->_linkParent($name, $config);
        }
        $this->setInitialized($name, $config);
        if ($config['set_modified']) {
            $this->setModified($name, $config);
        }
    }

    /**
     * Actual implementation of setting element value into array structure field
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name  Array structure field name
     * @param string $key   Key of element within array structure field to set value of
     * @param mixed $value  Element value to set
     * @param array $config Configuration options
     * @return void
     */
    protected function _arraySet($name, $key, $value, $config)
    {
        $this->_struct[$name][$key] = $value;
    }

    /**
     * Add element into array structure field
     *
     * @param string $name                   Array structure field name
     * @param mixed $value                   New element value to add
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @throws Rx_Model_Exception
     * @return void
     */
    public function arrayAdd($name, $value, $config = null)
    {
        if (!$this->_check($name, true, true, $config)) {
            return;
        }
        // Select key for new value
        $key = null;
        $class = $this->_arrayFields[$name];
        if ($class) {
            if (!$value instanceof $class) {
                throw new Rx_Model_Exception('Value for "' . $name . '" field of "' . get_class(
                        $this
                    ) . '" structure must be instance of ' . $class);
            }
            if ($value instanceof Rx_Struct_Model_Abstract) {
                $key = $value->getByMeaning('array_key');
            }
        }
        if ($key === null) {
            $keys = array_keys($this->_struct[$name]);
            $key = (sizeof($keys)) ? max($keys) + 1 : 0;
            $_cnt = 100;
            while ($_cnt--) {
                if (!array_key_exists($key, $this->_struct[$name])) {
                    break;
                }
                $key++;
            }
        }
        $this->arraySet($name, $key, $value, $config);
    }

    /**
     * Implementation of unset() for array structure field
     *
     * @param string $name                   Array structure field name
     * @param string $key                    Key of element within array structure field
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function arrayUnset($name, $key, $config = null)
    {
        $config = $this->getConfig($config);
        if (!$this->_check($name, true, true, $config)) {
            return (false);
        }
        unset($this->_struct[$name][$key]);
        if ($config['set_modified']) {
            $this->setModified($name, $config);
        }
    }

    /**
     * Clearing of array structure field
     *
     * @param string $name                   Array structure field name
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function arrayClear($name, $config = null)
    {
        $config = $this->getConfig($config);
        if (!$this->_check($name, true, true, $config)) {
            return (false);
        }
        $this->_struct[$name] = array();
        if ($config['set_modified']) {
            $this->setModified($name, $config);
        }
    }

    /**
     * Implementation of array_key_exists() for array structure field
     *
     * @param string $name                   Array structure field name
     * @param string $key                    Key of element within array structure field
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function arrayKeyExists($name, $key, $config = null)
    {
        if (!$this->_check($name, true, false, $config)) {
            return (false);
        }
        $exists = (array_key_exists($key, $this->_struct[$name]));
        return ($exists);
    }

    /**
     * Implementation of array_keys() for array structure field
     *
     * @param string $name                   Array structure field name
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function arrayKeys($name, $config = null)
    {
        if (!$this->_check($name, true, false, $config)) {
            return (false);
        }
        return (array_keys($this->_struct[$name]));
    }

    /**
     * Implementation of uasort() for array structure field
     *
     * @param string $name                   Array structure field name
     * @param callback $callback             Callback function for sorting array elements
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function arraySort($name, $callback, $config = null)
    {
        if (!$this->_check($name, true, true, $config)) {
            return (false);
        }
        if (!is_callable($callback)) {
            trigger_error('Invalid callback function', E_USER_WARNING);
            return (false);
        }
        uasort($this->_struct[$name], $callback);
        return (true);
    }

    /**
     * Implementation of uksort() for array structure field
     *
     * @param string $name                   Array structure field name
     * @param callback $callback             Callback function for sorting array elements
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function arrayKSort($name, $callback, $config = null)
    {
        if (!$this->_check($name, true, true, $config)) {
            return (false);
        }
        if (!is_callable($callback)) {
            trigger_error('Invalid callback function', E_USER_WARNING);
            return (false);
        }
        uksort($this->_struct[$name], $callback);
        return (true);
    }

    /**
     * Implementation of sizeof() for array structure field
     *
     * @param string $name                   Array structure field name
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return int
     */
    public function arraySize($name, $config = null)
    {
        if (!$this->_check($name, true, false, $config)) {
            return (0);
        }
        return (sizeof($this->_struct[$name]));
    }

    /**
     * Import complete structure contents from given structure object
     *
     * @param Rx_Struct_Model_Abstract $struct Structure object to import information from
     * @param array|Zend_Config|null $config   OPTIONAL Configuration options to override default object's configuration
     * @return boolean                              true if structure was copied, false in a case of error
     */
    public function import($struct, $config = null)
    {
        if (!$struct instanceof Rx_Struct_Abstract) {
            trigger_error('Source structure for importing should be instance of Rx_Struct_Abstract', E_USER_WARNING);
            return (false);
        }
        // Reset structure so we will be sure that we import contents into "clean" structure
        // However we should preserve link to parent structure
        $parent = $this->getParent();
        $parentName = $this->_parentName;
        $this->reset();
        if ($parent) {
            $this->setParent($parent, $parentName);
        }
        $keys = array_keys($this->_struct);
        // Force receiving raw values from foreign structure to be sure that we copy them "as is"
        $config = $this->modifyConfig(
            $config,
            array(
                'force_read'    => true,
                'force_write'   => true,
                'get_raw_value' => true,
            )
        );
        foreach ($keys as $key) {
            if (in_array($key, $this->_calculated)) {
                continue;
            }
            $value = null;
            if (isset($struct->$key)) {
                $value = $struct->get($key, null, $config);
            }
            $this->set($key, $value, $config);
        }
        return (true);
    }

    /**
     * Return structure as associative array
     *
     * @param array|Zend_Config|boolean|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function toArray($config = null)
    {
        $array = array();
        $keys = array_keys($this->_struct);
        // Save original copy of given configuration options to pass it to child structures
        // It is required to avoid losing configuration options in a case if child structures
        // have some options that are not available in parent structure object (e.g. parent
        // structure is normal model structure but it have patched child structure)
        $_config = $config;
        $config = $this->getConfig($config);
        // Do not use direct retrieving of values from structure table
        // because get() method may use additional value processing
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->_arrayFields)) {
                $array[$key] = array();
                $aKeys = $this->arrayKeys($key, $config);
                foreach ($aKeys as $aKey) {
                    $value = $this->arrayGet($key, $aKey, null, $config);
                    if ($value instanceof Rx_Struct_Abstract) {
                        $value = $value->toArray($_config);
                    }
                    $array[$key][$aKey] = $value;
                }
            } else {
                $value = $this->_get($key, null, $config);
                if ($value instanceof Rx_Struct_Abstract) {
                    $value = $value->toArray($_config);
                }
                $array[$key] = $value;
            }
        }
        return ($array);
    }

    /**
     * Check if entity model is defined for this structure
     *
     * @return boolean
     */
    public function haveEntity()
    {
        if ($this->_entity instanceof Rx_Model_Entity) {
            return (true);
        }
        if (!$this->_entityClassName) {
            return (false);
        }
        $class = Rx_ModelManager::getClass($this->_entityClassName);
        return ((boolean)$class);
    }

    /**
     * Get entity model for this structure
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options for entity object
     * @return Rx_Model_Entity
     * @throws Rx_Model_Exception
     */
    public function getEntity($config = null)
    {
        if (!$this->_entity instanceof Rx_Model_Entity) {
            if (!$this->_entityClassName) {
                throw new Rx_Model_Exception('Entity class name is not defined for structure class ' . get_class(
                        $this
                    ));
            }
            $entity = Rx_ModelManager::get($this->_entityClassName, $this, $config);
            if (!$entity instanceof Rx_Model_Entity) {
                throw new Rx_Model_Exception('Failed to get entity class "' . $this->_entityClassName . '"');
            }
            $this->_entity = $entity;
        }
        return ($this->_entity);
    }

    /**
     * Save structure contents in database
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options for entity object
     * @param string $partId                 OPTIONAL Structure part Id to save (whole structure will be saved by default)
     * @return boolean                          true if structure was successfully saved, false in a case of error
     * @throws Rx_Model_Exception
     */
    public function save($config = null, $partId = null)
    {
        return ($this->getEntity($config)->save($config, $partId));
    }

    /**
     * Implementation of Serializable interface
     *
     * @return string
     */
    public function serialize()
    {
        $data = array(
            'struct' => array(),
            'linked' => $this->_linked,
        );
        foreach ($this->_struct as $name => $value) {
            if ((in_array($name, $this->_calculated)) ||
                (in_array($name, $this->_transient))
            ) {
                continue;
            }
            $data['struct'][$name] = $value;
        }
        return (serialize($data));
    }

    /**
     * Implementation of Serializable interface
     *
     * @param array $data Serialized object data
     * @return void
     */
    public function unserialize($data)
    {
        $this->reset();
        if (!is_array($data)) {
            $data = @unserialize($data);
        }
        if (!is_array($data)) {
            return;
        }
        $struct = ((isset($data['struct'])) && (is_array($data['struct']))) ? $data['struct'] : array();
        foreach ($struct as $name => $value) {
            if (array_key_exists($name, $this->_struct)) {
                $this->_struct[$name] = $value;
            }
        }
        $linked = ((isset($data['linked'])) && (is_array($data['linked']))) ? $data['linked'] : array();
        foreach ($linked as $name) {
            $this->_linkParent($name);
        }
        foreach ($this->_arrayFields as $name => $class) {
            if (!is_array($this->_struct[$name])) {
                $this->_struct[$name] = array();
            }
        }
    }

    /**
     * Reset class member variables before re-initializing them
     *
     * @return void
     */
    protected function _reset()
    {
        parent::_reset();
        $this->initMeanings();
        // Initialize special flags for structure fields
        $this->_readOnly = $this->_getFlagFields($this->_getReadOnlyFields(), 'read-only');
        $this->_writeOnly = $this->_getFlagFields($this->_getWriteOnlyFields(), 'write-only');
        $this->_calculated = $this->_getFlagFields($this->_getCalculatedFields(), 'calculated');
        $this->_linkable = $this->_getFlagFields($this->_getLinkableFields(), 'linkable');
        $this->_arrayFields = $this->_getFlagFields($this->_getArrayFields(), 'array', true);
        $this->_transient = $this->_getFlagFields($this->_getTransientFields(), 'transient');
        $this->setParent();
        $this->clearModified(true);
        $this->_linked = array();
        foreach ($this->_arrayFields as $name => $class) {
            if (!is_array($this->_struct[$name])) {
                $this->_struct[$name] = array();
            }
        }
    }

    /**
     * Support unset() overloading on PHP 5.1
     * Unsetting field name in a term of removing it from structure is not allowed,
     * so unset() just wipes field's value.
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_arrayFields)) {
            $this->arrayClear($name);
        } else {
            $this->set($name, null, null, true);
        }
    }

    /**
     * Initialize list of structure fields that should obtain special flag
     *
     * @param array|string|boolean $fields List of fields as received from initialization methods
     * @param string $type                 Flag type
     * @param boolean $assoc               OPTIONAL true if list of fields may be passed as associative array
     * @return array
     */
    protected function _getFlagFields($fields, $type, $assoc = false)
    {
        $result = array();
        if ($fields === true) {
            $fields = array_keys($this->_struct);
        } elseif ($fields === false) {
            $fields = array();
        } elseif (!is_array($fields)) {
            $fields = array($fields);
        }
        foreach ($fields as $field => $value) {
            if ((!$assoc) ||
                (($assoc) && (is_numeric($field)) && ($value))
            ) {
                $field = $value;
                $value = null;
            }
            if (!$field) {
                continue;
            }
            if (!array_key_exists($field, $this->_struct)) {
                trigger_error(
                    'Attempt to set special flag "' . $type . '" to unavailable structure field: ' . $field,
                    E_USER_NOTICE
                );
                continue;
            }
            if ($assoc) {
                $result[$field] = $value;
            } elseif (!in_array($field, $result)) {
                $result[] = $field;
            }
        }
        return ($result);
    }

    /**
     * Initialize meanings of certain structure fields
     * Passing meanings as argument is useful for inheritance
     *
     * @param array $meanings OPTIONAL Meanings to add (in a form "meaning Id"=>"structure field name")
     * @return array
     */
    protected function initMeanings($meanings = array())
    {
        if (!is_array($meanings)) {
            $meanings = array();
        }
        // Define meanings that are used by Rx_Model_Entity and Rx_Model_Collection
        $meanings = array_merge(array(
            'id'          => 'id', // Item identifier (not obligated to be database Id)
            'db_id'       => 'id', // Item identifier in database
            'uid'         => 'uid', // Public UID
            'active'      => 'active', // Item activity flag (boolean)
            'added_at'    => 'added_at', // Date of item creation
            'modified_at' => 'modified_at', // Date of last item modification
            'array_key'   => 'id', // Key for array structure field entry
        ), $meanings);
        foreach ($meanings as $meaning => $name) {
            if (!isset($this->$name)) {
                continue;
            }
            $this->_meanings[$meaning] = $name;
        }
    }

    /**
     * Get list of names of structure fields that should be marked as read-only
     * These fields will be writable only during object construction
     * or by directly passing "constructor" option in config
     *
     * @return array|string
     */
    protected function _getReadOnlyFields()
    {
        // By default protect database Id, public UID and item creation date
        return (array(
            $this->getMeaningField('db_id'),
            $this->getMeaningField('uid'),
            $this->getMeaningField('added_at'),
        ));
    }

    /**
     * Get list of names of structure fields that should be marked as write-only
     * These fields will not be readable in any case but it will be possible to write to them
     *
     * @return array|string
     */
    protected function _getWriteOnlyFields()
    {
        return (array());
    }

    /**
     * Get list of calculated structure fields
     * These fields will not be writable in any case
     *
     * @return array|string
     */
    protected function _getCalculatedFields()
    {
        return (array());
    }

    /**
     * Get list of linkable structure fields
     * Structures in these fields will be linked with main structure
     *
     * @return array|string
     */
    protected function _getLinkableFields()
    {
        return (array());
    }

    /**
     * Get list of array structure fields
     * These fields will be allowed for array operations
     * If returned value is array - it can be defined in a form:
     * "structure field name" => "class name"
     * In this case values stored in array field will be checked for valid class name
     * and their "id" values will be used as array keys
     *
     * @return array|string
     */
    protected function _getArrayFields()
    {
        return (array());
    }

    /**
     * Get list of transient structure fields
     * These fields will not be saved during object serialization
     *
     * @return array|string
     */
    protected function _getTransientFields()
    {
        return (array());
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'constructor'    => false, // true to run modification into "class constructor mode":
            // "force_write" + not "set_modified"
            'force_read'     => false, // true to force reading write-only properties
            'force_write'    => false, // true if structure is being constructed from information taken from database
            // false for normal structure modification
            'get_raw_value'  => false, // true to get raw structure field value (do not call custom getters)
            'set_modified'   => true, // true to set field as modified when setting field value
            // false to skip setting field modification status on setting field value
            'use_given_keys' => false, // true to use given array keys when setting array of items into array structure field that accepts list of structures
            // false to get array keys from structures being set
            'skip_init'      => false, // true to skip initialization of uninitialized structure fields
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
            case 'constructor':
            case 'force_read':
            case 'force_write':
            case 'get_raw_value':
            case 'set_modified':
            case 'use_given_keys':
            case 'skip_init':
                $value = (boolean)$value;
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
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
        $config = parent::getConfig($config);
        if ($config['constructor']) {
            $config = $this->modifyConfig(
                $config,
                array(
                    'force_write'  => true,
                    'set_modified' => false,
                )
            );
        }
        return ($config);
    }

}
