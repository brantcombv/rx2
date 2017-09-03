<?php

class Rx_Application_Resource_Debugform extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config');

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        $options = null;
        $params = array();
        // Debug form configuration can be selected by "debugform" parameter in request
        $task = (array_key_exists('debugform', $_REQUEST)) ? $_REQUEST['debugform'] : null;
        if ((array_key_exists('start_debug', $_REQUEST)) && (array_key_exists('original_url', $_REQUEST))) {
            // If we're under debugging session - parse original URL
            $p = parse_url($_REQUEST['original_url'], PHP_URL_QUERY);
            parse_str($p, $p);
            if (array_key_exists('debugform', $p)) {
                $task = $p['debugform'];
            }
        }
        if ($task) {
            $_SERVER['REQUEST_METHOD'] = strtoupper(Rx_Config::get('rx.debugform.' . $task . '.method', 'POST'));
            $params = Rx_Config::getArray('rx.debugform.' . $task . '.param');
            foreach ($params as $name => $value) {
                if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                    $_GET[$name] = $value;
                } else {
                    $_POST[$name] = $value;
                }
                $_REQUEST[$name] = $value;
            }
            $cookies = Rx_Config::getArray('rx.debugform.' . $task . '.cookie');
            foreach ($cookies as $name => $value) {
                $_COOKIE[$name] = $value;
            }
            $headers = Rx_Config::getArray('rx.debugform.' . $task . '.header');
            foreach ($headers as $name => $value) {
                $name = strtoupper(str_replace('-', '_', $name));
                $_SERVER[$name] = $value;
            }
            $httpHeaders = Rx_Config::getArray('rx.debugform.' . $task . '.http');
            foreach ($httpHeaders as $name => $value) {
                $name = strtoupper(str_replace('-', '_', 'http_' . $name));
                $_SERVER[$name] = $value;
            }
            if (Rx_Config::get('rx.debugform.' . $task . '.ajax')) {
                $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            }
        }
    }

}
