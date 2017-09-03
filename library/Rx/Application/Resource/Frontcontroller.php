<?php

class Rx_Application_Resource_Frontcontroller extends Rx_Application_Resource_Abstract
{
    /**
     * Front controller plugin to switch module-based application resources
     * for applications with multiple modules
     *
     * @var string $_modulePlugin
     */
    protected $_modulePlugin = 'Rx_Controller_Plugin_Module';
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('autoloader', 'path');

    /**
     * Perform resource initialization
     *
     * @return Zend_Controller_Front
     */
    protected function _init()
    {
        // Provide defaults for front controller configuration
        // if these options are not defined explicitly
        $options = $this->getOptions();
        $options = array_change_key_case($options, CASE_LOWER);
        $options['bootstrap'] = $this->getBootstrap();
        if (!array_key_exists('throwexceptions', $options)) {
            $options['throwexceptions'] = (defined('APPLICATION_ENV')) ? (APPLICATION_ENV == 'development') : false;
        }
        if (!array_key_exists('baseurl', $options)) {
            $options['baseurl'] = Rx_Path::getBaseUrl();
        }
        // Perform usual bootstrap of front controller
        $resource = new Zend_Application_Resource_Frontcontroller($options);
        $front = $resource->init();
        // If no controller directory is defined - set default directory
        if (!$front->getControllerDirectory()) {
            $front->setControllerDirectory(Rx_Path::get('app', 'controllers', true));
        }
        // Configure plugins loader for controller action helpers
        $prefixes = Rx_Loader::getPrefixPath('Rx_Controller_Action_Helper');
        foreach ($prefixes as $prefix => $path) {
            Zend_Controller_Action_HelperBroker::addPath($path, $prefix);
        }
        // For applications with multiple modules enable controller plugin
        // to switch module-based application resources (e.g. views and layouts)
        if ($this->getBootstrap()->isRegisteredResource('modules', true)) {
            $this->setModulePlugin($this->_modulePlugin);
            $front->registerPlugin($this->_modulePlugin);
        }
        return ($front);
    }

    /**
     * Set name/instance of controller plugin class to switch
     * module-based application resources for applications with multiple modules
     *
     * @param string|Zend_Controller_Plugin_Abstract $class Class name/instance
     * @return void
     * @throws Zend_Application_Bootstrap_Exception
     */
    protected function setModulePlugin($class)
    {
        if (is_string($class)) {
            if (!class_exists($class, true)) {
                throw new Zend_Application_Bootstrap_Exception('Module plugin class is not found: ' . $class);
            }
            $class = new $class();
        }
        if (!$class instanceof Zend_Controller_Plugin_Abstract) {
            throw new Zend_Application_Bootstrap_Exception('Module plugin must be instance of Zend_Controller_Plugin_Abstract');
        }
        $this->_modulePlugin = $class;
    }

}
