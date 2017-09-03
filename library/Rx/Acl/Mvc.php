<?php

/**
 * ACL for MVC targets
 */
class Rx_Acl_Mvc extends Zend_Acl
{
    /**
     * true if application uses multiple modules
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_multipleModules = false;
    /**
     * Name of default ACL role
     *
     * @var string|Zend_Acl_Role_Interface $_defaultRole
     */
    protected $_defaultRole = null;

    /**
     * Class constructor
     *
     * @param array $options OPTIONAL Configuration options
     * @return Rx_Acl_Mvc
     */
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            $options = array();
        }
        if (array_key_exists('multipleModules', $options)) {
            $this->_multipleModules = (boolean)$options['multipleModules'];
        }
        if (array_key_exists('defaultRole', $options)) {
            $this->setDefaultRole($options['defaultRole']);
        }
        $this->init();
    }

    /**
     * Set default ACL role
     *
     * @param string|Zend_Acl_Role_Interface $role
     * @return Zend_Acl
     * @throws Zend_Acl_Exception
     */
    public function setDefaultRole($role)
    {
        if (is_string($role)) {
            $role = new Zend_Acl_Role($role);
        }
        if (($role instanceof Zend_Acl_Role_Interface) || ($role === null)) {
            $this->_defaultRole = $role;
            if (($this->_defaultRole) && (!$this->hasRole($this->_defaultRole))) {
                $this->addRole($this->_defaultRole);
            }
        } else {
            throw new Zend_Acl_Exception('Invalid value is passed as default ACL role');
        }
        return ($this);
    }

    /**
     * Get default ACL role
     *
     * @return Zend_Acl_Role_Interface|null
     */
    public function getDefaultRole()
    {
        return ($this->_defaultRole);
    }

    /**
     * ACL list initialization
     *
     * @return void
     */
    public function init()
    {
        // This method is mean to be overridden to provide actual ACL lists
    }

    /**
     * Returns the identified Resource
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @throws Zend_Acl_Exception
     * @return Zend_Acl_Resource_Interface
     */
    public function get($resource)
    {
        $result = null;
        try {
            $result = parent::get($resource);
        } catch (Zend_Acl_Exception $e) {
            // Exception is thrown in a case if no resource is available - add it dynamically
            $target = ($resource instanceof Zend_Acl_Resource_Interface) ? $resource->getResourceId() : $resource;
            $parent = null;
            if (preg_match('/^(mvc:)([a-z0-9\_]+)(?:\.([a-z0-9\_]+))?(?:\.([a-z0-9\_]+))?$/i', $target, $t)) {
                // This is MVC resource, create all implied resources
                array_shift($t);
                $prefix = array_shift($t);
                $any = '__any__';
                $action = $any;
                $controller = $any;
                $module = $any;
                switch (sizeof($t)) {
                    case 1:
                        if ($this->_multipleModules) { // mvc:module
                            $module = array_shift($t);
                        } else { // mvc:controller
                            $controller = array_shift($t);
                        }
                        break;
                    case 2:
                        if ($this->_multipleModules) { // mvc:module.controller
                            $module = array_shift($t);
                            $controller = array_shift($t);
                        } else { // mvc:controller.action
                            $controller = array_shift($t);
                            $action = array_shift($t);
                        }
                        break;
                    case 3:
                        // mvc:module.controller.action
                        $module = array_shift($t);
                        $controller = array_shift($t);
                        $action = array_shift($t);
                        break;
                    default:
                        throw new Zend_Acl_Exception('Invalid format of MVC ACL identifier: ' . $target);
                        break;
                }
                if (!$this->_multipleModules) {
                    // Ignore "module" component for MVC ACL target for applications with single module
                    $module = Rx_Url::getConfig('default_module');
                }
                $targets = array(
                    $prefix . $module . '.' . $any . '.' . $any,
                    $prefix . $module . '.' . $controller . '.' . $any,
                    $prefix . $module . '.' . $controller . '.' . $action,
                );
                $target = $prefix . $module . '.' . $controller . '.' . $action;
                $parent = null;
                foreach ($targets as $ct) {
                    if (!$this->has($ct)) {
                        $this->addResource($ct, $parent);
                    }
                    if ($ct == $target) {
                        break;
                    }
                    $parent = $ct;
                }
            } elseif (preg_match('/^mvc:/i', $target)) {
                // This is MVC resource but it is not valid
                $resource = new Zend_Acl_Resource('__mvc_invalid_request__');
                if (!$this->has($resource)) {
                    $this->addResource($resource);
                    // Don't allow anybody to reach these resources
                    $this->deny(array_keys($this->_getRoleRegistry()->getRoles()), $resource);
                }
                $result = $resource;
            }
            // Get newly added MVC resource
            if (!$result instanceof Zend_Acl_Resource_Interface) {
                $result = parent::get($target);
            }
        }
        return ($result);
    }

}
