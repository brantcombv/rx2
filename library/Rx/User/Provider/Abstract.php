<?php

abstract class Rx_User_Provider_Abstract
{

    /**
     * User information profile
     *
     * @var Rx_User_Profile $_profile
     */
    protected $_profile = null;
    /**
     * Class that represents user information profile in application
     *
     * @var string $_profileClass
     */
    protected $_profileClass = 'Rx_User_Profile';
    /**
     * true if user can be changed directly by calling Rx_User::switchUser(),
     * false if direct user changing is not allowed
     *
     * @return boolean $_directUserChangingAllowed
     */
    protected $_directUserChangingAllowed = false;
    /**
     * true if user can be changed by login process
     * false if user login is not allowed
     *
     * @return boolean $_userLoginAllowed
     */
    protected $_userLoginAllowed = false;
    /**
     * true if user Id is required for application,
     * false if application can operate without user Id
     *
     * @var boolean $_userRequired
     */
    protected $_userRequired = false;
    /**
     * true to clear application state upon any kind of user switching
     * (to avoid potential information leaking)
     * false to skip application state clearing
     *
     * @var boolean $_clearAppStateOnUserSwitch
     */
    protected $_clearAppStateOnUserSwitch = true;
    /**
     * true if user profile information is being initialized, false otherwise
     *
     * @var boolean $_initializingUser
     */
    protected $_initializingUser = false;
    /**
     * true if user switching process is running, false otherwise
     *
     * @var boolean $_switchingUser
     */
    protected $_switchingUser = false;
    /**
     * true if user logging process is running, false otherwise
     *
     * @var boolean $_loginUser
     */
    protected $_loginUser = false;
    /**
     * Last error message
     *
     * @var string
     */
    protected $_error = null;
    /**
     * Zend_Auth adapter instance
     *
     * @var Zend_Auth_Adapter_Interface $_authAdapter
     */
    protected $_authAdapter = null;
    /**
     * Zend_Auth storage instance
     *
     * @var Zend_Auth_Storage_Interface $_authStorage
     */
    protected $_authStorage = null;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_initializingUser = false;
        $this->_switchingUser = false;
        $this->_loginUser = false;
    }

    /**
     * Get user profile information structure
     *
     * @return Rx_User_Profile
     * @throws Rx_User_Exception
     */
    protected function _getProfileStruct()
    {
        static $profile = null;

        if (!$profile) {
            $class = $this->_profileClass;
            if (!class_exists($class, true)) {
                throw new Rx_User_Exception('User profile class "' . $class . '" is not available');
            }
            $profile = new $class();
            if (!$profile instanceof Rx_User_Profile) {
                throw new Rx_User_Exception('User profile class "' . $class . '" must be instance of Rx_User_Profile');
            }
        }
        return (clone($profile));
    }

    /**
     * Get user information profile
     *
     * @return Rx_User_Profile
     * @throws Rx_User_Exception
     */
    public function getProfile()
    {
        if (!$this->_profile) {
            $profile = $this->_getProfileStruct();
            // Load user profile information from application state
            $info = Rx_AppState::get($profile);
            if ($info) {
                $profile->set($info);
            }
            $this->_initializingUser = true;
            $this->setProfile($profile);
            $this->_initializingUser = false;
            // If user is required - initialize profile with default user information
            if (($this->_isUserRequired()) && (!$this->isUserExists())) {
                $this->_switchingUser = true;
                $default = $this->_getDefaultUser();
                if (!$default instanceof Rx_User_Profile) {
                    throw new Rx_User_Exception('Default user information is required, but not provided');
                }
                $this->setProfile($default);
                Rx_Notify::notify(
                    'rx_user_switched',
                    array(
                        'id' => $this->getProfile()->id,
                    ),
                    Rx_User::getInstance()
                );
                $this->_switchingUser = false;
            }
        }
        return ($this->_profile);
    }

    /**
     * Set user information profile
     *
     * @param Rx_User_Profile $profile New user information profile
     * @return void
     * @throws Rx_User_Exception
     */
    protected function setProfile($profile)
    {
        if (!$profile instanceof Rx_User_Profile) {
            throw new Rx_User_Exception('User information profile must be instance of Rx_User_Profile');
        }
        // Save new user profile into application state
        if ((!$this->_initializingUser) && ($this->_isAppStateShouldBeCleared())) {
            Rx_AppState::removeAll();
        }
        $this->_profile = $profile;
        Rx_AppState::set($profile, $profile->toArray());
    }

    /**
     * Check if user information is defined in provider
     *
     * @return boolean
     */
    public function isUserExists()
    {
        $id = $this->getProfile()->id;
        return (($id !== null));
    }

    /**
     * Get adapter for Zend_Auth to perform authentication through Rx_User provider
     *
     * @return Zend_Auth_Adapter_Interface
     * @throws Rx_User_Exception
     */
    public function getAuthAdapter()
    {
        if (!$this->_authAdapter) {
            $adapter = $this->_getAuthAdapter();
            if (!in_array('Zend_Auth_Adapter_Interface', class_implements($adapter))) {
                throw new Rx_User_Exception('Zend_Auth adapter must implement Zend_Auth_Adapter_Interface interface');
            }
            $this->_authAdapter = $adapter;
        }
        return ($this->_authAdapter);
    }

    /**
     * Get storage adapter for Zend_Auth
     *
     * @return Zend_Auth_Storage_Interface
     * @throws Rx_User_Exception
     */
    public function getAuthStorage()
    {
        if (!$this->_authStorage) {
            $storage = $this->_getAuthStorage();
            if (!in_array('Zend_Auth_Storage_Interface', class_implements($storage))) {
                throw new Rx_User_Exception('Zend_Auth storage adapter must implement Zend_Auth_Storage_Interface interface');
            }
            $this->_authStorage = $storage;
        }
        return ($this->_authStorage);
    }

    /**
     * Clear last error message
     *
     * @return void
     */
    protected function clearError()
    {
        $this->_error = null;
    }

    /**
     * Set error message
     *
     * @param string $error
     * @return boolean
     */
    protected function setError($error)
    {
        $this->_error = $error;
        return (false);
    }

    /**
     * Check if there is error
     *
     * @return boolean
     */
    public function haveError()
    {
        return ($this->_error !== null);
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Switch user to user with given Id
     *
     * @param int $id Id of new current user
     * @return boolean      true if user was switched successfully, false if user switching was declined
     */
    public function switchUser($id)
    {
        $this->clearError();
        if (!$this->_isDirectUserChangingAllowed()) {
            return ($this->setError('user_switch_not_allowed'));
        }
        $this->_switchingUser = true;
        $profile = $this->_getUserProfile($id);
        if (!$profile instanceof Rx_User_Profile) {
            if (!$this->haveError()) {
                $this->setError('user_switch_failed');
            }
            $this->_switchingUser = false;
            return (false);
        }
        $this->setProfile($profile);
        Rx_Notify::notify(
            'rx_user_switched',
            array(
                'id' => $this->getProfile()->id,
            ),
            Rx_User::getInstance()
        );
        $this->_switchingUser = false;
        return (true);
    }

    /**
     * Return true if user switching process is running, false otherwise
     *
     * @return boolean
     */
    public function isSwitchingUser()
    {
        return ($this->_switchingUser);
    }

    /**
     * Perform user login based on given information
     *
     * @param mixed $login,... Login information passed as arbitrary number of arguments
     * @return boolean          true if user is logged in, false if login is failed
     * @event rx_user_login
     */
    public function login($login)
    {
        $this->clearError();
        if (!$this->_isUserLoginAllowed()) {
            return ($this->setError('login_not_allowed'));
        }
        $args = func_get_args();
        $this->_loginUser = true;
        $this->_switchingUser = true;
        $profile = call_user_func_array(array($this, '_loginUser'), $args);
        if (!$profile instanceof Rx_User_Profile) {
            if (!$this->haveError()) {
                $this->setError('login_failed');
            }
            $this->_switchingUser = false;
            $this->_loginUser = false;
            return (false);
        }
        $this->setProfile($profile);
        Rx_Notify::notify(
            'rx_user_login',
            array(
                'id' => $this->getProfile()->id,
            ),
            Rx_User::getInstance()
        );
        $this->_switchingUser = false;
        $this->_loginUser = false;
        return (true);
    }

    /**
     * Perform user logout
     *
     * @return void
     * @event rx_user_logout
     */
    public function logout()
    {
        $this->_loginUser = true;
        $this->_switchingUser = true;
        $this->_logoutUser();
        $this->setProfile($this->_getProfileStruct());
        Rx_Notify::notify('rx_user_logout', null, Rx_User::getInstance());
        $this->_switchingUser = false;
        $this->_loginUser = false;
    }

    /**
     * Determine if user can be changed directly by calling Rx_User::setId()
     *
     * @return boolean
     */
    protected function _isDirectUserChangingAllowed()
    {
        // This method is mean to be overridden in a case if special logic
        // is required to determine if user can be changed directly
        return ($this->_directUserChangingAllowed);
    }

    /**
     * Determine if user can be changed by login
     *
     * @return boolean
     */
    protected function _isUserLoginAllowed()
    {
        // This method is mean to be overridden in a case if special logic
        // is required to determine if user can login/logout
        return ($this->_userLoginAllowed);
    }

    /**
     * Determine if user is required for application
     *
     * @return boolean
     */
    protected function _isUserRequired()
    {
        // This method is mean to be overridden in a case if special logic
        // is required to determine if user is required for application
        return ($this->_userRequired);
    }

    /**
     * Determine if application state information should be cleared upon user's switching
     *
     * @return boolean
     */
    protected function _isAppStateShouldBeCleared()
    {
        // This method is mean to be overridden in a case if special logic
        // is required to determine status of this flag
        return ($this->_clearAppStateOnUserSwitch);
    }

    /**
     * Get adapter for Zend_Auth to perform authentication through Rx_User provider
     *
     * @return Zend_Auth_Adapter_Interface
     */
    protected function _getAuthAdapter()
    {
        $adapter = new Rx_User_Auth_Adapter();
        return ($adapter);
    }

    /**
     * Get storage adapter for Zend_Auth
     *
     * @return Zend_Auth_Storage_Interface
     */
    protected function _getAuthStorage()
    {
        $storage = new Rx_User_Auth_Storage();
        return ($storage);
    }

    /**
     * Get user profile information by given user Id
     *
     * @param int $id User Id to get information about
     * @return Rx_User_Profile|boolean  User profile or false if user Id is declined
     */
    protected function _getUserProfile($id)
    {
        // This method should be overridden into real user information providers
        // to perform validation of given user Ids and to provide required information
        return ($this->setError('user_profile_not_implemented'));
    }

    /**
     * Get user profile information for default user
     *
     * @return Rx_User_Profile
     * @throws Rx_User_Exception
     */
    protected function _getDefaultUser()
    {
        throw new Rx_User_Exception('Default user information is required, but _getDefaultUser() method is not overridden');
    }

    /**
     * Login user by given information.
     *
     * @param mixed $login,... Login information passed as arbitrary number of arguments
     * @return Rx_User_Profile|boolean  User profile on success or false if login failed
     */
    protected function _loginUser($login)
    {
        return ($this->setError('user_login_not_implemented'));
    }

    /**
     * Logout user
     *
     * @return void
     */
    protected function _logoutUser()
    {

    }

}
