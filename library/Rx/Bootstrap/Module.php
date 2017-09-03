<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Rx
 * @package    Rx_Application
 * @subpackage Module
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Base bootstrap class for modules
 *
 * @category   Rx
 * @package    Rx_Application
 * @subpackage Module
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Rx_Bootstrap_Module extends Rx_Bootstrap_Web
{
    /**
     * Set this explicitly to reduce impact of determining module name
     *
     * @var string $_moduleName
     */
    protected $_moduleName = null;
    /**
     * @var string
     */
    protected $_appNamespace = null;
    /**
     * Parent bootstrap instance
     *
     * @var Rx_Bootstrap_Abstract $_parentBootstrap
     */
    protected $_parentBootstrap = null;
    /**
     * @var Zend_Loader_Autoloader_Resource
     */
    protected $_resourceLoader;

    /**
     * Constructor
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return Rx_Bootstrap_Module
     */
    public function __construct($application)
    {
        // Parent constructor is not called because of different way
        // of handling application configuration options
        $this->setApplication($application);

        // Use same plugin loader as parent bootstrap
        if ($application instanceof Zend_Application_Bootstrap_ResourceBootstrapper) {
            $this->_parentBootstrap = $application;
            $this->setPluginLoader($application->getPluginLoader());
        }

        $key = strtolower($this->getModuleName());
        if ($application->hasOption($key)) {
            // Don't run via setOptions() to prevent duplicate initialization
            $this->setOptions($application->getOption($key));
        }

        if ($application->hasOption('resourceloader')) {
            $this->setOptions(array(
                'resourceloader' => $application->getOption('resourceloader')
            ));
        }
        $this->initResourceLoader();

        // ZF-6545: ensure front controller resource is loaded,
        // but only register it in a case if it is missing in global scope
        // and it should be registered in global scope since front controller
        // is a singleton by itself
        if (!$this->getParentBootstrap()->hasPluginResource('FrontController')) {
            $this->getParentBootstrap()->registerPluginResource('FrontController');
        }

        // ZF-6545: prevent recursive registration of modules
        if ($this->hasPluginResource('modules')) {
            $this->unregisterPluginResource('modules');
        }
    }

    /**
     * Ensure resource loader is loaded
     *
     * @return void
     */
    public function initResourceLoader()
    {
        $namespace = $this->getAppNamespace();
        if ($namespace!==false) {
            $r = new ReflectionClass($this);
            $path = $r->getFileName();
            $this->setResourceLoader(new Zend_Application_Module_Autoloader(array(
                'namespace' => $namespace,
                'basePath'  => dirname($path),
            )));
        }
    }

    /**
     * Set module resource loader
     *
     * @param  Zend_Loader_Autoloader_Resource $loader
     * @return Rx_Bootstrap_Module
     */
    public function setResourceLoader(Zend_Loader_Autoloader_Resource $loader)
    {
        $this->_resourceLoader = $loader;
        return $this;
    }

    /**
     * Retrieve module resource loader
     *
     * @return Zend_Loader_Autoloader_Resource
     */
    public function getResourceLoader()
    {
        if (!$this->_resourceLoader) {
            $this->initResourceLoader();
        }
        return $this->_resourceLoader;
    }

    /**
     * Get default application namespace
     *
     * Proxies to {@link getModuleName()}, and returns the current module name
     *
     * @return string
     */
    public function getAppNamespace()
    {
        if (!$this->_appNamespace) {
            $this->setAppNamespace($this->getModuleName());
        }
        return $this->_appNamespace;
    }

    /**
     * Set application namespace (for module autoloading)
     *
     * @param string $value
     * @return Rx_Bootstrap_Module
     */
    public function setAppNamespace($value)
    {
        $this->_appNamespace = $value;
        return $this;
    }

    /**
     * Retrieve module name
     *
     * @return string
     */
    public function getModuleName()
    {
        if (empty($this->_moduleName)) {
            $class = get_class($this);
            if (preg_match('/^([a-z][a-z0-9]*)_/i', $class, $matches)) {
                $prefix = $matches[1];
            } else {
                $prefix = $class;
            }
            $this->_moduleName = $prefix;
        }
        return $this->_moduleName;
    }

    /**
     * Retrieve parent application instance
     *
     * @param boolean $root     OPTIONAL true to get root Zend_Application object,
     *                          false to get parent application instance
     * @return Zend_Application|Zend_Application_Bootstrap_Bootstrapper
     */
    public function getApplication($root = false)
    {
        if ($root) {
            $application = $this->_application;
            do {
                if (!$application) {
                    break;
                }
                $application = $application->getApplication();
            } while (!$application instanceof Zend_Application);
            return ($application);
        } else {
            return ($this->_application);
        }
    }

    /**
     * Get instance of bootstrap object that owns this module bootstrap
     *
     * @return Rx_Bootstrap_Abstract
     */
    public function getParentBootstrap()
    {
        return ($this->_parentBootstrap);
    }

    /**
     * Execute a resource
     *
     * @param  string $resource
     * @return void
     * @throws Zend_Application_Bootstrap_Exception When resource not found
     */
    protected function _executeResource($resource)
    {
        // If it is plugin resource that was already registered in global scope -
        // execute it in global scope. Check resource registration explicitly
        // to avoid transparent resource registration from this request
        if ($this->getParentBootstrap()->isExists($resource)) {
            $this->getParentBootstrap()->bootstrap($resource);
        }
        parent::_executeResource($resource);
    }

    /**
     * Check if application resource with given name is registered
     *
     * @param string $resource   Name of resource to check
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return boolean
     */
    public function isRegisteredResource($resource, $recursive = false)
    {
        $result = parent::isRegisteredResource($resource);
        if ((!$result) && ($recursive)) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = $parent->isRegisteredResource($resource, $recursive);
            }
        }
        return ($result);
    }

    /**
     * Check if application resource with given name was already executed
     *
     * @param string $resource   Name of resource to check
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return boolean
     */
    public function isExecuted($resource, $recursive = false)
    {
        $result = parent::isExecuted($resource);
        if ((!$result) && ($recursive)) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = $parent->isExecuted($resource, $recursive);
            }
        }
        return ($result);
    }

    /**
     * Is the requested plugin resource registered?
     *
     * @param  string $resource
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return boolean
     */
    public function hasPluginResource($resource, $recursive = false)
    {
        $result = parent::hasPluginResource($resource);
        if ((!$result) && ($recursive)) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = $parent->hasPluginResource($resource, $recursive);
            }
        }
        return ($result);
    }

    /**
     * Get a registered plugin resource
     *
     * @param  string $resource
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return Zend_Application_Resource_Resource
     */
    public function getPluginResource($resource, $recursive = false)
    {
        $result = parent::getPluginResource($resource);
        if ((!$result) && ($recursive)) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = $parent->getPluginResource($resource, $recursive);
            }
        }
        return ($result);
    }

    /**
     * Retrieve all plugin resources
     *
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return array
     */
    public function getPluginResources($recursive = false)
    {
        $result = parent::getPluginResources($recursive);
        if ($recursive) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = array_merge($parent->getPluginResources($recursive), $result);
            }
        }
        return ($result);
    }

    /**
     * Retrieve plugin resource names
     *
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return array
     */
    public function getPluginResourceNames($recursive = false)
    {
        $result = parent::getPluginResourceNames($recursive);
        if ($recursive) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = array_unique(array_merge($parent->getPluginResourceNames($recursive), $result));
            }
        }
        return ($result);
    }

    /**
     * Determine if a resource has been stored in the container
     *
     * @param  string $name
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return boolean
     */
    public function hasResource($name, $recursive = false)
    {
        $result = parent::hasResource($name);
        if ((!$result) && ($recursive)) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = $parent->hasResource($name, $recursive);
            }
        }
        return ($result);
    }

    /**
     * Retrieve a resource from the container
     *
     * @param  string $name
     * @param boolean $recursive OPTIONAL true to also check parent bootstraps
     * @return null|mixed
     */
    public function getResource($name, $recursive = false)
    {
        $result = parent::getResource($name);
        if ((!$result) && ($recursive)) {
            $parent = $this->getParentBootstrap();
            if ($parent) {
                $result = $parent->getResource($name, $recursive);
            }
        }
        return ($result);
    }

}
