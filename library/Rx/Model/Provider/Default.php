<?php

class Rx_Model_Provider_Default extends Rx_Model_Provider_Abstract
{

    /**
     * Cache of already used database adapters information
     *
     * @var array $_adapters
     */
    protected $_adapters = array();

    /**
     * Get database adapter information by given adapter type
     *
     * @param string|null $type        Adapter type to get
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return array
     * @throws Rx_Model_Exception
     */
    protected function _getAdapterInfo($type, $model = null)
    {
        if ($type === null) {
            $type = '__default'; // @see Rx_Application_Resource_Db#init();
        }
        if (!array_key_exists($type, $this->_adapters)) {
            $this->_adapters[$type] = array(
                'adapter' => null,
                'prefix'  => null,
                'cache'   => null,
            );
            $adapter = null;
            if (!Zend_Registry::isRegistered('db.' . $type . '.adapter')) {
                throw new Rx_Model_Exception('No database adapter is found for database connection: ' . $type);
            }
            $adapter = Zend_Registry::get('db.' . $type . '.adapter');
            if (!$adapter instanceof Zend_Db_Adapter_Abstract) {
                throw new Rx_Model_Exception('Database adapter for database connection: "' . $type . '" must be instance of Zend_Db_Adapter_Abstract');
            }
            $this->_adapters[$type]['adapter'] = $adapter;
            if (Zend_Registry::isRegistered('db.' . $type . '.prefix')) {
                $this->_adapters[$type]['prefix'] = Zend_Registry::get('db.' . $type . '.prefix');
            }
            $cache = null;
            if (Zend_Registry::isRegistered('db.' . $type . '.cache')) {
                $cache = Zend_Registry::get('db.' . $type . '.cache');
            } elseif (Zend_Registry::isRegistered('db.cache')) {
                $cache = Zend_Registry::get('db.cache');
            }
            if (!$cache instanceof Zend_Cache_Core) {
                throw new Rx_Model_Exception('Cache adapter for database connection: "' . $type . '" must be instance of Zend_Cache_Core');
            }
            $this->_adapters[$type]['cache'] = $cache;
        }
        return ($this->_adapters[$type]);
    }


    /**
     * Get database adapter to use for model
     *
     * @param string|null $type        Adapter type to get
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Db_Adapter_Abstract
     * @throws Rx_Model_Exception
     */
    public function getDbAdapter($type = null, $model = null)
    {
        $info = $this->_getAdapterInfo($type, $model);
        return ($info['adapter']);
    }

    /**
     * Get prefix for database tables for adapter of given type
     *
     * @param string|null $type        Adapter type to get prefix for
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return string
     * @throws Rx_Model_Exception
     */
    public function getDbPrefix($type = null, $model = null)
    {
        $info = $this->_getAdapterInfo($type, $model);
        return ($info['prefix']);
    }

    /**
     * Get cache object to use for model
     *
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Cache_Core
     * @throws Rx_Model_Exception
     */
    public function getCache($model = null)
    {
        $info = $this->_getAdapterInfo($type, $model);
        return ($info['cache']);
    }

    /**
     * Get patch for configuration options for given model
     *
     * @param Rx_Model_Abstract $model Model object that requests information
     * @param array $config            Current model configuration options
     * @return array|null
     * @see Rx_Configurable_Abstract#_getConfigPatchProvider()
     */
    public function getConfigPatch($model, $config)
    {
        return (null);
    }

    /**
     * Get logger object of requested type
     *
     * @param string|null $type        OPTIONAL Logger type to get
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Log|null
     */
    public function getLog($type = null, $model = null)
    {
        return (null);
    }

    /**
     * Get ACL object to control permissions for accessing database
     *
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Acl|null
     */
    public function getAcl($model = null)
    {
        return (null);
    }

    /**
     * Check if scope limitation should be applied for database queries
     *
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return boolean
     */
    public function getUseScope($model = null)
    {
        return (false);
    }

}
