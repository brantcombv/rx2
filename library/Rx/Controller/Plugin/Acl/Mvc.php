<?php

class Rx_Controller_Plugin_Acl_Mvc extends Zend_Controller_Plugin_Abstract
{
    /**
     * MVC ACL object instance
     *
     * @var Zend_Acl $_acl
     */
    protected $_acl = null;
    /**
     * Configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;

    /**
     * Class constructor
     *
     * @param Zend_Acl $acl             OPTIONAL ACL object to use
     * @param array|Zend_Config $config OPTIONAL Configuration options
     * @return Rx_Controller_Plugin_Acl_Mvc
     */
    public function __construct($acl = null, $config = null)
    {
        if (!$acl instanceof Zend_Acl) {
            if (Zend_Registry::isRegistered('Rx_Acl_Mvc')) {
                $acl = Zend_Registry::get('Rx_Acl_Mvc');
            }
            if (!$acl instanceof Zend_Acl) {
                $acl = new Rx_Acl_Mvc();
                Zend_Registry::set('Rx_Acl_Mvc', $acl);
            }
        }
        $this->_acl = $acl;
        $this->_config = new Rx_Configurable_Embedded($this, array(
            'defaultRole'       => 'anonymous', // Default role for MVC ACL object
            'invalidRequestMvc' => 'error.error', // MVC target for "invalid request" ACL result
            'notAllowedMvc'     => 'error.error', // MVC target for "not allowed" ACL result
        ), null, $config);
    }

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // Check if given request is dispatchable
        if (!Zend_Controller_Front::getInstance()->getDispatcher()->isDispatchable($request)) {
            $this->mvcRedirect($this->_config->getConfig('invalidRequestMvc'), $request);
            return;
        }
        // Determine if current user is allowed to access requested page
        $mvc = Rx_Url::parseRequest($request);
        $role = (Rx_User::isExists()) ? Rx_User::getRole() : $this->_config->getConfig('defaultRole');
        $resource = Rx_Url::toMvc($mvc, array('mvc_prefix' => 'mvc:'));
        $allowed = $this->_acl->isAllowed($role, $resource);
        if (!$allowed) { // Redirect to "not allowed" page
            $this->mvcRedirect($this->_config->getConfig('notAllowedMvc'), $request);
        }
    }

    /**
     * Perform request redirect to given MVC target
     *
     * @param string $target                            MVC target to redirect to
     * @param Zend_Controller_Request_Abstract $request Request to use for redirect
     * @return void
     */
    protected function mvcRedirect($target, Zend_Controller_Request_Abstract $request)
    {
        $mvc = Rx_Url::parse($target);
        $request
            ->setModuleName($mvc['mvc']['module'])
            ->setControllerName($mvc['mvc']['controller'])
            ->setActionName($mvc['mvc']['action']);
    }

}
