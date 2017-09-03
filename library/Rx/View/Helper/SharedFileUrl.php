<?php

class Rx_View_Helper_SharedFileUrl extends Zend_View_Helper_Abstract
{
    /**
     * Render URL for shared file with given Id
     *
     * @param string|Rx_SharedFiles_Collection $collection Shared files object or identifier, shared file belongs to
     * @param string $id                                   If of shared file to render URL for
     * @return string
     */
    public function sharedFileUrl($collection, $id)
    {
        return (Rx_Url::url(
            'shared:',
            array(
                'collection' => $collection,
                'id'         => $id,
            )
        ));
    }
}
