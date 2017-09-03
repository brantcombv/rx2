<?php

class Rx_Application_Resource_Debugrpc extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config', 'rpc');

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // Debug RPC configuration can be selected by "debugrpc" parameter in request
        $task = (array_key_exists('debugrpc', $_REQUEST)) ? $_REQUEST['debugrpc'] : null;
        if ((array_key_exists('start_debug', $_REQUEST)) && (array_key_exists('original_url', $_REQUEST))) {
            // If we're under debugging session - parse original URL
            $p = parse_url($_REQUEST['original_url'], PHP_URL_QUERY);
            parse_str($p, $p);
            if (array_key_exists('debugrpc', $p)) {
                $task = $p['debugrpc'];
            }
        }
        if ($task) {
            /* @var $rpc Rx_Rpc_Server */
            $rpc = $this->getBootstrap()->getResource('rpc');
            $rpc->getRequest()
                ->setMethod(Rx_Config::get('rx.debugrpc.' . $task . '.method'))
                ->setParams(Rx_Config::getArray('rx.debugrpc.' . $task . '.param'));
        }
    }

}
