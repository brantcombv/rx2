<?php

abstract class Rx_Struct_Model_Patched extends Rx_Struct_Model_Abstract
{

    /**
     * List of structure fields that can be patched
     *
     * @var array $_patchFields
     */
    protected $_patchFields = array();
    /**
     * Patch for original structure
     *
     * @var array $_patch
     */
    protected $_patch = array();
    /**
     * List of modified patch fields
     *
     * @var array $_patchModified
     */
    protected $_patchModified = array();

    /**
     * Class constructor
     *
     * @param array $struct             OPTIONAL Structure fields to set on class creation
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     */
    public function __construct($struct = null, $config = null)
    {
        // Don't pass given structure to parent constructor because of calling to set()
        // which must not affect $_patch at this stage since all structure contents
        // given to constructor must go to $_struct.
        parent::__construct(null, $config);
        if ($struct !== null) {
            $this->set($struct, null, $this->getConfig(array(
                'constructor' => true,
                'use_patch'   => false,
            )));
        }
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
        // This version of get() is actually a copy of corresponding method from parent class.
        // The only reason to have separate copy of it - is correct handling of "get_raw_value"
        // which refers to "self" and hence must reside in same class as it was called from
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
     * Actual implementation of structure field retrieving by name
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name   Structure element name to get value of
     * @param mixed $default Default value to return in a case if element is not available
     * @param array $config  Configuration options
     * @return mixed
     */
    protected function _get($name, $default, $config)
    {
        $result = $default;
        if (array_key_exists($name, $this->_struct)) {
            if ($this->_isPatch($config, true, $name)) {
                if (array_key_exists($name, $this->_patch)) {
                    $result = $this->_patch[$name];
                } elseif (!$config['no_fallback']) {
                    $result = $this->_struct[$name];
                }
            } else {
                $result = $this->_struct[$name];
            }
        }
        return ($result);
    }

    /**
     * Actual implementation of setting structure field value.
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name  Structure element name to set value of
     * @param mixed $value  New value for this element
     * @param array $config Configuration options
     * @return void
     */
    protected function _set($name, $value, $config)
    {
        if (array_key_exists($name, $this->_struct)) {
            if ($this->_isPatch($config, false, $name)) {
                $this->_patch[$name] = $value;
            } else {
                $this->_struct[$name] = $value;
            }
        }
    }

    /**
     * Get contents of patch
     *
     * @param array|Zend_Config|boolean|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getPatch($config = null)
    {
        $array = array();
        $config = $this->modifyConfig($config, 'use_patch', true);
        // Do not use direct retrieving of values from structure table
        // because get() method may use additional value processing
        foreach ($this->_patchFields as $key) {
            // We should only return patch fields that are actually available,
            // otherwise it will not be possible to store valid patch information
            // in database.
            if (!array_key_exists($key, $this->_patch)) {
                continue;
            }
            $array[$key] = $this->_get($key, null, $config);
        }
        return ($array);
    }

    /**
     * Support unset() overloading on PHP 5.1
     * Unsetting field is mean to remove its patched value
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (in_array($name, $this->_arrayFields)) {
            $this->arrayClear($name);
        } else {
            if (in_array($name, $this->_patchFields)) {
                unset($this->_patch[$name]);
                $this->setModified($name, true);
            }
            $this->set(
                $name,
                null,
                array(
                    'use_patch'   => false,
                    'force_write' => true,
                )
            );
        }
    }

    /**
     * Get list of modified structure fields
     *
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return array
     */
    public function getModified($config = null)
    {
        $config = $this->getConfig($config);
        if ($this->_isPatch($config, true)) {
            return ($this->_patchModified);
        } else {
            return (parent::getModified($config));
        }
    }

    /**
     * Mark structure field with given name as modified
     *
     * @param string|array $name             Structure field name(s) to mark as modified
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return void
     */
    public function setModified($name, $config = null)
    {
        $config = $this->getConfig($config);
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (($this->_isPatch($config, false, $name)) && (!in_array($name, $this->_patchModified))) {
                $this->_patchModified[] = $name;
                // If we're linked to parent structure - notify it about our modification
                if ($this->_parent) {
                    $this->_parent->setModified($this->_parentName);
                }
            } else {
                parent::setModified($name, $config);
            }
        }
    }

    /**
     * Mark structure field with given name as not modified
     *
     * @param string|array $name             Structure field name(s) to mark as not modified
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return void
     */
    public function setNotModified($name, $config = null)
    {
        $config = $this->getConfig($config);
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (($this->_isPatch($config, false, $name)) && (in_array($name, $this->_patchModified))) {
                $key = array_search($name, $this->_patchModified);
                if ($key !== false) {
                    unset($this->_patchModified[$key]);
                }
            } else {
                parent::setNotModified($name, $config);
            }
        }
    }

    /**
     * Check structure fields modification status
     *
     * @param array|bool|string|true $name      OPTIONAL Structure field name(s) to check as modification status of
     *                                          true to check if structure is modified at all
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @return boolean
     */
    public function isModified($name = true, $config = null)
    {
        $config = $this->getConfig($config);
        if (!$this->_isPatch($config, true)) {
            return (parent::isModified($name, $config));
        }
        $names = (is_array($name)) ? $name : array($name);
        foreach ($names as $name) {
            if (in_array($name, $this->_patchModified)) {
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
        // NOTICE: Patching for linked structure fields is not supported
        $config = $this->getConfig($config);
        if ($this->_isPatch($config, false)) {
            $this->_patchModified = array();
        }
        parent::clearModified($clearLinked, $config);
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
        $config = $this->modifyConfig($config, 'use_patch', false);
        if (!parent::import($struct, $config)) {
            return (false);
        }
        // Tune configuration so we will get raw patch values from source structure
        $config = $this->modifyConfig(
            $config,
            array(
                'force_read'    => true,
                'force_write'   => true,
                'get_raw_value' => true,
                'use_patch'     => true,
                'no_fallback'   => true,
            )
        );
        foreach ($this->_patchFields as $key) {
            $value = null;
            if (isset($struct->$key)) {
                $value = $struct->get($key, null, $config);
            }
            $this->set($key, $value, $config);
        }
        return (true);
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
            'patch'  => array(),
            'linked' => $this->_linked,
        );
        foreach ($this->_struct as $name => $value) {
            if (in_array($name, $this->_calculated)) {
                continue;
            }
            $data['struct'][$name] = $value;
        }
        foreach ($this->_patch as $name => $value) {
            if (in_array($name, $this->_calculated)) {
                continue;
            }
            $data['patch'][$name] = $value;
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
        $data = @unserialize($data);
        if (!is_array($data)) {
            return;
        }
        parent::unserialize($data);
        $patch = ((isset($data['patch'])) && (is_array($data['patch']))) ? $data['patch'] : array();
        foreach ($patch as $name => $value) {
            if (in_array($name, $this->_patchFields)) {
                $this->_patch[$name] = $value;
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
        $this->_patchFields = $this->_getFlagFields($this->_getPatchFields(), 'patch');
        $this->_patch = array();
        $this->_patchModified = array();
    }

    /**
     * Determine if patch or main fields should be used for operation
     *
     * @param array $config Configuration options
     * @param boolean $get  true for "get" operation, false for "set" operation
     * @param string $name  OPTIONAL Field name that will be used for operation
     * @return boolean
     */
    protected function _isPatch($config, $get, $name = null)
    {
        if ($get) {
            $isPatch = ($config['use_patch'] !== null) ? $config['use_patch'] : $config['default_get_patched'];
        } else {
            $isPatch = ($config['use_patch'] !== null) ? $config['use_patch'] : $config['default_set_patched'];
        }
        if ($name !== null) {
            $isPatch = (($isPatch) && (in_array($name, $this->_patchFields)));
        }
        return ($isPatch);
    }

    /**
     * Provide list of structure fields that can be patched
     *
     * @return array|string|boolean     Array of fields, true to allow patching all fields, false to disable patching
     */
    protected function _getPatchFields()
    {
        // IMPORTANT! List of patched fields should NOT contain fields that
        // should have some special flags (e.g. linked, calculated read/write-only fields)
        // because this functionality is not supported for patched fields
        return (false);
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
        // Add meaning for patch information database Id
        return (parent::initMeanings(array_merge($meanings, array(
            'patch_db_id' => '_patch_id',
        ))));
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
        // Protect patch database Id from modification
        return (array_merge(
            parent::_getReadOnlyFields(),
            array($this->getMeaningField('patch_db_id'))
        ));
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'use_patch'           => null, // true to perform operation on patch structure,
            // false to perform on original structure
            'no_fallback'         => false, // true to skip fetching value from original structure field
            // in a case if requested field have no patch value. Default value will be returned in this case
            'default_set_patched' => true, // true if set() and unset() operations should be applied to patch structure by default,
            // false if they should be applied to original structure by default
            'default_get_patched' => true, // true if get() operation should be applied to patch structure by default,
            // false if it should be applied to original structure by default
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
            case 'use_patch':
            case 'no_fallback':
            case 'default_set_patched':
            case 'default_get_patched':
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
     * @param string|array|Zend_Config|boolean|null $config OPTIONAL Option name to get or configuration options
     *                                                      to override default object's configuration.
     *                                                      Boolean values are used as shortcuts for "use_patch" configuration option
     * @return mixed
     */
    public function getConfig($config = null)
    {
        if (($config === true) || ($config === false)) {
            $config = array('use_patch' => $config);
        }
        return (parent::getConfig($config));
    }

}
