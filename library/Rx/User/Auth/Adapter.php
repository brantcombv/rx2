<?php

class Rx_User_Auth_Adapter implements Zend_Auth_Adapter_Interface
{

    /**
     * Authentication credentials
     *
     * @var array $_credentials
     */
    protected $_credentials = array();

    /**
     * Set credentials for authentication through adapter
     *
     * @param mixed $credentials,... Authentication credentials passed as arbitrary number of arguments
     * @return void
     */
    public function setCredentials($credentials)
    {
        $this->_credentials = func_get_args();
    }

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception  If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (call_user_func_array(array(Rx_User::getInstance(), 'login'), $this->_credentials)) {
            $result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, Rx_User::getId());
        } else {
            $result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
        }
        return ($result);
    }

}
