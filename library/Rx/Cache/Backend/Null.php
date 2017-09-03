<?php

/**
 * This "null" cache backend is used in a case if cache is not configured properly in application
 */
class Rx_Cache_Backend_Null extends Zend_Cache_Backend implements Zend_Cache_Backend_Interface
{

    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    public function setDirectives($directives)
    {
        parent::setDirectives($directives);
    }

    public function load($id, $doNotTestCacheValidity = false)
    {
        return (false);
    }

    public function test($id)
    {
        return (false);
    }

    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        return (true);
    }

    public function remove($id)
    {
        return (true);
    }

    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        return (true);
    }

    public function isAutomaticCleaningAvailable()
    {
        return (true);
    }
}
