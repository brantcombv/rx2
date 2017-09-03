<?php

class Rx_Navigation_Page_Mvc extends Rx_Navigation_Page
{

    /**
     * MVC URL target
     *
     * @var string $_mvc
     */
    protected $_mvc = null;

    /**
     * Action name to use when assembling URL
     *
     * @var string
     */
    protected $_action;

    /**
     * Controller name to use when assembling URL
     *
     * @var string
     */
    protected $_controller;

    /**
     * Module name to use when assembling URL
     *
     * @var string
     */
    protected $_module;

    /**
     * Params to use when assembling URL
     *
     * @see getHref()
     * @var array
     */
    protected $_params = array();

    /**
     * Route name to use when assembling URL
     *
     * @see getHref()
     * @var string
     */
    protected $_route;

    /**
     * Whether params should be reset when assembling URL
     *
     * @see getHref()
     * @var bool
     */
    protected $_resetParams = true;


    /**
     * Whether href should be encoded when assembling URL
     *
     * @see getHref()
     * @var bool
     */
    protected $_encodeUrl = true;

    /**
     * Cached href
     *
     * The use of this variable minimizes execution time when getHref() is
     * called more than once during the lifetime of a request. If a property
     * is updated, the cache is invalidated.
     *
     * @var string
     */
    protected $_hrefCache;

    /**
     * Action helper for assembling URLs
     *
     * @see getHref()
     * @var Zend_Controller_Action_Helper_Url
     */
    protected static $_urlHelper = null;

    // Accessors:

    /**
     * Returns whether page should be considered active or not
     *
     * This method will compare the page properties against the request object
     * that is found in the front controller.
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default is
     *                          false.
     * @return bool             whether page should be considered active or not
     */
    public function isActive($recursive = false)
    {
        if (!$this->_active) {
            $front = Zend_Controller_Front::getInstance();
            $request = $front->getRequest();
            $reqParams = array();
            if ($request) {
                $reqParams = $request->getParams();
                // Fix for ZF-11944
                $reqParams[$request->getModuleKey()] = $request->getModuleName();
                $reqParams[$request->getControllerKey()] = $request->getControllerName();
                $reqParams[$request->getActionKey()] = $request->getActionName();
                if (!array_key_exists('module', $reqParams)) {
                    $reqParams['module'] = $front->getDefaultModule();
                }
            }

            $myParams = $this->_params;

            if ($this->_route) {
                $route = $front->getRouter()->getRoute($this->_route);
                if (method_exists($route, 'getDefaults')) {
                    $myParams = array_merge($route->getDefaults(), $myParams);
                }
            }

            if (null !== $this->_module) {
                $myParams['module'] = $this->_module;
            } elseif (!array_key_exists('module', $myParams)) {
                $myParams['module'] = $front->getDefaultModule();
            }

            if (null !== $this->_controller) {
                $myParams['controller'] = $this->_controller;
            } elseif (!array_key_exists('controller', $myParams)) {
                $myParams['controller'] = $front->getDefaultControllerName();
            }

            if (null !== $this->_action) {
                $myParams['action'] = $this->_action;
            } elseif (!array_key_exists('action', $myParams)) {
                $myParams['action'] = $front->getDefaultAction();
            }

            foreach ($myParams as $key => $value) {
                if ($value == null) {
                    unset($myParams[$key]);
                }
            }

            if (count(array_intersect_assoc($reqParams, $myParams)) ==
                count($myParams)
            ) {
                $this->_active = true;
                return true;
            }
        }

        return parent::isActive($recursive);
    }

    /**
     * Returns href for this page
     *
     * This method uses {@link Zend_Controller_Action_Helper_Url} to assemble
     * the href based on the page's properties.
     *
     * @return string  page href
     */
    public function getHref()
    {
        if ($this->_hrefCache) {
            return $this->_hrefCache;
        }

        if (null === self::$_urlHelper) {
            self::$_urlHelper =
                Zend_Controller_Action_HelperBroker::getStaticHelper('Url');
        }

        $params = $this->getParams();

        if ($param = $this->getModule()) {
            $params['module'] = $param;
        }

        if ($param = $this->getController()) {
            $params['controller'] = $param;
        }

        if ($param = $this->getAction()) {
            $params['action'] = $param;
        }

        $url = self::$_urlHelper->url(
            $params,
            $this->getRoute(),
            $this->getResetParams(),
            $this->getEncodeUrl()
        );

        // Add the fragment identifier if it is set
        $fragment = $this->getFragment();
        if (null !== $fragment) {
            $url .= '#' . $fragment;
        }

        return $this->_hrefCache = $url;
    }

    /**
     * Set MVC URL target
     *
     * @param string $mvc
     * @return Zend_Navigation_Page_Mvc
     */
    public function setMvc($mvc)
    {
        $parsed = Rx_Url::parse($mvc);
        $this->_mvc = Rx_Url::toMvc($parsed);
        $options = array_merge(
            array('params' => $parsed['params']),
            $parsed['mvc'],
            array('route' => $parsed['route'])
        );
        $this->setOptions($options);
        if (!$this->getResource()) {
            $mvc = Rx_Url::toMvc(
                $parsed,
                array(
                    'mvc_prefix' => 'mvc:',
                )
            );
            $this->setResource($mvc);
        }
        return ($this);
    }

    /**
     * Set MVC URL target
     *
     * @return string
     */
    public function getMvc()
    {
        return ($this->_mvc);
    }

    /**
     * Sets action name to use when assembling URL
     *
     * @see getHref()
     *
     * @param  string $action action name
     * @return Zend_Navigation_Page_Mvc   fluent interface, returns self
     * @throws Zend_Navigation_Exception  if invalid $action is given
     */
    public function setAction($action)
    {
        if (null !== $action && !is_string($action)) {
            throw new Zend_Navigation_Exception(
                'Invalid argument: $action must be a string or null');
        }

        $this->_action = $action;
        $this->_hrefCache = null;
        return $this;
    }

    /**
     * Returns action name to use when assembling URL
     *
     * @see getHref()
     *
     * @return string|null  action name
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Sets controller name to use when assembling URL
     *
     * @see getHref()
     *
     * @param  string|null $controller controller name
     * @return Zend_Navigation_Page_Mvc   fluent interface, returns self
     * @throws Zend_Navigation_Exception  if invalid controller name is given
     */
    public function setController($controller)
    {
        if (null !== $controller && !is_string($controller)) {
            throw new Zend_Navigation_Exception(
                'Invalid argument: $controller must be a string or null');
        }

        $this->_controller = $controller;
        $this->_hrefCache = null;
        return $this;
    }

    /**
     * Returns controller name to use when assembling URL
     *
     * @see getHref()
     *
     * @return string|null  controller name or null
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * Sets module name to use when assembling URL
     *
     * @see getHref()
     *
     * @param  string|null $module module name
     * @return Zend_Navigation_Page_Mvc   fluent interface, returns self
     * @throws Zend_Navigation_Exception  if invalid module name is given
     */
    public function setModule($module)
    {
        if (null !== $module && !is_string($module)) {
            throw new Zend_Navigation_Exception(
                'Invalid argument: $module must be a string or null');
        }

        $this->_module = $module;
        $this->_hrefCache = null;
        return $this;
    }

    /**
     * Returns module name to use when assembling URL
     *
     * @see getHref()
     *
     * @return string|null  module name or null
     */
    public function getModule()
    {
        return $this->_module;
    }

    /**
     * Sets params to use when assembling URL
     *
     * @see getHref()
     *
     * @param  array|null $params        [optional] page params. Default is null
     *                                   which sets no params.
     * @return Zend_Navigation_Page_Mvc  fluent interface, returns self
     */
    public function setParams(array $params = null)
    {
        if (null === $params) {
            $this->_params = array();
        } else {
            // TODO: do this more intelligently?
            $this->_params = $params;
        }

        $this->_hrefCache = null;
        return $this;
    }

    /**
     * Returns params to use when assembling URL
     *
     * @see getHref()
     *
     * @return array  page params
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Sets route name to use when assembling URL
     *
     * @see getHref()
     *
     * @param  string $route route name to use when assembling URL
     * @return Zend_Navigation_Page_Mvc   fluent interface, returns self
     * @throws Zend_Navigation_Exception  if invalid $route is given
     */
    public function setRoute($route)
    {
        if (null !== $route && (!is_string($route) || strlen($route) < 1)) {
            throw new Zend_Navigation_Exception(
                'Invalid argument: $route must be a non-empty string or null');
        }

        $this->_route = $route;
        $this->_hrefCache = null;
        return $this;
    }

    /**
     * Returns route name to use when assembling URL
     *
     * @see getHref()
     *
     * @return string  route name
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * Sets whether params should be reset when assembling URL
     *
     * @see getHref()
     *
     * @param  bool $resetParams         whether params should be reset when
     *                                   assembling URL
     * @return Zend_Navigation_Page_Mvc  fluent interface, returns self
     */
    public function setResetParams($resetParams)
    {
        $this->_resetParams = (bool)$resetParams;
        $this->_hrefCache = null;
        return $this;
    }

    /**
     * Returns whether params should be reset when assembling URL
     *
     * @see getHref()
     *
     * @return bool  whether params should be reset when assembling URL
     */
    public function getResetParams()
    {
        return $this->_resetParams;
    }

    /**
     * Sets whether href should be encoded when assembling URL
     *
     * @see getHref()
     *
     * @param boolean $encodeUrl        whether href should be encoded when
     *                                  assembling URL
     * @return Zend_Navigation_Page_Mvc fluent interface, returns self
     */
    public function setEncodeUrl($encodeUrl)
    {
        $this->_encodeUrl = (bool)$encodeUrl;
        $this->_hrefCache = null;

        return $this;
    }

    /**
     * Returns whether herf should be encoded when assembling URL
     *
     * @see getHref()
     *
     * @return bool whether herf should be encoded when assembling URL
     */
    public function getEncodeUrl()
    {
        return $this->_encodeUrl;
    }

    /**
     * Sets action helper for assembling URLs
     *
     * @see getHref()
     *
     * @param  Zend_Controller_Action_Helper_Url $uh URL helper
     * @return void
     */
    public static function setUrlHelper(Zend_Controller_Action_Helper_Url $uh)
    {
        self::$_urlHelper = $uh;
    }

    // Public methods:

    /**
     * Returns an array representation of the page
     *
     * @return array  associative array containing all page properties
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'mvc'          => $this->getMvc(),
                'action'       => $this->getAction(),
                'controller'   => $this->getController(),
                'module'       => $this->getModule(),
                'params'       => $this->getParams(),
                'route'        => $this->getRoute(),
                'reset_params' => $this->getResetParams(),
                'encodeUrl'    => $this->getEncodeUrl(),
            )
        );
    }
}