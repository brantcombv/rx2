<?php

class Rx_Rpc_Smd extends Zend_Json_Server_Smd
{

    /**
     * Cast to array
     *
     * @return array
     */
    public function toArray()
    {
        $service = parent::toArray();
        unset($service['services']); // Avoid duplicated description of JSON-RPC server methods
        return ($service);
    }

}