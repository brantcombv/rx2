<?php

/**
 * Application cache initialization resource
 */
class Rx_Application_Resource_Cache extends Rx_Application_Resource_Abstract
{
    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'enabled'  => true, // TRUE to enable cache functionality
        'registry' => 'cache', // Name of registry entry for cache
        'backend'  => array( // Backend options
            'type'    => 'file',
            'options' => array(
                'cache_dir'              => ':cache/',
                'hashed_directory_level' => 2,
                'file_name_prefix'       => 'rx%version%',
                'hashed_directory_perm'  => 0700,
                'cache_file_perm'        => 0600,
            ),
        ),
        'frontend' => array( // Frontend options
            'type'    => 'core',
            'options' => array(
                'lifetime'                => 2592000,
                'write_control'           => false,
                'automatic_serialization' => true,
            ),
        ),
    );
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config', 'path');

    /**
     * Perform resource initialization
     *
     * @throws Rx_Application_Exception
     * @return Zend_Cache_Core
     */
    protected function _init()
    {
        if ($this->getOption('enabled')) {
            // Instantiate and configure cache backend
            $backend = $this->getOption('backend.type');
            if (!class_exists($backend, true)) {
                if ($backend) {
                    $backend = Rx_Loader::loadPlugin($backend, 'Zend_Cache_Backend');
                } else {
                    $backend = 'Zend_Cache_Backend';
                }
            }
            if (!$backend) {
                throw new Rx_Application_Exception('Unable to get cache backend class: ' . $this->getOption('backend.type'));
            }
            $backend = new $backend();
            if (!$backend instanceof Zend_Cache_Backend) {
                throw new Rx_Application_Exception('Сache backend class must be instance of Zend_Cache_Backend');
            }
            $options = $this->getOption('backend.options');
            if (!is_array($options)) {
                $options = array();
            }
            foreach ($options as $k => $v) {
                if (Rx_Path::isPathReference($v)) {
                    // Resolve paths references
                    $v = Rx_Path::normalize($v);
                } elseif (preg_match('/^[0-7]{4}$/', $v)) {
                    // Parse octal values for permissions
                    $r = 0;
                    for ($i = 0; $i < 4; $i++) {
                        $r += (int)substr($v, 3 - $i, 1) * pow(8, $i);
                    }
                    $v = $r;
                } elseif ($k == 'file_name_prefix') {
                    // If we have application version defined - make cache entries
                    // to be dependent on it to avoid problems caused by obsolete cache entries
                    $version = '';
                    if (defined('APPLICATION_VERSION')) {
                        $version = preg_replace('/[^a-zA-Z0-9_]+/', '_', APPLICATION_VERSION);
                    }
                    $v = str_replace('%version%', $version, $v);
                }
                $backend->setOption($k, $v);
            }
            $frontend = $this->getCacheFrontend();
            $frontend->setBackend($backend);
        } else {
            // Cache is disabled, create non-functional cache
            $backend = new Rx_Cache_Backend_Null();
            $frontend = $this->getCacheFrontend();
            $frontend->setBackend($backend);
        }
        Zend_Registry::set($this->getOption('registry', 'cache'), $frontend);

        return ($frontend);
    }

    /**
     * Instantiate and configure cache frontend
     *
     * @return Zend_Cache_Core
     * @throws Rx_Application_Exception
     */
    protected function getCacheFrontend()
    {
        $frontend = $this->getOption('frontend.type', 'core');
        if (!class_exists($frontend, true)) {
            if (strtolower($frontend) == 'core') {
                $frontend = 'Zend_Cache_Core';
            } else {
                $frontend = Rx_Loader::loadPlugin($frontend, 'Zend_Cache_Frontend');
            }
        }
        $frontend = new $frontend();
        if (!$frontend instanceof Zend_Cache_Core) {
            throw new Rx_Application_Exception('Cache frontend class must be instance of Zend_Cache_Core');
        }
        $options = $this->getOption('frontend.options');
        if (!is_array($options)) {
            $options = array();
        }
        foreach ($options as $k => $v) {
            if (Rx_Path::isPathReference($v)) {
                $v = Rx_Path::normalize($v);
            }
            $frontend->setOption($k, $v);
        }
        return $frontend;
    }

}
