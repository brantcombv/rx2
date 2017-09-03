<?php

class Rx_Application_Resource_View extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('path');
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'doctype'     => null, // Doctype for documents that will be rendered by view
        'charset'     => null, // Charset meta tag value
        'contentType' => null, // Content-Type meta tag value
    );

    /**
     * Perform resource initialization
     *
     * @return Zend_View
     */
    protected function _init()
    {
        $options = $this->getOptions();
        $bootstrap = $this->getBootstrap();
        $options['bootstrap'] = $bootstrap;
        // Register standard paths for view helpers and view filters
        if (!array_key_exists('helperPath', $options)) {
            $options['helperPath'] = Rx_Loader::getPrefixPath('Rx_View_Helper');
        }
        if (!array_key_exists('filterPath', $options)) {
            $options['filterPath'] = Rx_Loader::getPrefixPath('Rx_View_Filter');
        }
        // Determine path to view scripts
        if (array_key_exists('scriptPath', $options)) {
            $path = Rx_Path::resolve($options['scriptPath']);
        } elseif (Rx_Path::isRegistered('views')) {
            $path = Rx_Path::get('views', true);
        } else {
            $path = 'views/scripts';
        }
        if (!Rx_Path::isAbsolute($path)) {
            if ($bootstrap instanceof Rx_Bootstrap_Module) {
                // For module-based bootstrap we should get view scripts path relative to module root
                $basePath = $bootstrap->getResourceLoader()->getBasePath();
            } else {
                $basePath = Rx_Path::get('app', true);
            }
            $path = Rx_Path::build($basePath, $path, true);
        }
        if (is_dir($path)) {
            $options['scriptPath'] = $path;
        } else {
            trigger_error('View scripts path pointers to unavailable directory: ' . $path, E_USER_WARNING);
            unset($options['scriptPath']);
        }
        // Perform default resource initialization
        $resource = new Zend_Application_Resource_View($options);
        $view = $resource->init();
        Zend_Registry::set('Zend_View', $view);
        // If charset is defined but not applied due to non-HTML5 doctype -
        // create HTML4-compatible meta for it
        if (($options['charset']) && (!$options['contentType'])) {
            /** @var $doctype Zend_View_Helper_Doctype */
            $doctype = $view->doctype();
            if (!$doctype->isHtml5()) {
                /** @var $headMeta Zend_View_Helper_HeadMeta */
                $headMeta = $view->headMeta();
                $headMeta->appendHttpEquiv('Content-Type', 'text/html; charset=' . $options['charset']);
            }
        }

        return ($view);
    }

}
