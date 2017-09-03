<?php

class Rx_Application_Resource_Debug extends Rx_Application_Resource_Abstract
{

    /**
     * Options for the resource
     *
     * @var array $_options
     */
    protected $_options = array(
        'enabled' => false, // true to enable debug environment changes
        'forced'  => false, // true to force enabling debug environment changes
        // (IP restriction is applied anyway)
        'ips'     => '127.0.0.1', // Comma-separated list of allowed IPs
        'config'  => null, // Path to configuration file to get debug modifications from
        'prefix'  => 'rx.debug', // Prefix for entries of additional configuration file to get as debug modifications
        'modify'  => array(), // List of debug modifications to apply
    );

    /**
     * Perform resource initialization
     *
     * @throws Rx_Exception
     * @return boolean
     */
    protected function _init()
    {
        if ((!$this->getOption('enabled')) || (!$this->isDebugAllowed())) {
            return (false);
        }
        $modifications = $this->getOption('modify', array());
        $config = $this->getOption('config');
        if ($config) {
            $this->getBootstrap()->bootstrap(array('path'));
            $config = Rx_Path::normalize($config);
            if (!file_exists($config)) {
                throw new Rx_Exception('Additional configuration file for debug modifications is not found');
            }
            $class = Rx_Loader::loadPlugin(pathinfo($config, PATHINFO_EXTENSION), 'Zend_Config');
            if (!$class) {
                throw new Rx_Exception('Unable to find configuration loader for additional configuration file for debug modifications');
            }
            try {
                $config = new $class($config, $this->getBootstrap()->getApplication()->getEnvironment());
            } catch (Zend_Config_Exception $e) {
                throw new Rx_Exception('Failed to load additional configuration file for debug modifications: ' . $e->getMessage());
            }
            $m = Rx_Config::getArray($this->getOption('prefix'), $config);
            $modifications = $this->mergeArrays($modifications, $m);
        }
        if (!is_array($modifications)) {
            return (true);
        }
        foreach ($modifications as $name => $value) {
            $method = '_modify' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
            if (!method_exists($this, $method)) {
                trigger_error('No method is defined for applying debug modification: ' . $name, E_USER_WARNING);
            }
            try {
                $result = $this->$method($value);
                if (!$result) {
                    trigger_error('Failed to apply debug modification: ' . $name, E_USER_WARNING);
                }
            } catch (Exception $e) {
                throw new Rx_Exception('Exception occur while applying debug modification: ' . $name . ' (' . $e->getMessage() . ')');
            }
        }
        return (true);
    }

    /**
     * Merge given arrays without merging duplicated scalar values
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected function mergeArrays(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = $this->mergeArrays($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Determine if debug modifications are allowed to be applied
     *
     * @return boolean
     */
    protected function isDebugAllowed()
    {
        // Only allow user change when request came from allowed IPs
        // IP restriction overrides forced enabling of debug modifications
        // to avoid unnecessary problems in production environment
        if (!$this->isAllowedIp()) {
            return (false);
        }
        // If we're forced to use debug modifications - do it
        $options = $this->getOptions();
        if ($options['forced']) {
            return (true);
        }
        // Allow debug modifications if we're running under debugger
        if ($this->isUnderDebugger()) {
            return (true);
        }
        // By default no debug modifications should be applied
        return (false);
    }

    /**
     * Check if request came from IP that is allowed to use debug modifications
     *
     * @return boolean
     */
    protected function isAllowedIp()
    {
        $options = $this->getOptions();
        $ips = $options['ips'];
        $ips = '/^' . strtr($ips, array('.' => '\.', '*' => '.*', ',' => '|', ' ' => '')) . '$/';
        $allowedIp = ((array_key_exists('REMOTE_ADDR', $_SERVER)) &&
            (preg_match($ips, $_SERVER['REMOTE_ADDR'])));
        return ($allowedIp);
    }

    /**
     * Check if request is performed under debugger
     *
     * @return boolean
     */
    protected function isUnderDebugger()
    {
        $debugger = ((array_key_exists('start_debug', $_REQUEST)) &&
            (array_key_exists('original_url', $_REQUEST)));
        return ($debugger);
    }

    /**
     * Check if XMLHttpRequest is performed
     *
     * @return boolean
     */
    protected function isXHR()
    {
        $xhr = ((array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) &&
            ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
        return ($xhr);
    }

    /**
     * Switch user
     *
     * @param mixed $value Value to set
     * @return boolean
     */
    protected function _modifyUser($value)
    {
        $this->getBootstrap()->bootstrap(array('user'));

        // If we already have some user - don't switch it
        if (Rx_User::isExists()) {
            return (true);
        }
        if (!Rx_User::switchUser($value)) {
            return (false);
        }
        return (true);
    }

}
