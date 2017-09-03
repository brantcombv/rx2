<?php

class Rx_Application_Resource_Model extends Rx_Application_Resource_Abstract
{
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'provider' => 'default', // Model information provider class name
    );
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('appstate', 'config', 'cache', 'db', 'scope');

    /**
     * Perform resource initialization
     *
     * @throws Rx_Model_Exception
     * @return void
     */
    protected function _init()
    {
        // Create model provider class as defined in model configuration
        $options = $this->getOptions();
        $provider = Rx_Loader::loadPlugin($options['provider'], 'Rx_Model_Provider');
        if ($provider) {
            $provider = new $provider();
        }
        if (!$provider instanceof Rx_Model_Provider_Abstract) {
            throw new Rx_Model_Exception('Model information provider must be instance of Rx_Model_Provider_Abstract');
        }
        Zend_Registry::set('Rx_Model_Provider', $provider);
    }

}
