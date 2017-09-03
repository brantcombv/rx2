<?php

class Rx_Application_Resource_User extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('appstate');
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'provider' => 'default', // User information provider class name
    );

    /**
     * Perform resource initialization
     *
     * @throws Rx_User_Exception
     * @return void
     */
    protected function _init()
    {
        $options = $this->getOptions();
        $provider = Rx_Loader::loadPlugin($options['provider'], 'Rx_User_Provider');
        if ($provider) {
            $provider = new $provider();
        }
        if (!$provider instanceof Rx_User_Provider_Abstract) {
            throw new Rx_User_Exception('User information provider object must be instance of Rx_User_Provider_Abstract');
        }
        Rx_User::registerProvider($provider);
    }

}
