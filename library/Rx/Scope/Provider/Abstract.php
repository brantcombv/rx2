<?php

abstract class Rx_Scope_Provider_Abstract implements Rx_Notify_Observer
{

    /**
     * List of known scope limitation Ids and their dependencies
     *
     * @var array $_ids
     */
    protected $_ids = array();
    /**
     * Reverted dependencies tree for scope limitation Ids
     *
     * @var array $_revdep
     */
    protected $_revdep = array();
    /**
     * Cached scope limitation values
     *
     * @var array $_scopes
     */
    protected $_scopes = array();
    /**
     * Name of scope limitation Id that is being changed at this moment
     *
     * @var array $_changingScope
     */
    protected $_changingScope = array();
    /**
     * true if scoping is being disabled at this moment, false otherwise
     *
     * @var boolean $_scopingDisabled
     * @see getScope()
     */
    protected $_scopingDisabled = false;
    /**
     * true if notification event is being processed, false if not
     *
     * @var boolean $_handlingNotify
     */
    protected $_handlingNotify = false;
    /**
     * true when performing trusted setting of scope limitation variable, false otherwise
     *
     * @var boolean $_trustedSet
     */
    protected $_trustedSet = false;

    /**
     * Class constructor
     */
    public function __construct()
    {
        // Initialize scope limitation variables
        $this->_ids = array();
        $this->_revdep = array();
        $ids = $this->_getIds();
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id) {
            $dependency = $this->_getDependency($id);
            if (!$dependency) {
                $dependency = null;
            } elseif (!is_array($dependency)) {
                $dependency = array($dependency);
            }
            if (is_array($dependency)) {
                foreach ($dependency as $k => $v) {
                    if (!array_key_exists($v, $this->_ids)) {
                        unset($dependency[$k]);
                    }
                    if (!array_key_exists($v, $this->_revdep)) {
                        $this->_revdep[$v] = array();
                    }
                    if (!in_array($id, $this->_revdep[$v])) {
                        $this->_revdep[$v][] = $id;
                    }
                }
            }
            $this->_ids[$id] = $dependency;
        }
        // Attempt to get current scope limitation values from application state
        $this->_scopes = array();
        $scopes = Rx_AppState::get($this, array());
        if (!is_array($scopes)) {
            $scopes = array();
        }
        foreach ($scopes as $id => $value) {
            if (!array_key_exists($id, $this->_ids)) {
                continue;
            }
            $this->_scopes[$id] = $value;
        }
        $this->_changingScope = array();
        $this->_scopingDisabled = false;
        $this->_handlingNotify = false;
        $this->_trustedSet = false;
    }

    /**
     * Get scope limitation value by given identifier
     *
     * @param string $id Scope limitation value to get
     * @return array
     */
    public function getScope($id)
    {
        // If scope value is now being validated before setting - we should disable scoping
        // to avoid infinite loop if scope validation will require accessing application models
        if (($this->isScopingDisabled()) ||
            ($this->isChangingScope($id))
        ) {
            return (array(true));
        }
        if (!array_key_exists($id, $this->_ids)) {
            return ($this->_handleAccessToUnknownScopeVariable($id, false));
        }
        // If scope variable is already defined - simply return its value
        if ($this->isDefined($id)) {
            return ($this->_getScope($id));
        }
        // Scope variable is not yet defined - we should initialize its value
        // To be able to do this we need to resolve all dependencies of this variable
        $dependencies = $this->_ids[$id];
        if (is_array($dependencies)) {
            // Initialization is performed by attempting to get scope variable value
            // so it will be able to initialize its own value and own dependencies in its turn
            foreach ($dependencies as $depId) {
                $this->getScope($depId);
            }
        }
        // Receive scope limitation value from custom getter
        $this->setChangingScope($id);
        $value = $this->_initScopeVar($id);
        $this->_setScope($id, $value);
        $this->setChangingScope();
        return ($value);
    }

    /**
     * Set new scope limitation value for given identifier
     *
     * @param string $id   Scope limitation value identifier
     * @param mixed $value New value for scope limitation
     * @return boolean      true if scope variable was changed successfully, false in a case of error
     */
    public function setScope($id, $value)
    {
        // Avoid recursive changes of same scope limitation value
        if (($this->isScopingDisabled()) ||
            ($this->isChangingScope($id))
        ) {
            return (true);
        }
        if (!array_key_exists($id, $this->_ids)) {
            return ($this->_handleAccessToUnknownScopeVariable($id, true, $value));
        }
        $this->setChangingScope($id);
        // Disable scope variable validation if we're using trusted setter
        $valid = ($this->_trustedSet) ? true : $this->_validateScopeVar($id, $value);
        if (!$valid) {
            trigger_error(
                'Invalid value for scope limitation variable "' . $id . '" or variable can\'t be set directly',
                E_USER_WARNING
            );
            $this->setChangingScope();
            return (false);
        }
        // Store received scope value
        $this->_setScope($id, $value);
        $this->setChangingScope();
        return (true);
    }

    /**
     * Trusted setting of scope limitation variable
     *
     * @param string $id   Scope limitation value identifier
     * @param mixed $value New value for scope limitation
     * @return boolean      true if scope variable was changed successfully, false in a case of error
     */
    protected final function trustedSet($id, $value)
    {
        // This method is mean to be used within scope provider (mainly into notifications handler)
        // to avoid unnecessary validation of scope variables that are known to came
        // from trusted sources (e.g. from notification events with verified senders)
        // Main purpose is to:
        // - avoid possible recursive calls
        // - save time
        $this->_trustedSet = true;
        $result = $this->setScope($id, $value);
        $this->_trustedSet = false;
        return ($result);
    }

    /**
     * Handle given notification event
     *
     * @param Rx_Notify_Event $event Notification event object
     * @return void
     */
    public final function handleNotify($event)
    {
        // Actual events notification is moved into separate method
        // because we need to know if we're in process of handling event
        // to avoid possible infinite loop when scope variable initialization
        // or validation causes same notification event to be fired again
        // e.g. if scope variable value is came from current value of Rx_Model_Collection
        // and variable validation involves same model
        $this->_handlingNotify = true;
        $this->_handleNotify($event);
        $this->_handlingNotify = false;
    }

    /**
     * Actual implementation of handling notification events
     *
     * @param Rx_Notify_Event $event Notification event object
     * @return void
     */
    protected function _handleNotify($event)
    {
        // This function should be overridden if some scope variables changes
        // are came as notification events
    }

    /**
     * Get name of scope limitation Id that is being changed at this moment
     *
     * @return string|null
     */
    public function getChangingScope()
    {
        reset($this->_changingScope);
        $id = current($this->_changingScope);
        if (!$id) {
            $id = null;
        }
        return ($id);
    }

    /**
     * Set name of scope limitation Id that is being changed at this moment
     *
     * @param string $id OPTIONAL Scope limitation identifier
     * @return void
     */
    protected function setChangingScope($id = null)
    {
        $id = (array_key_exists($id, $this->_ids)) ? $id : null;
        if ($id) {
            array_unshift($this->_changingScope, $id);
        } else {
            array_shift($this->_changingScope);
        }
    }

    /**
     * Check if scope limitation variable with given Id is being changed at this moment
     *
     * @param string $id Scope limitation identifier
     * @return boolean
     */
    public function isChangingScope($id)
    {
        return (in_array($id, $this->_changingScope));
    }

    /**
     * Check if scoping if disabled at this moment
     *
     * @return boolean
     */
    public function isScopingDisabled()
    {
        return ($this->_scopingDisabled);
    }

    /**
     * Set status of "scoping disabled" indicator
     *
     * @param boolean $status New status
     * @return void
     */
    public function setDisableScoping($status)
    {
        $this->_scopingDisabled = (boolean)$status;
    }

    /**
     * Check if scope limitation with given Id is defined
     *
     * @param string $id Scope limitation identifier
     * @return boolean
     */
    protected function isDefined($id)
    {
        return (array_key_exists($id, $this->_scopes));
    }

    /**
     * Actual implementation of getting scope limitation value
     *
     * @param string $id Scope limitation identifier
     * @return mixed
     */
    protected function _getScope($id)
    {
        return (array_key_exists($id, $this->_scopes) ? $this->_scopes[$id] : null);
    }

    /**
     * Actual implementation of setting scope limitation value
     *
     * @param string $id   Scope limitation identifier
     * @param mixed $value Scope limitation value
     * @return void
     * @event rx_scope_changed
     */
    protected function _setScope($id, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        $this->_scopes[$id] = $value;
        // Wipe all scope values for dependent ids because they're became invalid after setting current value
        if (array_key_exists($id, $this->_revdep)) {
            foreach ($this->_revdep[$id] as $_id) {
                unset($this->_scopes[$_id]);
            }
        }
        // Save new state of scopes in application state to preserve it between requests
        Rx_AppState::set($this, $this->_scopes, true);
        // Notify application about scope change
        // No value is sent outside within event - it would be better if scope is always received from getScope()
        Rx_Notify::notify(
            'rx_scope_changed',
            array(
                'id' => $id,
            ),
            Rx_Scope::getInstance()
        );
    }

    /**
     * Handle access to unknown scope variable with given Id
     *
     * @param string $id   Scope limitation identifier
     * @param boolean $set true for attempt to set, false to attempt to get variable
     * @param mixed $value Scope limitation value that was attempted to set
     * @return mixed            Value that should be returned as response
     */
    protected function _handleAccessToUnknownScopeVariable($id, $set, $value = null)
    {
        if ($set) {
            trigger_error('Attempt to set unknown scope limitation variable: ' . $id, E_USER_WARNING);
            return (false);
        } else {
            trigger_error('Attempt to get unknown scope limitation variable: ' . $id, E_USER_WARNING);
            return (array(false));
        }
    }

    /**
     * Initialize value of scope limitation value with given identifier
     *
     * @param string $id Scope limitation identifier
     * @return mixed
     */
    protected function _initScopeVar($id)
    {
        // This method is mean to be overridden to provide scope variable value
        // in a case if it was accessed before manual initialization
        return (true);
    }

    /**
     * Validate given scope limitation value for given identifier
     *
     * @param string $id   Scope limitation value identifier
     * @param mixed $value Scope limitation value to validate (passed by reference, so can be changed)
     * @return mixed        true if value can be stored, false to disable storing given value
     */
    protected function _validateScopeVar($id, &$value)
    {
        // This method is mean to be overridden in a case if some special logic
        // for scope limitation value should be applied
        return (true);
    }

    /**
     * Get list of available scope limitation identifiers
     *
     * @return array
     */
    protected function _getIds()
    {
        // This method should be overridden to provide list of valid Ids
        // of scope limitation variables known by application
        return (array());
    }

    /**
     * Get list of scope limitation Ids, given Id depends on
     *
     * @param string $id
     * @return array|string|null
     */
    protected function _getDependency($id)
    {
        // This method is mean to be overridden in a case if some scope limitation variable
        // depends on another scope variables and hence requires them to be initialized before
        // its own value will be possible to initialize
        return (null);
    }

}
