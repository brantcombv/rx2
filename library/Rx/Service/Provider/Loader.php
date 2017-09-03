<?php

/**
 * Service provider for plugins loader service
 */
class Rx_Service_Provider_Loader extends Rx_Service_Provider_Abstract
{

    /**
     * Create instance of the service
     *
     * @param string $serviceId Service Id to get
     * @param array $params     List of parameters to use for constructing service
     * @return object|null
     */
    protected function createServiceInstance($serviceId, $params)
    {
        return (new Rx_Service_Loader());
    }

}
