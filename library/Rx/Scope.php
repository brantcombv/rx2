<?php

class Rx_Scope
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Scope $_instance
     */
    protected static $_instance = null;
    /**
     * Scope information provider
     *
     * @var Rx_Scope_Provider_Abstract $_provider
     */
    protected $_provider = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Scope
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Register scope information provider
     *
     * @param Rx_Scope_Provider_Abstract $provider Scope information provider object
     * @return void
     * @throws Rx_Scope_Exception
     */
    public static function registerProvider($provider)
    {
        if (!$provider instanceof Rx_Scope_Provider_Abstract) {
            throw new Rx_Scope_Exception('Scope information provider object must be instance of Rx_Scope_Provider_Abstract');
        }
        $instance = self::getInstance();
        $instance->_provider = $provider;
    }

    /**
     * Get scope information provider object
     *
     * @return Rx_Scope_Provider_Abstract
     * @throws Rx_Scope_Exception
     */
    public static function getProvider()
    {
        $instance = self::getInstance();
        if (!$instance->_provider) {
            throw new Rx_Scope_Exception('No scope information provider is registered');
        }
        return ($instance->_provider);
    }

    /**
     * Get scope limitation value for given Id
     *
     * @param string $id        Scope identifier to get
     * @param mixed $value      Scope values that are planned to use for scope limitation (optional)
     * @param boolean $array    true to force returning value as array,
     *                          false to force to return it as scalar value,
     *                          null to return single value as scalar and multiple as array (default)
     * @return mixed
     * @throws Rx_Scope_Exception
     */
    public static function getScope($id, $value = null, $array = null)
    {
        $scope = self::getProvider()->getScope($id);
        if (!is_array($scope)) {
            $scope = array($scope);
        }
        if ($value !== null) {
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $k => $v) {
                if (!in_array($v, $scope)) {
                    unset($value[$k]);
                }
            }
        } else {
            $value = $scope;
        }
        if ($array === true) {
            if (!is_array($value)) {
                $value = array($value);
            }
        } elseif ($array === false) {
            if (is_array($value)) {
                $value = array_shift($value);
            }
        } else {
            if ((is_array($value)) && (sizeof($value) < 2)) {
                $value = array_shift($value);
            }
        }
        return ($value);
    }

    /**
     * Set new scope limitation value
     *
     * @param string $id   Scope identifier to set
     * @param mixed $value New value for scope limitation
     * @return boolean          true if scope variable was set successfully, false in a case of error
     * @throws Rx_Scope_Exception
     */
    public static function setScope($id, $value)
    {
        return (self::getProvider()->setScope($id, $value));
    }

    /**
     * Get name of scope limitation Id that is being changed at this moment
     *
     * @return string|null
     */
    public static function getChangingScope()
    {
        return (self::getProvider()->getChangingScope());
    }

}
