<?php

class Rx_SharedFiles_Provider_Default extends Rx_SharedFiles_Provider_Abstract
{

    /**
     * Get information about shared file with given name from shared files collection
     *
     * @param string $id Shared file Id to get information for
     * @return Rx_SharedFiles_File|null
     */
    public function getFileInfo($id)
    {
        return (null);
    }

    /**
     * Get contents of shared file
     *
     * @param Rx_SharedFiles_File $info Shared file information structure to get file contents for
     * @return mixed|null
     */
    public function getFileContent($info)
    {
        return (null);
    }

    /**
     * Determine if information of shared file with given Id can be stored in cache
     *
     * @param Rx_SharedFiles_File|string $info Shared file Id or information structure to check
     * @return boolean          true if file can be stored, false if it should be requested from provider
     */
    public function isCacheable($id)
    {
        return (true);
    }

}
