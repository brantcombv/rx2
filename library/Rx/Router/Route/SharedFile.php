<?php

class Rx_Router_Route_SharedFile extends Rx_Router_Route_Base
{

    /**
     * Url prefix for shared files
     *
     * @var string $_prefix
     */
    protected $_prefix = null;

    /**
     * Class constructor
     *
     * @param string $prefix OPTIONAL Url prefix for shared files
     */
    public function __construct($prefix = 'shared')
    {
        parent::__construct();
        $this->_prefix = $prefix;
    }

    /**
     * Check request matching for route
     *
     * @param Zend_Controller_Request_Http $request
     * @return array|boolean
     */
    public function match($request)
    {
        $this->_match($request);
        $path = $request->getPathInfo();
        $p = explode('/', ltrim($path, '/'));
        $prefix = array_shift($p);
        if (strtolower($prefix) != $this->_prefix) {
            return (false);
        }
        $collection = array_shift($p);
        $fileId = array_shift($p);
        $hash = array_shift($p);
        $filename = array_shift($p);
        if (!Rx_Uid::isUid($hash)) {
            // No hash is defined
            $filename = $hash;
            $hash = null;
        }
        $params = array(
            $request->getModuleKey()     => Zend_Controller_Front::getInstance()->getDispatcher()->getDefaultModule(),
            $request->getControllerKey() => 'shared',
            $request->getActionKey()     => 'index',
            'collection'                 => $collection,
            'id'                         => $fileId,
            'hash'                       => $hash,
            'filename'                   => $filename,
        );
        return ($params);
    }

    /**
     * Assemble URL from given information
     *
     * @param array $data     Information for url assembling
     * @param boolean $reset  OPTIONAL true to reset url information
     * @param boolean $encode OPTIONAL true to encode resulted url
     * @return string
     */
    public function assemble($data = array(), $reset = false, $encode = false)
    {
        $collection = null;
        $cId = null;
        $fileId = null;
        $hash = null;
        $filename = null;

        // Get shared files collection Id
        if (array_key_exists('collection', $data)) {
            $collection = $data['collection'];
        } elseif (array_key_exists('controller', $data)) // To support compact url format from Rx_Url
        {
            $collection = $data['controller'];
        }

        if (!$collection instanceof Rx_SharedFiles_Collection) {
            $collection = Rx_SharedFiles::get($collection, true);
        }

        if ($collection instanceof Rx_SharedFiles_Collection) {
            $cId = $collection->getId();

            // Get shared file Id
            if (array_key_exists('id', $data)) {
                $fileId = $data['id'];
            } elseif (array_key_exists('action', $data)) // To support compact url format from Rx_Url
            {
                $fileId = $data['action'];
            }

            $info = $collection->getFileInfo($fileId);
            if ($info instanceof Rx_SharedFiles_File) {
                // Get shared file information
                $hash = $info->hash;
                $filename = $info->filename;
            } else {
                trigger_error(
                    'Failed to construct url: unknown shared file Id "' . $fileId . '" in collection "' . $cId . '"',
                    E_USER_WARNING
                );
            }
        } else {
            trigger_error('Failed to construct url: no shared files collection is provided', E_USER_ERROR);
        }
        // Construct url from collected information
        $url = array($this->_prefix);
        if ($cId !== null) {
            $url[] = urlencode($cId);
        }
        if ($fileId !== null) {
            $url[] = urlencode($fileId);
        }
        if ($hash !== null) {
            $url[] = urlencode($hash);
        }
        if ($filename !== null) {
            $url[] = urlencode($filename);
        }
        $url = '/' . join('/', $url);

        return ($this->_assemble($url, false));
    }

}
