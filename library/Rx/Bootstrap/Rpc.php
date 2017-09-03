<?php

class Rx_Bootstrap_Rpc extends Rx_Bootstrap_Abstract
{

    /**
     * Class constructor
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return Rx_Bootstrap_Rpc
     * @throws Zend_Application_Bootstrap_Exception When invalid application is provided
     */
    public function __construct($application)
    {
        parent::__construct($application);
        if (!$this->isRegisteredResource('rpc')) {
            $this->registerPluginResource('rpc');
        }
    }

    /**
     * Run the application
     *
     * @return void
     */
    public function run()
    {
        // Use direct access to $_SERVER because we have no Zend_Controller_Request
        $isPost = (array_key_exists('REQUEST_METHOD', $_SERVER)) ? ($_SERVER['REQUEST_METHOD'] == 'POST') : false;
        /* @var $rpc Rx_Rpc_Server */
        $rpc = $this->getResource('rpc');
        if ((!$isPost) && (!$rpc->haveRequest())) {
            // GET requests should be treated as requests to SMD
            $options = $this->getPluginResource('rpc')->getOptions();
            if ($options['smd']['show']) {
                // Configure SMD with parameters from RPC resource configuration
                $smd = $rpc->getServiceMap();
                // We need to show SMD - determine its parameters
                $target = $smd->getTarget();
                if ($options['smd']['target']) {
                    $target = Rx_Path::getUrl(Rx_Path::normalize($options['smd']['target']));
                }
                if (!$target) {
                    // Target URL is not specified in configuration, try to take it from $_SERVER
                    $uri = (array_key_exists('REQUEST_URI', $_SERVER)) ? $_SERVER['REQUEST_URI'] : null;
                    if ($uri) {
                        $uri = explode('?', $uri, 2);
                        $uri = array_shift($uri);
                    }
                    $target = (strlen($uri)) ? $uri : null;
                }
                if ($target) {
                    $smd->setTarget($target);
                }
                if ($options['smd']['v2']) {
                    $smd->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);
                }
                $smd = $smd->toJson();
                // Output SMD
                header('Content-Type: application/json');
                header('Content-Length: ' . strlen($smd));
                echo $smd;
                return;
            }
        }
        // Proceed with standard handling of normal JSON-RPC requests
        $rpc->handle();
    }

}