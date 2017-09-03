<?php

class Rx_Application_Resource_Navigation extends Rx_Application_Resource_Abstract implements Rx_Notify_Observer
{

    /**
     * Options for the resource
     *
     * @var array $_options
     */
    protected $_options = array(
        'pages'           => null, // Navigation pages configuration
        'defaultPageType' => null, // Override default type of navigation page
        'configFile'      => null, // Path to navigation configuration file
        'configPath'      => null, // Path within loaded navigation configuration
        'cache'           => null, // true to cache loaded navigation, false to skip caching or string to define name of cache entry
        'registry'        => 'Zend_Navigation', // Name of registry entry to store navigation or empty string to skip storing
    );
    /**
     * Navigation container
     *
     * @var Zend_Navigation $_navigation
     */
    protected $_navigation = null;

    /**
     * Perform resource initialization
     *
     * @return Zend_Navigation
     */
    protected function _init()
    {
        $options = $this->getOptions();
        if ($options['defaultPageType']) {
            Zend_Navigation_Page::setDefaultPageType($options['defaultPageType']);
        }
        if (($options['cache']) && ($this->getBootstrap()->isRegisteredResource('cache'))) {
            $cache = $this->getBootstrap()
                ->bootstrap(array('cache'))
                ->getResource('cache');
            $this->_loadFromCache($cache);
        }
        // If Zend_View is used by application - register additional helper paths
        // for navigation view helpers
        if ($this->getBootstrap()->isRegisteredResource('view')) {
            $view = $this->getBootstrap()
                ->bootstrap(array('view'))
                ->getResource('view');
            if ($view instanceof Zend_View_Abstract) {
                $prefixes = Rx_Loader::getPrefixPath(Zend_View_Helper_Navigation::NS);
                foreach ($prefixes as $prefix => $path) {
                    $view->addHelperPath($path, $prefix);
                }
            }
        }
        if (($options['registry']) && (strlen($options['registry']))) {
            Zend_Registry::set($options['registry'], $this->_getNavigation());
        }
        // If we have MVC ACL resource - use it for applying ACL to navigation pages
        if ($this->getBootstrap()->isRegisteredResource('aclmvc', true)) {
            /* @var $acl Rx_Acl_Mvc */
            $acl = $this->getBootstrap()->getResource('aclmvc', true);
            if ($acl instanceof Rx_Acl_Mvc) {
                Zend_View_Helper_Navigation_HelperAbstract::setDefaultAcl($acl);
                $this->_setAclRole();
                Rx_Notify::subscribe($this, 'rx_user_switched');
            }
        }
        return ($this->_getNavigation());
    }

    /**
     * Get navigation container
     *
     * @return Zend_Navigation
     */
    protected function _getNavigation()
    {
        if (!$this->_navigation) {
            $options = $this->getOptions();
            $pages = $options['pages'];
            if (!is_array($pages)) {
                if ($options['configFile']) {
                    // Resolve path to configuration file
                    $path = Rx_Path::resolve($options['configFile']);
                    if (!Rx_Path::isAbsolute($path)) {
                        if ($this->getBootstrap() instanceof Rx_Bootstrap_Module) {
                            // For module-based bootstrap we should get module root as base path
                            $basePath = $this->getBootstrap()->getResourceLoader()->getBasePath();
                        } else {
                            $basePath = Rx_Path::get('app', true);
                        }
                        $path = Rx_Path::build($basePath, $path, false);
                    }
                    $pages = $this->_loadConfig($path);
                    if (($pages instanceof Zend_Config) && ($options['configPath'])) {
                        $pages = Rx_Config::getConfig($options['configPath'], $pages);
                    }
                } else {
                    $pages = array();
                }
            }
            // Avoid direct adding pages to Zend_Navigation
            // because they will be added using old Zend_Navigation_Container logic
            if ($pages instanceof Zend_Config) {
                $pages = $pages->toArray();
            }
            $this->_navigation = new Zend_Navigation();
            $container = new Rx_Navigation_Page_Container();
            $container->setPages($pages);
            $this->_navigation->setPages($container->getPages());
        }
        return ($this->_navigation);
    }

    /**
     * Load navigation configuration from cache
     *
     * @param Zend_Cache_Core $cache OPTIONAL Cache to load navigation configuration from
     * @return void
     */
    protected function _loadFromCache($cache = null)
    {
        if (!$cache instanceof Zend_Cache_Core) {
            return;
        }
        $options = $this->getOptions();
        $cacheKey = (strlen($options['cache']) > 1) ? $options['cache'] : get_class($this);
        if ($this->getBootstrap() instanceof Rx_Bootstrap_Module) {
            $cacheKey = join('_', array($cacheKey, $this->getBootstrap()->getModuleName()));
        }
        if ($cache->test($cacheKey)) {
            $navigation = $cache->load($cacheKey);
            if ($navigation instanceof Zend_Navigation) {
                $this->_navigation = $navigation;
                return;
            }
        }
        $cache->save($this->_getNavigation(), $cacheKey);
    }

    /**
     * Load navigation configuration from given path
     *
     * @param string $path Path to file with navigation configuration
     * @return Zend_Config
     * @throws Zend_Application_Resource_Exception
     */
    protected function _loadConfig($path)
    {
        if (!file_exists($path)) {
            throw new Zend_Application_Resource_Exception('Failed to load navigation configuration: file not found (' . $path . ')');
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        try {
            switch ($extension) {
                case 'ini':
                    $config = new Zend_Config_Ini($path);
                    break;
                case 'xml':
                    $config = new Zend_Config_Xml($path);
                    break;
                default:
                    throw new Zend_Application_Resource_Exception('Failed to load navigation configuration: unknown type of configuration file (' . $extension . ')');
                    break;
            }
        } catch (Zend_Config_Exception $e) {
            throw new Zend_Application_Resource_Exception('Failed to load navigation configuration: exception occurs (' . $e->getMessage() . ')');
        }
        return ($config);
    }

    /**
     * Handle given notification event
     *
     * @param Rx_Notify_Event $event Notification event object
     * @return void
     */
    public function handleNotify($event)
    {
        if ($event->getType() != 'rx_user_switched') {
            return;
        }
        $this->_setAclRole();
    }

    /**
     * Change ACL role into navigation view helpers
     *
     * @param string|null $role OPTIONAL ACL role to set or null to take current role from Rx_User
     * @return void
     */
    protected function _setAclRole($role = null)
    {
        if ($role === null) {
            if (Rx_User::isExists()) {
                $role = Rx_User::getRole();
            } elseif ($this->getBootstrap()->isExecuted('aclmvc', true)) {
                /* @var $acl Rx_Acl_Mvc */
                $acl = $this->getBootstrap()->getResource('aclmvc', true);
                if ($acl instanceof Rx_Acl_Mvc) {
                    $role = $acl->getDefaultRole();
                }
            }
        }
        Zend_View_Helper_Navigation_HelperAbstract::setDefaultRole($role);
    }

}
