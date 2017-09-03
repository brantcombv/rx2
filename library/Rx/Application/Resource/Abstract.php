<?php

/**
 * Base implementation of application resource with support
 * for initialization of dependent resources and with better management of resource's options
 *
 * @method Rx_Bootstrap_Abstract getBootstrap()
 */
abstract class Rx_Application_Resource_Abstract extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = null;
    /**
     * List of additional application resources that this resource depends on
     *
     * @var array $_dependendResoures
     */
    protected $_dependendResoures = array();

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return mixed
     */
    public function init()
    {
        // Perform bootstrap of required resources
        $resources = $this->_bootstrapResources;
        if (!is_array($resources)) {
            $resources = ($resources !== null) ? array($resources) : array();
        }
        if (sizeof($resources)) {
            $this->getBootstrap()->bootstrap($resources);
        }
        // Perform bootstrap of additional resources
        if ((is_array($this->_dependendResoures)) && (sizeof($this->_dependendResoures))) {
            unset($this->_options['depends']);
            $this->getBootstrap()->bootstrap($this->_dependendResoures);
        }
        // Perform resource initialization
        $result = $this->_init();
        return ($result);
    }

    /**
     * Perform resource initialization
     *
     * @return mixed
     */
    abstract protected function _init();

    /**
     * Set comma-separated list of additional application resources
     * that this resource depends on
     *
     * @param string $resources
     * @return void
     */
    protected function setDepends($resources)
    {
        $resources = explode(',', $resources);
        $this->_dependendResoures = array();
        foreach ($resources as $resource) {
            $resource = trim($resource);
            if (!strlen($resource)) {
                continue;
            }
            $this->_dependendResoures[] = $resource;
        }
    }

    /**
     * Get value of application resource option by given name
     * Multi-level option can be obtained by "path": path.to.option.value
     *
     * @param string $name   Name of option to get
     * @param mixed $default OPTIONAL Default value for the option
     * @param array $options OPTIONAL List of resource options to get value from
     * @return mixed
     */
    protected function getOption($name, $default = null, $options = null)
    {
        $result = null;
        $found = true;
        if (!is_array($options)) {
            $options = $this->getOptions();
        }
        $path = explode('.', $name);
        foreach ($path as $p) {
            if ((!is_array($options)) || (!array_key_exists($p, $options))) {
                $found = false;
                break;
            }
            $result = $options[$p];
            if (is_array($options[$p])) {
                $options = $options[$p];
            }
        }
        if ($found) {
            return ($result);
        } else {
            return ($default);
        }
    }

}
