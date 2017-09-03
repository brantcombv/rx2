<?php

class Rx_Application_Resource_Scope extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('appstate', 'config');
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'provider' => 'default', // Scope information provider class name
    );

    /**
     * Perform resource initialization
     *
     * @throws Rx_Scope_Exception
     * @return void
     */
    protected function _init()
    {
        $options = $this->getOptions();
        $provider = Rx_Loader::loadPlugin($options['provider'], 'Rx_Scope_Provider');
        if ($provider) {
            $provider = new $provider();
        }
        if (!$provider instanceof Rx_Scope_Provider_Abstract) {
            throw new Rx_Scope_Exception('Scope information provider object must be instance of Rx_Scope_Provider_Abstract');
        }
        Rx_Scope::registerProvider($provider);
    }

}
