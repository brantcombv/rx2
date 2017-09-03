<?php

class Rx_View_Helper_SharedFile extends Zend_View_Helper_Abstract
{
    /**
     * Insert contents of shared file with given Id
     *
     * @param string|Rx_SharedFiles_Collection $collection Shared files object or identifier, shared file belongs to
     * @param string $id                                   If of shared file to get contents of
     * @return mixed
     */
    public function sharedFile($collection, $id)
    {
        $cId = $collection;
        $collection = Rx_SharedFiles::get($cId, true);
        if (!$collection instanceof Rx_SharedFiles_Collection) {
            trigger_error('Unknown shared files collection Id: ' . $cId, E_USER_ERROR);
            return (null);
        }
        $info = $collection->getFileInfo($id, true);
        if (!$info instanceof Rx_SharedFiles_File) {
            trigger_error('Shared file "' . $id . '" is missed in collection "' . $cId . '"', E_USER_ERROR);
            return (null);
        }
        return ($info->content);
    }
}
