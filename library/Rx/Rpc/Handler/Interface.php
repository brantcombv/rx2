<?php

/**
 * Interface for RPC calls handler classes
 */
interface Rx_Rpc_Handler_Interface
{

    /**
     * Set instance of RPC server that handles request
     *
     * @param Rx_Rpc_Server $server Instance of RPC server
     * @return void
     */
    public function rpcSetServer($server);

    /**
     * Check if given method name is valid RPC method
     *
     * @param string $method    Method name to check
     * @return boolean|string   true if method name is valid as RPC method, false if not
     *                          String return value will be treated as alias name for RPC method
     */
    public function rpcIsValidMethod($method);

    /**
     * Handler for various hook points that occur while handling RPC server requests
     *
     * @param string $hook            Hook point Id (@see Rx_Rpc_Server::HOOK_xxx constants)
     * @param Rx_Rpc_Server $server   RPC server instance
     * @param Rx_Notify_Event $notify Request handling notification event
     * @return boolean                  true to keep request processing, false to break request handling
     */
    public function rpcHookPointsHandler($hook, $server, $notify);

}
