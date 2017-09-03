<?php

class Rx_Application_Resource_Url extends Rx_Application_Resource_Abstract
{
    /**
     * true to attempt to guess some of configuration parameters
     *
     * @var boolean $_guess
     */
    protected $_guess = true;

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // The only purpose of application resource is to give ability to setup
        // default configuration options for Rx_Url based on explicitly defined values
        // given as application resource configuration, e.g.:
        // resources.url.mvc_use_module = true
        $config = $this->getOptions();
        unset($config['guess']);
        if ($this->_guess) {
            // Try to be smart to guess some values from other application resources
            /* @var $bootstrap Rx_Bootstrap_Abstract */
            $bootstrap = $this->getBootstrap();
            if ($bootstrap->isRegisteredResource('frontcontroller')) {
                $bootstrap->bootstrap('frontcontroller');
                /* @var $front Zend_Controller_Front */
                $front = $bootstrap->getResource('frontcontroller');
                // Register front controller plugin that will monitor request parameters
                // and modify Rx_Url default configuration parameters accordingly
                $plugin = Rx_Loader::loadPlugin('url', 'Rx_Controller_Plugin');
                if ($plugin) {
                    /** @var $plugin Rx_Controller_Plugin_Url */
                    $plugin = new $plugin;
                    $front->registerPlugin($plugin);
                }
                // Enable usage of ZF routing subsystem for building URLs
                if (!array_key_exists('mvc_use_router', $config)) {
                    $config['mvc_use_router'] = true;
                }
                // If "modules" application resource is registered -
                // then application is mean to use multiple modules and hence it may be good idea
                // to enable module element into MVC target construction
                if ($bootstrap->isRegisteredResource('modules')) {
                    if (!array_key_exists('mvc_use_module', $config)) {
                        $config['mvc_use_module'] = true;
                    }
                }
            } else {
                // If no front controller is used - disable use of ZF routing subsystem
                if (!array_key_exists('mvc_use_router', $config)) {
                    $config['mvc_use_router'] = false;
                }
            }
            // If paths are used - we can take base URL from it
            if ($bootstrap->isRegisteredResource('path')) {
                if (!array_key_exists('base_url', $config)) {
                    $config['base_url'] = Rx_Path::getBaseUrl();
                }
            }
            // If we're running console or RPC application - disable use of ZF routing subsystem
            if (($bootstrap instanceof Rx_Bootstrap_Console) ||
                ($bootstrap instanceof Rx_Bootstrap_Rpc)
            ) {
                if (!array_key_exists('mvc_use_module', $config)) {
                    $config['mvc_use_module'] = false;
                }
            }
        }
        if (sizeof($config)) {
            Rx_Url::setConfig($config);
        }
    }

    /**
     * Set flag to attempt to guess some of configuration parameters
     *
     * @param boolean $value
     * @return void
     */
    public function setGuess($value)
    {
        $this->_guess = (boolean)$value;
    }

}
