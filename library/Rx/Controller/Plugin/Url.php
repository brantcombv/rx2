<?php

/**
 * Controller plugin to tune Rx_Url configuration
 * depending on current MVC target parameters
 */
class Rx_Controller_Plugin_Url extends Zend_Controller_Plugin_Abstract
{

    /**
     * Configuration options passed to URL application resource
     *
     * @var array $_config
     */
    protected $_config = array();

    /**
     * Class constructor
     *
     * @param array $config OPTIONAL Configuration options passed to URL application resource
     * @return Rx_Controller_Plugin_Url
     */
    public function __construct($config = null)
    {
        if (is_array($config)) {
            $this->_config = $config;
        }
    }

    /**
     * Called before Zend_Controller_Front begins evaluating the
     * request against its routes.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        // Define default parameters for generating complete URLs
        // based on current request parameters
        $config = array();
        if (!array_key_exists('domain', $this->_config)) {
            /** @var $request Zend_Controller_Request_Http */
            $host = $request->getHttpHost();
            $p = explode(':', $host);
            $host = array_shift($p);
            if (strlen($host)) {
                $config['domain'] = $host;
            }
            $port = array_shift($p);
            if ((!array_key_exists('port', $this->_config)) && (strlen($port))) {
                $config['port'] = $port;
            }
        }
        if (!array_key_exists('protocol', $this->_config)) {
            $config['protocol'] = $request->getScheme();
        }
        if (sizeof($config)) {
            Rx_Url::setConfig($config);
        }
    }

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     * Switches view and layout for correct instances as defined by application configuration
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // Setup default module/controller/action for URLs based on their current values
        $config = array();
        if (!array_key_exists('default_module', $this->_config)) {
            $module = $request->getModuleName();
            if (!$module) {
                $module = Zend_Controller_Front::getInstance()->getDefaultModule();
            }
            $config['default_module'] = $module;
        }
        if (!array_key_exists('default_controller', $this->_config)) {
            $controller = $request->getControllerName();
            if (!$controller) {
                $controller = Zend_Controller_Front::getInstance()->getDefaultControllerName();
            }
            $config['default_controller'] = $controller;
        }
        if (!array_key_exists('default_action', $this->_config)) {
            $action = $request->getActionName();
            if (!$action) {
                $action = Zend_Controller_Front::getInstance()->getDefaultAction();
            }
            $config['default_action'] = $action;
        }
        if (sizeof($config)) {
            Rx_Url::setConfig($config);
        }
    }

}
