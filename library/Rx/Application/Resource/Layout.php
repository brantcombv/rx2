<?php

class Rx_Application_Resource_Layout extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('frontcontroller', 'view', 'path');

    /**
     * Perform resource initialization
     *
     * @return Zend_Layout
     */
    protected function _init()
    {
        // Provide defaults for layout configuration
        // if these options are not defined explicitly
        $options = $this->getOptions();
        $options['bootstrap'] = $this->getBootstrap();
        if (!array_key_exists('layout', $options)) {
            $options['layout'] = 'default';
        }
        // Determine path to layout
        if (array_key_exists('layoutPath', $options)) {
            $path = Rx_Path::resolve($options['layoutPath'], true);
        } elseif (Rx_Path::isRegistered('layouts')) {
            $path = Rx_Path::get('layouts', true);
        } else {
            $path = 'views/layouts';
        }
        if (!Rx_Path::isAbsolute($path)) {
            if ($this->getBootstrap() instanceof Rx_Bootstrap_Module) {
                // For module-based bootstrap we should get layout path relative to module root
                $basePath = $this->getBootstrap()->getResourceLoader()->getBasePath();
            } else {
                $basePath = Rx_Path::get('app', true);
            }
            $path = Rx_Path::build($basePath, $path, true);
        }
        if (is_dir($path)) {
            $options['layoutPath'] = $path;
        } else {
            trigger_error('Layouts path pointers to unavailable directory: ' . $path, E_USER_WARNING);
            unset($options['layoutPath']);
        }
        // Perform default resource initialization
        if ($this->getBootstrap() instanceof Rx_Bootstrap_Module) {
            // For module bootstrap we should reset layout MVC instance
            // to be able to have separate layouts for different modules
            Zend_Layout::resetMvcInstance();
        }
        $resource = new Zend_Application_Resource_Layout($options);
        $layout = $resource->init();

        return ($layout);
    }

}
