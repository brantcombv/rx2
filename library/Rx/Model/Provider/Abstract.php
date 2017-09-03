<?php

abstract class Rx_Model_Provider_Abstract
{

    /**
     * Get database adapter to use for model
     *
     * @param string|null $type        Adapter type to get
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Db_Adapter_Abstract
     */
    abstract public function getDbAdapter($type = null, $model = null);

    /**
     * Get prefix for database tables for adapter of given type
     *
     * @param string|null $type        Adapter type to get prefix for
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return string
     */
    abstract public function getDbPrefix($type = null, $model = null);

    /**
     * Get cache object to use for model
     *
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Cache_Core
     */
    abstract public function getCache($model = null);

    /**
     * Get patch for configuration options for given model
     *
     * @param Rx_Model_Abstract $model Model object that requests information
     * @param array $config            Current model configuration options
     * @return array|null
     * @see Rx_Configurable_Abstract#_getConfigPatchProvider()
     */
    abstract public function getConfigPatch($model, $config);

    /**
     * Get logger object of requested type
     *
     * @param string|null $type        OPTIONAL Logger type to get
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Log|null
     */
    abstract public function getLog($type = null, $model = null);

    /**
     * Get ACL object to control permissions for accessing database
     *
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return Zend_Acl|null
     */
    abstract public function getAcl($model = null);

    /**
     * Check if scope limitation should be applied for database queries
     *
     * @param Rx_Model_Abstract $model OPTIONAL Model object that requests information
     * @return boolean
     */
    abstract public function getUseScope($model = null);

}
