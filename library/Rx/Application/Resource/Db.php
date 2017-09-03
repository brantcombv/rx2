<?php

class Rx_Application_Resource_Db extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config', 'cache');

    /**
     * Perform resource initialization
     *
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _init()
    {
        $defaultId = '__default'; // Id of default database connection
        $connections = array();
        $config = Rx_Config::getConfig('db');
        /* @var $value Zend_Config */
        foreach ($config as $name => $value) {
            if (in_array($name, array('adapter', 'params'))) {
                if (!array_key_exists($defaultId, $connections)) {
                    $connections[$defaultId] = array(
                        'adapter' => null,
                        'params'  => array(),
                    );
                }
                if ($name == 'params') {
                    $value = $value->toArray();
                }
                $connections[$defaultId][$name] = $value;
            } else {
                if (!array_key_exists($name, $connections)) {
                    $connections[$name] = array(
                        'adapter' => null,
                        'params'  => array(),
                    );
                }
                $connections[$name]['adapter'] = Rx_Config::get($name . '.adapter', null, $config);
                $connections[$name]['params'] = Rx_Config::getArray($name . '.params', $config);
            }
        }

        // If we have cache - then use it for metadata caching, but with separate prefix
        $cache = null;
        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
            if ($cache instanceof Zend_Cache_Core) {
                $cache = clone($cache);
                $cache->setOption('cache_id_prefix', 'db_');
                Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
                Zend_Registry::set('db.cache', $cache);
            } else {
                $cache = null;
            }
        }
        // Initialize database connections
        foreach ($connections as $name => $info) {
            // If database adapter identifier starts with "rx_" prefix - then we need to use custom adapter
            if (preg_match('/^rx_/i', $info['adapter'])) {
                $info['adapter'] = preg_replace('/^rx_/i', '', $info['adapter']);
                $info['params']['adapterNamespace'] = 'Rx_Db_Adapter';
            }
            $db = Zend_Db::factory($info['adapter'], $info['params']);
            Zend_Registry::set('db.' . $name . '.adapter', $db);
            if ($name == $defaultId) {
                Zend_Db_Table_Abstract::setDefaultAdapter($db);
            }
            // Setup database tables prefix
            if (array_key_exists('prefix', $info['params'])) {
                $prefix = $info['params']['prefix'];
                if ((strlen($prefix)) && (substr($prefix, -1) != '_')) {
                    $prefix .= '_';
                }
                Zend_Registry::set('db.' . $name . '.prefix', $prefix);
            }
        }

        return (Zend_Db_Table_Abstract::getDefaultAdapter());
    }

}
