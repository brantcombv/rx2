<?php

class Rx_SharedFiles_File extends Rx_Struct_Abstract
{
    /**
     * Mapping table for determining MIME type of file by its extension
     *
     * @var array $_mimeMap
     */
    protected $_mimeMap = array(
        'txt'  => 'text/plain',
        'html' => 'text/html',
        'htm'  => 'text/html',
        'php'  => 'text/html',
        'xml'  => 'application/xml',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'swf'  => 'application/x-shockwave-flash',
        'zip'  => 'application/zip',
    );

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'id'         => null, // Shared file identifier
            'path'       => null, // Path to shared file in local filesystem
            'filename'   => null, // File name
            'mtime'      => null, // Shared file modification time
            'hash'       => null, // Hash for file contents
            'mime'       => null, // MIME type of file contents
            'size'       => null, // File size
            'content'    => null, // Shared file contents
            'properties' => array(), // Additional properties for shared file
        );
    }

    /**
     * Actual implementation of setting structure field value.
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name  Structure element name to set value of
     * @param mixed $value  New value for this element
     * @param array $config Configuration options
     * @return void
     */
    protected function _set($name, $value, $config)
    {
        switch ($name) {
            case 'properties':
                if (!is_array($value)) {
                    $value = array();
                }
                break;
            default:
                break;
        }
        parent::_set($name, $value, $config);
    }

    /**
     * Set path to shared file and re-calculate related properties
     *
     * @param string $path                   Path to shared file
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function setPath($path, $config = null)
    {
        $config = $this->getConfig($config);
        // To avoid file hash re-calculation every time (since it may be relatively expensive operation)
        // file properties are re-calculated only if:
        // - File path was changed
        // - File size or modification time was changed
        if (($path != $this->_get('path', null, $config)) ||
            ((file_exists($path)) &&
                ((filesize($path) != $this->_get('size', null, $config)) ||
                    (filemtime($path) != $this->_get('mtime', null, $config))))
        ) {
            $this->set(array(
                'path'       => $path,
                'filename'   => basename($path),
                'mtime'      => filemtime($path),
                'hash'       => Rx_Uid::getUid(md5_file($path), true),
                'mime'       => $this->_getMimeType($path, $config),
                'size'       => filesize($path),
                'content'    => null,
                // Remove embedded content of shared file since content is now accessible by path
                'properties' => $this->_getFileProperties($path, $config),
            ), null, $config);
        }
    }

    /**
     * Set content of shared file and re-calculate related properties
     *
     * @param mixed $content                 Shared file content
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return void
     */
    public function setContent($content, $config = null)
    {
        $config = $this->getConfig($config);
        // We should convert non-string content into string to be able to calculate its hash
        if (!is_string($content)) {
            if ($content === true) {
                $content = 'true';
            } elseif ($content === false) {
                $content = 'false';
            } elseif ($content === null) {
                $content = 'null';
            } elseif ((is_int($content)) || (is_float($content))) {
                $content = (string)$content;
            } elseif (is_array($content)) {
                $content = serialize($content);
            } elseif (is_object($content)) {
                if (is_callable(array($content, 'toString'))) {
                    $content = $content->toString();
                } elseif (is_callable(array($content, 'toArray'))) {
                    $content = serialize($content->toArray());
                } else {
                    $content = serialize($content);
                }
            }
        }
        // getUid() should be calculated from md5() hash from content
        // because of use of md5_file() function for calculating hash from file content
        // Otherwise we can get different hashes if same content is being assigned
        // from file and directly
        $hash = Rx_Uid::getUid(md5($content), true);
        $this->set(array(
            'content' => null, // Don't set content itself since it should be stored separately
            'path'    => null, // Since content is set directly - no path is available
            'size'    => strlen($content),
            'hash'    => $hash,
            'mtime'   => time(),
        ), null, $config);
    }

    /**
     * Get MIME type for file by given path
     *
     * @param string $path  Path to file to get MIME type for
     * @param array $config Configuration options
     * @return string
     */
    protected function _getMimeType($path, $config)
    {
        // Use fileinfo extension functionality if it is available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_resource($finfo)) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (strlen($mime)) {
                    return ($mime);
                }
            }
        }
        // If no filefino extension is available or there was some problem
        // with using it - try to use other methods of determining MIME types
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            if (strlen($mime)) {
                return ($mime);
            }
        }
        // Last chance - use embedded MIME types mapping table
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (array_key_exists($ext, $this->_mimeMap)) {
            return ($this->_mimeMap[$ext]);
        }
        // No MIME type was found - return default MIME type
        return ('application/octet-stream');
    }

    /**
     * Get additional properties of shared file
     *
     * @param string $path  Path to file to properties for
     * @param array $config Configuration options
     * @return array
     */
    protected function _getFileProperties($path, $config)
    {
        $properties = array();
        // If given file is an image - return its dimensions as additional properties
        $info = getimagesize($path);
        if (is_array($info)) {
            $properties['width'] = $info[0];
            $properties['height'] = $info[1];
        }
        return ($properties);
    }

}
