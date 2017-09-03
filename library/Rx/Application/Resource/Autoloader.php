<?php

class Rx_Application_Resource_Autoloader extends Rx_Application_Resource_Abstract
{
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'namespace'       => 'Rx', // Namespace that is handled by autoloader
        'basePath'        => null, // Base path for namespace resources (defaults to APPLICATION_ROOT)
        'resource'        => array(), // Resource types to add to autoloader (resource.<Id>.name and resource.<Id>.path)
        'defaultResource' => null, // Default resource type
        'includepaths'    => array(), // Additional include paths for application (relative to autoloader's base path)
    );

    /**
     * Perform resource initialization
     *
     * @throws Rx_Exception
     * @return Zend_Loader_Autoloader_Resource
     */
    protected function _init()
    {
        $options = $this->getOptions();
        /** @var $boostrap Rx_Bootstrap_Module */
        $boostrap = $this->getBootstrap();
        if ($boostrap instanceof Rx_Bootstrap_Module) {
            $autoloader = $boostrap->getResourceLoader();
        } else {
            if ($options['basePath']) {
                $basePath = $options['basePath'];
            } elseif (defined('APPLICATION_ROOT')) {
                $basePath = APPLICATION_ROOT;
            } else {
                throw new Rx_Exception('Base path for resources autoloader is not defined. APPLICATION_ROOT is expected to be defined and point to root directory of application in this case');
            }
            // Create resources autoloader
            $autoloader = new Zend_Loader_Autoloader_Resource(array(
                'namespace' => $options['namespace'],
                'basePath'  => $basePath,
            ));
        }
        $paths = (is_array($options['includepaths'])) ? $options['includepaths'] : array();
        if (sizeof($paths)) {
            $basePath = $autoloader->getBasePath();
            foreach ($paths as $name => $path) {
                $paths[$name] = Rx_Path::build($basePath, $path, true);
            }
            $bootstrap = $boostrap;
            do {
                $application = $bootstrap->getApplication();
                $bootstrap = $application;
            } while (!$application instanceof Zend_Application);
            $application->setIncludePaths($paths);
        }
        $resources = (is_array($options['resource'])) ? $options['resource'] : array();
        foreach ($resources as $type => $params) {
            if (!array_key_exists('namespace', $params)) {
                trigger_error(
                    '"resources.autoloader.resource.' . $type . '.namespace" parameter is not defined into application configuration',
                    E_USER_WARNING
                );
                continue;
            }
            if (!array_key_exists('path', $params)) {
                trigger_error(
                    '"resources.autoloader.resource.' . $type . '.path" parameter is not defined into application configuration',
                    E_USER_WARNING
                );
                continue;
            }
            $autoloader->addResourceType($type, $params['path'], $params['namespace']);
        }
        if (strlen($options['defaultResource'])) {
            $autoloader->setDefaultResourceType($options['defaultResource']);
        }
        return ($autoloader);
    }

}
