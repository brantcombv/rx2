<?php

class Rx_User
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_User $_instance
     */
    protected static $_instance = null;
    /**
     * User information provider
     *
     * @var Rx_User_Provider_Abstract $_provider
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
     * @return Rx_User
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Register user information provider
     *
     * @param Rx_User_Provider_Abstract $provider User information provider object
     * @return void
     * @throws Rx_User_Exception
     */
    public static function registerProvider($provider)
    {
        if (!$provider instanceof Rx_User_Provider_Abstract) {
            throw new Rx_User_Exception('User information provider object must be instance of Rx_User_Provider_Abstract');
        }
        $instance = self::getInstance();
        $instance->_provider = $provider;
    }

    /**
     * Get user information provider object
     *
     * @return Rx_User_Provider_Abstract
     * @throws Rx_User_Exception
     */
    public static function getProvider()
    {
        $instance = self::getInstance();
        if (!$instance->_provider) {
            throw new Rx_User_Exception('No user information provider is registered');
        }
        return ($instance->_provider);
    }

    /**
     * Get adapter for Zend_Auth to perform authentication through Rx_User provider
     *
     * @return Zend_Auth_Adapter_Interface
     */
    public static function getAuthAdapter()
    {
        return (self::getProvider()->getAuthAdapter());
    }

    /**
     * Get storage adapter for Zend_Auth
     *
     * @return Zend_Auth_Storage_Interface
     */
    public static function getAuthStorage()
    {
        return (self::getProvider()->getAuthStorage());
    }

    /**
     * Check if current user is exists
     *
     * @return boolean
     */
    public static function isExists()
    {
        return (self::getProvider()->isUserExists());
    }

    /**
     * Get current user profile information
     *
     * @return Rx_User_Profile
     */
    public static function getProfile()
    {
        return (self::getProvider()->getProfile());
    }

    /**
     * Get Id of current user
     *
     * @return int|null
     */
    public static function getId()
    {
        return (self::getProvider()->getProfile()->id);
    }

    /**
     * Check if current user is active
     *
     * @return boolean
     */
    public static function isActive()
    {
        return (self::getProvider()->getProfile()->active);
    }

    /**
     * Get role of current user
     *
     * @return string|null
     */
    public static function getRole()
    {
        return (self::getProvider()->getProfile()->role);
    }

    /**
     * Switch current user
     *
     * @param int $id Id of new current user
     * @return boolean      true if user was switched, false in a case of error
     */
    public static function switchUser($id)
    {
        $provider = self::getProvider();
        if (!$provider->switchUser($id)) {
            return (false);
        }
        return (true);
    }

    /**
     * Return true if user switching process is running, false otherwise
     *
     * @return boolean
     */
    public static function isSwitchingUser()
    {
        return (self::getProvider()->isSwitchingUser());
    }

    /**
     * Perform user login by given auth information
     *
     * @param mixed $login,... Login information passed as arbitrary number of arguments
     * @return boolean          true if user was logged in, false in a case of error
     */
    public static function login($login)
    {
        $provider = self::getProvider();
        $args = func_get_args();
        if (!call_user_func_array(array($provider, 'login'), $args)) {
            return (false);
        }
        return (true);
    }

    /**
     * Perform user logout
     *
     * @return void
     */
    public static function logout()
    {
        self::getProvider()->logout();
    }

}
