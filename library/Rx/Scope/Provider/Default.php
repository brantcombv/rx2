<?php

class Rx_Scope_Provider_Default extends Rx_Scope_Provider_Abstract
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        // The only purpose of this class is to provide non-abstract implementation
        // of scope information provider, but without any real functionality
        parent::__construct();
        // Disable scoping since no scoping functionality is implemented
        $this->setDisableScoping(true);
    }

}