<?php

/**
 * Generic application bootstrap class
 *
 * Global constants that are expected to be defined:
 *
 * APPLICATION_ROOT     - Path to root directory of application
 * APPLICATION_PATH     - Path to directory where application classes resides
 *                        Usually: APPLICATION_ROOT.'/application'
 * APPLICATION_LIBRARY  - Path to directory with shared application libraries (actually root directory of Rx library)
 *                        Usually: APPLICATION_ROOT.'/library'
 * APPLICATION_ENV      - Application environment (e.g. "production" or "development")
 */
abstract class Rx_Bootstrap_Abstract extends Zend_Application_Bootstrap_BootstrapAbstract
{
    /**
     * List of application resources to run at the very beginning of bootstrap process
     *
     * @var array $_preBootstrap
     */
    protected $_preBootstrap = null;

    /**
     * Class constructor
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return Rx_Bootstrap_Abstract
     * @throws Zend_Application_Bootstrap_Exception When invalid application is provided
     */
    public function __construct($application)
    {
        parent::__construct($application);
        if (!is_array($this->_preBootstrap)) {
            // There is no custom list of pre-bootstrap modules are defined
            // so define standard list of them.
            $this->_preBootstrap = array();
            // By default we should bootstrap errors handling module
            // so it will be able to handle errors that may occur
            // during application bootstrap process
            $this->_preBootstrap[] = 'errors';
            if ($this->isRegisteredResource('debug')) {
                // If debug resource is registered - run it at the very beginning
                // of bootstrap process to allow setting debug modifications
                $this->_preBootstrap[] = 'debug';
            }
        }
        $this->bootstrap($this->_preBootstrap);
    }

    /**
     * Set comma-separated list of applications that should be launched
     * at the very beginning of application's bootstrap process
     *
     * @param string $resources
     * @return void
     */
    public function setPreBootstrap($resources)
    {
        $resources = explode(',', $resources);
        foreach ($resources as $key => $resource) {
            $resource = strtolower(trim($resource));
            if (strlen($resource)) {
                $resources[$key] = $resource;
            } else {
                unset($resources[$key]);
            }
        }
        $this->_preBootstrap = $resources;
    }

    /**
     * Check if application resource with given name is registered
     *
     * @param string $resource Name of resource to check
     * @return boolean
     */
    public function isRegisteredResource($resource)
    {
        return ((in_array($resource, $this->getClassResourceNames())) ||
            (in_array($resource, $this->getPluginResourceNames())));
    }

    /**
     * Check if application resource with given name is exists
     * into list of registered resources in bootstrap.
     *
     * This method differs from isRegisteredResource() because it just
     * performs check of resource existence without implicitly
     * registering it during check.
     *
     * @param string $resource      Name of resource to check
     * @param boolean $onlyValid    OPTIONAL true to return positive result only for valid resources,
     *                              false to simply check resource registration existence
     * @return boolean
     */
    public function isExists($resource, $onlyValid = true)
    {
        $resource = strtolower($resource);
        if (in_array($resource, $this->getClassResourceNames())) {
            return (true);
        }
        if (array_key_exists($resource, $this->_pluginResources)) {
            // Resource is explicitly registered
            if ($onlyValid) {
                // Make sure that it is valid resource
                if (!$this->_pluginResources[$resource] instanceof Zend_Application_Resource_Resource) {
                    // Workaround against ZF-12076
                    $rInfo = $this->_pluginResources[$resource];
                    $resource = $this->getPluginResource($resource);
                    $this->_pluginResources[$resource] = $rInfo;
                    return ($resource instanceof Zend_Application_Resource_Resource);
                } else {
                    return (true);
                }
            } else {
                return (true);
            }
        } else {
            return (false);
        }
    }

    /**
     * Check if application resource with given name was already executed
     *
     * @param string $resource Name of resource to check
     * @return boolean
     */
    public function isExecuted($resource)
    {
        return (in_array($resource, $this->_run));
    }

    /**
     * Get a registered plugin resource
     *
     * @param string $resource
     * @return Zend_Application_Resource_Resource
     */
    public function getPluginResource($resource)
    {
        // @see ZF-10979 for details about why this functionality is moved into separate class
        $oResource = parent::getPluginResource($resource);
        if ($oResource !== null) {
            return ($oResource);
        }
        // Attempt to load plugin resource in a case if it is not found yet
        $oResource = $this->_loadPluginResource($resource, null);
        if ($oResource) {
            return ($oResource);
        }
        return (null);
    }

    /**
     * Load a plugin resource
     *
     * @param  string $resource
     * @param  array|object|null $options
     * @return string|boolean
     */
    protected function _loadPluginResource($resource, $options)
    {
        if ((is_object($options)) && (method_exists($options, 'toArray'))) {
            $options = $options->toArray();
        } elseif ((is_string($options)) && (!strlen(
                $options
            ))
        ) { // Avoid create empty options from inclusion of plugin resources as .ini configuration entries
            $options = array();
        }
        return (parent::_loadPluginResource($resource, $options));
    }

}
