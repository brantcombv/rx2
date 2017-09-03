<?php

class Rx_Application_Resource_Aclmvc extends Rx_Application_Resource_Abstract
{
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'aclClass'        => 'Rx_Acl_Mvc', // Class to use for creating ACL object
        'defaultRole'     => 'anonymous', // Default role for MVC ACL object
        'notAllowedMvc'   => 'error.error', // MVC target for "not allowed" ACL result
        'multipleModules' => null, // true if application uses multiple modules, null to autodetect it
    );
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('frontcontroller', 'user', 'url');

    /**
     * Perform resource initialization
     *
     * @throws Zend_Application_Resource_Exception
     * @return Rx_Acl_Mvc
     */
    protected function _init()
    {
        $options = $this->getOptions();
        $class = $options['aclClass'];
        if (!class_exists($class, true)) {
            throw new Zend_Application_Resource_Exception('Unavailable MVC ACL class: ' . $class);
        }
        // Determine multiple modules usage by application
        if ($options['multipleModules'] === null) {
            // Most obvious indicator of usage of multiple modules is use of "modules" application resource
            $options['multipleModules'] = $this->getBootstrap()->isRegisteredResource('modules');
        }
        /* @var $acl Rx_Acl_Mvc */
        $acl = new $class($options);
        if (!$acl instanceof Rx_Acl_Mvc) {
            throw new Zend_Application_Resource_Exception('MVC ACL class "' . $class . '" must be instance of Rx_Acl_Mvc');
        }
        Zend_Registry::set('Rx_Acl_Mvc', $acl);
        $plugin = new Rx_Controller_Plugin_Acl_Mvc($acl, $options);
        $this->getBootstrap()->getResource('frontcontroller')->registerPlugin($plugin);
        return ($acl);
    }

}
