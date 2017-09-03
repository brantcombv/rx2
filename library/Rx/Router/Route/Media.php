<?php

class Rx_Router_Route_Media extends Rx_Router_Route_Base
{
    /**
     * Default mappings for media URLs
     *
     * @var array $_defaultMappings
     */
    protected $_defaultMappings = array(
        'image'  => ':images',
        'script' => ':scripts',
        'style'  => ':styles',
    );
    /**
     * Mapping table for media files that are known to have static URL
     *
     * @var array $_staticUrls
     */
    protected $_staticUrls = array(
        'favicon.ico'                      => 'favicon',
        'apple-touch-icon-precomposed.png' => 'favicon-ios',
        'apple-touch-icon.png'             => 'favicon-ios',
        'robots.txt'                       => 'robots',
        'sitemap.xml'                      => 'sitemap',
        'crossdomain.xml'                  => 'crossdomain',
    );
    /**
     * Mapping table for media files
     *
     * @var array $_map
     */
    protected $_map = array();
    /**
     * Additional configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;

    /**
     * Class constructor
     *
     * @param array $mappings           OPTIONAL Mappings for media files
     * @param array|Zend_Config $config OPTIONAL Additional configuration options
     * @return Rx_Router_Route_Media
     */
    public function __construct($mappings = null, $config = null)
    {
        parent::__construct();
        $this->_config = new Rx_Configurable_Embedded($this, array(
            'missedMediaHandler' => null,
            // MVC parameters for controller that will handle requests to missed media files
            'checkFileExistence' => APPLICATION_DEBUG,
            // true to check if media file is available in filesystem, false to skip check
        ), array(
            'checkConfig' => '_checkConfig',
        ), $config);
        $this->_setMappings($mappings);
    }

    /**
     * Get object's configuration or configuration option with given name
     * If argument is passed as string - value of configuration option with this name will be returned
     * If argument is some kind of configuration options set - it will be merged with current object's configuration and returned
     * If no argument is passed - current object's configuration will be returned
     *
     * @param string|array|Zend_Config|null $config OPTIONAL Option name to get or configuration options
     *                                              to override default object's configuration.
     * @return mixed
     */
    public function getConfig($config = null)
    {
        return ($this->_config->getConfig($config));
    }

    /**
     * Set configuration options for object
     *
     * @param array|string|Zend_Config $config      Configuration options to set
     * @param mixed $value                          If first parameter is passed as string then it will be treated as
     *                                              configuration option name and $value as its value
     * @return void
     */
    public function setConfig($config, $value = null)
    {
        $this->_config->setConfig($config, $value);
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    public function _checkConfig($name, &$value, $operation)
    {
        $valid = true;
        switch ($name) {
            case 'missedMediaHandler':
                if (strlen($value)) {
                    $value = Rx_Url::parse($value, null, true);
                } else {
                    $value = null;
                }
                break;
            case 'checkFileExistence':
                $value = (boolean)$value;
                break;
        }
        return ($valid);
    }

    /**
     * Set mappings for media files
     *
     * @param array $mappings            OPTIONAL Mappings to set,
     *                                   default mappings will be used if not given explicitly
     *                                   Media files mapping entry is defined in a form of:
     *                                   $mask => $url OR $mask => $path OR $mask => array('url'=>$url,'path'=>$path)
     *                                   where:
     *                                   $mask = MVC mask to match media files (e.g. "image.common")
     *                                   $path = Path in filesystem where media files actually resides
     *                                   In a case if no $url is given - $path must point on directory within site root (@see Rx_Path::getSiteRoot())
     *                                   $url  = Base URL to use for constructing URLs to media files
     * @return void
     */
    protected function _setMappings($mappings = null)
    {
        $this->_map = array(
            'paths' => array(),
            'urls'  => array(),
        );
        if (!is_array($mappings)) {
            $mappings = $this->_defaultMappings;
        }
        foreach ($mappings as $mask => $mapping) {
            $path = null;
            $url = null;
            if (is_array($mapping)) {
                if (array_key_exists('path', $mapping)) {
                    $path = $mapping['path'];
                }
                if (array_key_exists('url', $mapping)) {
                    $url = $mapping['url'];
                }
            } elseif ((Rx_Path::isAbsolute($mapping)) ||
                (Rx_Path::isPathReference($mapping))
            ) {
                $path = $mapping;
            } elseif ((is_string($mapping)) && (strlen($mapping))) {
                $url = $mapping;
            } else {
                trigger_error('Invalid mapping value for media files mask: ' . $mask, E_USER_WARNING);
                continue;
            }
            if (($path !== null) && ($url === null)) {
                $path = Rx_Path::normalize($path, true);
                $url = Rx_Path::getUrl($path, null, Rx_Path::SITE);
                if ($url === false) {
                    $url = null;
                }
            } elseif (($url !== null) && ($path === null)) {
                if (parse_url($url, PHP_URL_HOST) === null) {
                    $path = Rx_Path::getPathByUrl($url, null, Rx_Path::SITE);
                    $path = Rx_Path::normalize($path, true);
                }
            }
            if (($url !== null) && (substr($url, -1) != '/')) {
                $url .= '/';
            }
            if (($path === null) && ($url === null)) {
                continue;
            }
            $mask = Rx_Url::parse($mask, null, array('mvc_mask' => true));
            // It is allowed to skip "action" component in mapping definition
            // but we must have it for correct masks building
            $mask = preg_replace('/^(\*\.)\*\.(.+)/', '\1\2.*', $mask);
            if ($path !== null) {
                $this->_map['paths'][$mask] = $path;
            }
            if ($url !== null) {
                $this->_map['urls'][$mask] = $url;
            }
        }
    }

    /**
     * Check request matching for route
     *
     * @param Zend_Controller_Request_Http $request
     * @return array
     */
    public function match($request)
    {
        // If there is no handler for missed media files -
        // there is no meaning to match URLs for media files
        if (!$this->getConfig('missedMediaHandler')) {
            return (false);
        }
        $this->_match($request);
        $params = $this->getConfig('missedMediaHandler');
        $pathinfo = $request->getPathInfo();
        $params['url'] = $pathinfo;
        $staticId = $this->_matchStaticUrl($pathinfo);
        if ($staticId) {
            $params['type'] = Rx_Url::parse('static.' . $staticId, null, array('mvc_mask' => true));
            return ($params);
        }
        // Collect all matched paths, they should be sorted so we will avoid
        // invalid mapped path detection in situation like this:
        // /a/b/
        // /a/b/c/
        $matches = array();
        $pathinfo = Rx_Path::build('~', ltrim($pathinfo, '/'));
        foreach ($this->_map['paths'] as $mask => $path) {
            if (substr($pathinfo, 0, strlen($path)) != $path) {
                continue;
            }
            $matches[$mask] = strlen($path);
        }
        arsort($matches);
        reset($matches);
        $mask = key($matches);
        if (!$mask) {
            return (false);
        }
        $params['type'] = $mask;
        return ($params);
    }

    /**
     * Check if given path is matched to some file that is known to have static URL
     *
     * @param string $url URL to match
     * @return string|null    Id of matched static file or null if no match
     */
    protected function _matchStaticUrl($url)
    {
        $url = trim(strtolower($url));
        $base = Rx_Path::getBaseUrl();
        if (stripos($url, $base) === 0) {
            $url = substr($url, strlen($base));
        }
        if ((strlen($url)) && (array_key_exists($url, $this->_staticUrls))) {
            return ($this->_staticUrls[$url]);
        }
        return (null);
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
        $url = '/';
        $urlPath = null;
        $mvc = array(
            'module'     => Rx_Url::getConfig('default_module'),
            'controller' => Rx_Url::getConfig('default_controller'),
            'action'     => Rx_Url::getConfig('default_action'),
        );
        $target = array();
        foreach ($mvc as $component => $value) {
            $v = '*';
            if (array_key_exists($component, $data)) {
                $target[] = $data[$component];
                if ($data[$component] != $value) {
                    $v = $data[$component];
                }
            }
            $mvc[$component] = $v;
        }
        // Correct MVC definition for targets that have only "action" component defined
        if (preg_match('/^\*\.\*\..+/', join('.', $mvc))) {
            $mvc['controller'] = $mvc['action'];
            $mvc['action'] = '*';
        }
        $target = join('.', $target);
        unset($data['module'], $data['controller'], $data['action']);
        if (isset($data['_rx_url_path'])) {
            $urlPath = $data['_rx_url_path'];
            ltrim($urlPath, '/');
            unset($data['_rx_url_path']);
        }
        $components = array_reverse(array_keys($mvc));
        $matched = null;
        $mask = null;
        foreach ($components as $component) {
            $mask = join('.', $mvc);
            if (array_key_exists($mask, $this->_map['urls'])) {
                $matched = $mask;
                break;
            }
            $mvc[$component] = '*';
        }
        if ($matched) {
            $url = $this->_map['urls'][$matched];
            if (substr($url, -1) != '/') {
                $url .= '/';
            }
            $url .= $urlPath;
            if ($this->getConfig('checkFileExistence')) {
                // Check if file is actually exists in filesystem
                if (array_key_exists($mask, $this->_map['paths'])) {
                    $path = Rx_Path::build($this->_map['paths'][$mask], ltrim($urlPath, '/'));
                    if ((Rx_Path::isPure($path)) && (!is_dir($path))) {
                        trigger_error(
                            'Media router: generated media URL points to non-existing directory (' . $url . ')',
                            E_USER_WARNING
                        );
                    } elseif ((!Rx_Path::isPure($path)) && (!file_exists($path))) {
                        trigger_error(
                            'Media router: generated media URL points to non-existing file (' . $url . ')',
                            E_USER_WARNING
                        );
                    }
                }
            }
            // Add remaining parameters as URL query
            if (sizeof($data)) {
                $url .= (strpos($url, '?') !== false) ? '&' : '?';
                $url .= http_build_query($data);
            }
        } else {
            trigger_error('Media router: unknown media files target: ' . $target, E_USER_WARNING);
        }
        // No urls patching because media files are static
        return ($this->_assemble($url, false, false));
    }

}
