<?php

abstract class Rx_Configurable_Object extends Rx_Configurable_Abstract
{

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     * @return Rx_Configurable_Object
     */
    public function __construct($config = null)
    {
        // The only purpose of this class is to provide default implementation
        // of configurable class constructor. It is not possible to have constructor
        // directly in Rx_Configurable_Abstract because it will avoid use of Rx_Configurable_Abstract
        // functionality into classes with non-public constructors (e.g. singletons)
        $this->_bootstrapConfig();
        $this->setConfig($config);
    }

}
