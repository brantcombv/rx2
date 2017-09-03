<?php

class Rx_Controller_Plugin_Module extends Zend_Controller_Plugin_Abstract
{

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     * Switches module-dependent resources for applications with multiple modules
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     * @throws Zend_Controller_Exception
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        /* @var $bootstrap Rx_Bootstrap_Abstract */
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        if (!$bootstrap instanceof Rx_Bootstrap_Abstract) {
            throw new Zend_Controller_Exception('Failed to get application bootstrap instance');
        }
        if (!$bootstrap->hasResource('modules')) {
            return;
        }
        // Determine current module name
        // Since preDispatch() method for controller plugins is called
        // BEFORE module name normalization - it is possible to get wrong module
        // name from request in a case if router doesn't provide module name by itself
        // In this case we should fallback to default module name
        $dispatcher = Zend_Controller_Front::getInstance()->getDispatcher();
        $module = $request->getModuleName();
        if (!$dispatcher->isValidModule($module)) {
            $module = $dispatcher->getDefaultModule();
        }
        /* @var $mBootstraps ArrayObject */
        $mBootstraps = $bootstrap->getResource('modules');
        if (!array_key_exists($module, $mBootstraps)) {
            return;
        }
        /* @var $bootstrap Rx_Bootstrap_Module */
        $bootstrap = $mBootstraps[$module];
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (!preg_match('/^switch([a-z0-9]+)/i', $method, $name)) {
                continue;
            }
            $name = $name[1];
            $name = strtolower($name);
            if (($bootstrap->hasPluginResource($name)) &&
                ($bootstrap->isExecuted($name))
            ) {
                $resource = $bootstrap->getResource($name);
                call_user_func(array($this, $method), $resource, $module);
            } else {
                call_user_func(array($this, $method), $module);
            }
        }
    }

    /**
     * Switch view for current module
     *
     * @param Zend_View $view View instance to switch to
     * @param string $module  Current module name
     * @return void
     * @throws Zend_Controller_Exception
     */
    protected function switchView($view, $module)
    {
        if (!$view instanceof Zend_View) {
            throw new Zend_Controller_Exception('View object must be instance of Zend_View');
        }
        /* @var $vr Zend_Controller_Action_Helper_ViewRenderer */
        $vr = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $vr->setView($view);
    }

    /**
     * Switch layout for current module
     *
     * @param Zend_Layout $layout Layout instance to switch to
     * @param string $module      Current module name
     * @return void
     * @throws Zend_Controller_Exception
     */
    protected function switchLayout($layout, $module)
    {
        if (!$layout instanceof Zend_Layout) {
            throw new Zend_Controller_Exception('Layout object must be instance of Zend_Layout');
        }
        // Since it is not possible to change MVC layout instance directly in Zend_Layout -
        // we will move settings of given layout object into MVC layout instance and register it
        // in layout's helper and plugin
        $mvcLayout = Zend_Layout::getMvcInstance();
        if ($mvcLayout !== $layout) {
            $mvcLayout->setContentKey($layout->getContentKey());
            $mvcLayout->setHelperClass($layout->getHelperClass());
            $mvcLayout->setInflector($layout->getInflector());
            $mvcLayout->setInflectorTarget($layout->getInflectorTarget());
            $mvcLayout->setLayout($layout->getLayout());
            $mvcLayout->setLayoutPath($layout->getLayoutPath());
            $mvcLayout->setMvcSuccessfulActionOnly($layout->getMvcSuccessfulActionOnly());
            $mvcLayout->setPluginClass($layout->getPluginClass());
            $mvcLayout->setView($layout->getView());
            $mvcLayout->setViewBasePath($layout->getViewBasePath());
            $mvcLayout->setViewScriptPath($layout->getViewScriptPath());
            $mvcLayout->setViewSuffix($layout->getViewSuffix());
        }
        if (Zend_Controller_Action_HelperBroker::hasHelper('layout')) {
            /* @var $hLayout Zend_Layout_Controller_Action_Helper_Layout */
            $hLayout = Zend_Controller_Action_HelperBroker::getExistingHelper('layout');
            $hLayout->setLayoutInstance($mvcLayout);
        }
        $pClass = $mvcLayout->getPluginClass();
        if (Zend_Controller_Front::getInstance()->hasPlugin($pClass)) {
            /* @var $pLayout Zend_Layout_Controller_Plugin_Layout */
            $pLayout = Zend_Controller_Front::getInstance()->getPlugin($pClass);
            $pLayout->setLayout($mvcLayout);
        }
    }

}
