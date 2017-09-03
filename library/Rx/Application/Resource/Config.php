<?php

class Rx_Application_Resource_Config extends Rx_Application_Resource_Abstract
{
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'paths' => array(), // Paths to directories with configuration files in order of application
        'files' => null, // Names of configuration files in order of application
    );
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('autoloader');

    /**
     * Perform resource initialization
     *
     * @return Zend_Config
     * @throws Rx_Exception
     */
    protected function _init()
    {
        // Prepare list of paths where to look for configuration files
        $paths = $this->_options['paths'];
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        foreach ($paths as $k => $v) {
            $v = trim($v);
            if (!strlen($v)) {
                unset($paths[$k]);
                continue;
            }
            $vv = realpath($v);
            if (!is_dir($vv)) {
                trigger_error('Unavailable path for configuration files: ' . $v);
                unset($paths[$k]);
                continue;
            }
            if (!in_array(substr($vv, -1), array('/', '\\'))) {
                $vv .= '/';
            }
            $paths[$k] = $vv;
        }
        // Prepare list of names of configuration files to load
        $files = $this->_options['files'];
        $files = explode(',', $files);
        foreach ($files as $k => $v) {
            $v = trim($v);
            if (!strlen($v)) {
                unset($files[$k]);
                continue;
            }
            if (defined($v)) // It is possible to define configuration file name as constant
            {
                $v = constant($v);
            }
            $files[$k] = $v;
        }
        $config = null;
        // Load library's default configuration
        $p = realpath(dirname(__FILE__) . '/../../defaults.ini');
        if (!file_exists($p)) {
            throw new Rx_Exception('Unable to find default configuration options for Rx library at ' . $p);
        }
        $config = new Zend_Config_Ini($p, null, array('allowModifications' => true));
        // Load application configuration
        foreach ($paths as $path) {
            foreach ($files as $file) {
                $p = $path . $file . '.ini';
                if (!file_exists($p)) {
                    continue;
                }
                $cfg = new Zend_Config_Ini($p, APPLICATION_ENV, array('allowModifications' => true));
                if ($config) {
                    $config->merge($cfg);
                } else {
                    $config = $cfg;
                }
            }
        }
        $config->setReadOnly();
        Zend_Registry::set('Rx_Config', $config);

        return ($config);
    }

}
