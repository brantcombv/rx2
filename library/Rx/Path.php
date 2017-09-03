<?php

/**
 * Provides singleton for various paths management functionality
 */
class Rx_Path
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Path $_instance
     */
    protected static $_instance = null;
    /**
     * Path to root directory of the server where application is installed
     *
     * @var string $_serverRoot
     */
    protected $_serverRoot = null;
    /**
     * Path to root directory of site (e.g. document root)
     *
     * @var string $_siteRoot
     */
    protected $_siteRoot = null;
    /**
     * Registered named paths
     *
     * @var array $_paths
     */
    protected $_paths = array();
    /**
     * Base URL of site (actually mapping of either $_serverRoot or $_siteRoot to base URL in server configuration)
     *
     * @var string $_baseUrl
     */
    protected $_baseUrl = null;
    /**
     * Type of base URL mapping (if paths should be calculated against server or site root)
     *
     * @var int $_baseUrlType
     */
    protected $_baseUrlType = null;

    const ABSOLUTE = 1;
    const SERVER = 2;
    const SITE = 3;

    /**
     *
     */
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Path
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Set server root to calculate paths from.
     * Server root is path to directory where whole server information resides
     *
     * @param string $path Path to server root
     * @return void
     */
    public static function setServerRoot($path)
    {
        self::getInstance()->_serverRoot = self::normalize($path, false);
    }

    /**
     * Get server root path
     *
     * @return string
     */
    public static function getServerRoot()
    {
        return (self::getInstance()->_serverRoot);
    }

    /**
     * Set site root to calculate paths from.
     * Site root is path to directory where site's document root resides
     *
     * @param string $path Path to site root
     * @return void
     */
    public static function setSiteRoot($path)
    {
        self::getInstance()->_siteRoot = self::normalize($path, false);
    }

    /**
     * Get site root path
     *
     * @return string
     */
    public static function getSiteRoot()
    {
        return (self::getInstance()->_siteRoot);
    }

    /**
     * Determine if script is running under Unix-like or Windows-like OS.
     * Paths handling differs a bit between them
     *
     * @return boolean
     */
    public static function isUnix()
    {
        static $isUnix = null;

        if ($isUnix === null) {
            $win = (strcasecmp(substr(php_uname(), 0, 7), 'windows') == 0);
            $isUnix = !$win;
        }
        return ($isUnix);
    }

    /**
     * Check if given path is actually a path reference
     *
     * @param string $path Path to check
     * @return boolean
     */
    public static function isPathReference($path)
    {
        return (in_array(substr($path, 0, 1), array('^', '~', ':')));
    }

    /**
     * Check if given path is actually an URL
     *
     * @param string $path Path to check
     * @return boolean
     */
    public static function isUrl($path)
    {
        return (preg_match('/^([a-z][a-z0-9\-\+]+):\/\//i', $path));
    }

    /**
     * Check if given path is pure (points to directory) or not (possibly points to file)
     *
     * @param string $path Path to check
     * @return boolean
     */
    public static function isPure($path)
    {
        return (in_array(substr($path, -1), array('\\', '/')));
    }

    /**
     * Resolve path markers into given path
     *
     * @param string $path Path to resolve
     * @return string
     */
    public static function resolve($path)
    {
        if (!self::isPathReference($path)) {
            return ($path);
        }
        $root = null;
        $marker = substr($path, 0, 1);
        // Check if we have pure path or not
        $pure = self::isPure($path);
        switch ($marker) {
            case '^':
                $root = self::getServerRoot();
                $path = substr($path, 1);
                break;
            case '~':
                $root = self::getSiteRoot();
                $path = substr($path, 1);
                break;
            case ':':
                $t = str_replace("\\", '/', $path);
                $t = explode('/', $t, 2);
                $n = substr(array_shift($t), 1);
                $p = self::get($n, true);
                $t = array_shift($t);
                $path = self::build($p, $t, $pure);
                break;
        }
        if ($root) {
            // Remove leading slash from path to avoid error in a case of paths like: ~/path
            // which will became absolute paths on Unix after removing marker
            $path = ltrim($path, '/');
            // We must not force full paths to be pure (because they can be paths to files)
            $path = self::build($root, $path, $pure);
        }
        return ($path);
    }

    /**
     * Register named path, will be accessible globally by its name
     * Path types can be also defined with special path type markers:
     *  - path      - Either absolute path or path with default type
     *  - ^path     - Path calculated from server root
     *  - ~path     - Path calculated from site root
     *
     * @param string $name Name of the path to register
     * @param string $path Path, relative to one of base paths (either server root or site root)
     * @param int $type    OPTIONAL Path type (one of class constants for path types)
     * @return boolean
     */
    public static function register($name, $path, $type = null)
    {
        $instance = self::getInstance();
        $path = $instance->resolve($path);
        if ($type === null) {
            $type = ($instance->isAbsolute($path)) ? self::ABSOLUTE : self::SITE;
        }
        switch ($type) {
            case self::ABSOLUTE:
                $root = '';
                break;
            case self::SERVER:
                $root = $instance->_serverRoot;
                break;
            case self::SITE:
                $root = $instance->_siteRoot;
                break;
            default:
                trigger_error('Unknown named path type (' . $type . ')', E_USER_WARNING);
                $root = $instance->_siteRoot;
                break;
        }
        // If we got relative path as full path - then we must normalize it
        if (($root == '') && (!$instance->isAbsolute($path))) {
            $path = $instance->normalize($path, false);
        }
        // Check if we have pure path or not
        $t = str_replace("\\", '/', $path);
        $pure = (substr($t, -1) == '/');
        // We must not force full paths to be pure (because they can be paths to files)
        $path = $instance->build($root, $path, $pure);
        if ((!strlen($path)) ||
            ((!self::isUnix()) && (!preg_match('/^[a-z]:[\/\x5c]/i', $path))) ||
            ((self::isUnix()) && (substr($path, 0, 1) != '/'))
        ) {
            trigger_error('Failed to register path (name: ' . $name . ', path: ' . $path . ')', E_USER_WARNING);
            return (false);
        }
        $instance->_paths[$name] = $path;
        return (true);
    }

    /**
     * Magic function so that $obj->name = $value will work.
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->register($name, $value);
    }

    /**
     * Check if named path with given name is registered
     *
     * @param string $name Path name to check
     * @return boolean
     */
    public static function isRegistered($name)
    {
        return (array_key_exists($name, self::getInstance()->_paths));
    }

    /**
     * Get registered named path
     *
     * @param string $name,... Name of a path to retrieve or path in a form pathName(/pathComponent)+
     * @param boolean $pure    OPTIONAL TRUE to build pure path, FALSE if path contain filename
     * @return string|null
     */
    public static function get($name, $pure = null)
    {
        $parts = array();
        $pure = null;
        if (strpos($name, '/') !== false) {
            $t = explode('/', $name, 2);
            $name = array_shift($t);
            $parts = $t;
        } elseif (strpos($name, '\\') !== false) {
            $t = explode('\\', $name, 2);
            $name = array_shift($t);
            $parts = $t;
        }
        $instance = self::getInstance();
        if (!array_key_exists($name, $instance->_paths)) {
            trigger_error('Requested named path is not registered yet (name: ' . $name . ')', E_USER_WARNING);
            return (null);
        }
        $p = $instance->_paths[$name];
        $args = func_get_args();
        array_shift($args);
        // If "pure path" flag is explicitly defined - we must use it
        if ((sizeof($args)) && (is_bool($args[sizeof($args) - 1]))) {
            $pure = array_pop($args);
        }
        if (sizeof($args)) {
            $parts = $args;
        }
        if ($pure) {
            array_push($parts, $pure);
        }
        if (!sizeof($parts)) {
            return ($p);
        }
        array_unshift($parts, $p);
        return (call_user_func_array(array(__CLASS__, 'build'), $parts));
    }

    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return string|null
     */
    public function __get($name)
    {
        return ($this->get($name));
    }

    /**
     * Register base URL to use for URLs calculation
     * Base URL type can be also defined with special path type markers:
     *  - url       - Default URL type or type is passed as argument
     *  - ^url      - URL type: self::SERVER
     *  - ~url      - URL type: self::SITE
     *
     * @param string $url Base URL
     * @param int $type   OPTIONAL Default path type to use for URLs calculation (can be either self::SERVER ot self::SITE)
     * @return boolean
     */
    public static function setBaseUrl($url = '/', $type = null)
    {
        $_type = self::SITE;
        $marker = substr($url, 0, 1);
        switch ($marker) {
            case '^':
                $_type = self::SERVER;
                $url = substr($url, 1);
                break;
            case '~':
                $_type = self::SITE;
                $url = substr($url, 1);
                break;
        }
        if ($type === null) {
            $type = $_type;
        }
        if (!in_array($type, array(self::SERVER, self::SITE))) {
            trigger_error(
                'Invalid URL path type, must be either ' . __CLASS__ . '::SERVER or ' . __CLASS__ . '::SITE',
                E_USER_WARNING
            );
            return (false);
        }
        if (substr($url, -1) != '/') {
            $url .= '/';
        }
        self::getInstance()->_baseUrl = $url;
        self::getInstance()->_baseUrlType = $type;
        return (true);
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public static function getBaseUrl()
    {
        return (self::getInstance()->_baseUrl);
    }

    /**
     * Build URL from given path
     *
     * @param string $path    Path to build URL from
     * @param string $baseUrl OPTIONAL Base URL to use for url building
     * @param int $urlType    OPTIONAL URL type to use for URLs calculation
     * @return string|boolean
     */
    public static function getUrl($path, $baseUrl = null, $urlType = null)
    {
        $instance = self::getInstance();
        if ($baseUrl === null) {
            $baseUrl = $instance->_baseUrl;
        }
        if ($urlType === null) {
            $urlType = $instance->_baseUrlType;
        }
        if (substr($baseUrl, -1) != '/') {
            $baseUrl .= '/';
        }
        switch ($urlType) {
            case self::SERVER:
                $root = $instance->_serverRoot;
                break;
            case self::SITE:
            default:
                $root = $instance->_siteRoot;
                break;
        }
        $path = $instance->normalize($path);
        $url = $baseUrl;
        $regexp = '/^' . preg_quote($root, '/') . '/' . (($instance->isUnix()) ? '' : 'i');
        if (!preg_match($regexp, $path)) {
            // Path doesn't resides within available root path so we can't construct URL for it
            trigger_error('Given path "' . $path . '" doesn\'t resides within root path: ' . $root, E_USER_WARNING);
            return (false);
        }
        $path = preg_replace($regexp, '', $path);
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        $url .= $path;
        return ($url);
    }

    /**
     * Get path for given URL
     *
     * @param string $url     URL to to build path from
     * @param string $baseUrl OPTIONAL Base URL to use for path building
     * @param int $urlType    OPTIONAL URL type to use for calculation
     * @return string|boolean
     */
    public static function getPathByUrl($url, $baseUrl = null, $urlType = null)
    {
        $instance = self::getInstance();
        if ($baseUrl === null) {
            $baseUrl = $instance->_baseUrl;
        }
        if ($urlType === null) {
            $urlType = $instance->_baseUrlType;
        }
        if (substr($baseUrl, -1) == '/') {
            $baseUrl = substr($baseUrl, 0, -1);
        }
        switch ($urlType) {
            case self::SERVER:
                $root = $instance->_serverRoot;
                break;
            case self::SITE:
            default:
                $root = $instance->_siteRoot;
                break;
        }
        if (preg_match('/^([a-z0-9\-\+]+):\/\//i', $url)) {
            $p = parse_url($url);
            if (isset($p['path'])) {
                $url = $p['path'];
            } else {
                trigger_error('Unable to parse given url: ' . $url, E_USER_WARNING);
                return (false);
            }
        }
        if (substr($url, 0, 1) != '/') {
            trigger_error('Only absolute urls can be converted into paths', E_USER_WARNING);
            return (false);
        }
        if ((strlen($baseUrl)) && (!preg_match('/^' . preg_quote($baseUrl, '/') . '/', $url))) {
            trigger_error('Given url (' . $url . ') don\'t belong to base url: ' . $baseUrl, E_USER_WARNING);
            return (false);
        }
        $url = preg_replace('/^' . preg_quote($baseUrl, '/') . '/', '', $url);
        if (substr($url, 0, 1) == '/') {
            $url = substr($url, 1);
        }
        $path = self::build($root, $url);
        return ($path);
    }

    /**
     * Check, if given path is absolute (i.e. starts from filesystem root)
     *
     * @param string $path Path to check
     * @return boolean
     */
    static public function isAbsolute($path)
    {
        return ((((!self::isUnix()) && (preg_match('/^[a-z]:[\/\x5c]/i', $path))) || ((self::isUnix()) && (strlen(
                        $path
                    ) > 0) && ($path[0] == '/'))));
    }

    /**
     * Build path from given path parts
     *
     * @param string $path,...  Path components
     * @param boolean $pure     OPTIONAL true for pure paths (without filename),
     *                          false for paths with filename,
     *                          null to auto-detect path type
     * @return string|boolean
     */
    static public function build($path, $pure = null)
    {
        $args = func_get_args();
        if (is_bool($args[sizeof($args) - 1])) {
            // If "pure path" flag is explicitly defined - we must use it
            $pure = array_pop($args);
        } else {
            // Not a pure path by default
            $pure = false;
            // If last element in given path components is a pure path then resulted path must also be pure
            $t = end($args);
            if ($t === null) {
                array_pop($args);
                $t = end($args);
            }
            $t = str_replace("\\", '/', $t);
            if (substr($t, -1) == '/') {
                $pure = true;
            }
        }
        $instance = self::getInstance();
        $parts = array();
        $prefix = '';
        $v = false;
        foreach ($args as $path) {
            $tp = null;
            $path = $instance->resolve($path);
            if (self::isAbsolute($path)) {
                if (self::isUnix()) {
                    $tp = '/';
                    $path = substr($path, 1);
                } else {
                    $tp = substr($path, 0, 2) . '/';
                    $path = str_replace("\\", '/', substr($path, 3));
                }
            }
            if ($tp !== null) {
                if ($prefix !== '') {
                    trigger_error(
                        'Attempt to join several non-relative paths together (Given path components: ' . join(
                            ' | ',
                            $args
                        ) . ')',
                        E_USER_WARNING
                    );
                    return (false);
                } else {
                    $prefix = $tp;
                }
            }
            $p = explode('/', $path);
            foreach ($p as $part) {
                if ($part == '') {
                    continue;
                } elseif (($part == '..') && ($v)) {
                    // We need it to allow paths like: ../../path/to/dir/,
                    // but not allow: /path/to/this/../another/dir/
                    array_pop($parts);
                } elseif (($part == '..') && (!$v)) {
                    $parts[] = $part;
                } elseif ($part == '.') {
                    // There is nothing to do about it, skip
                } else {
                    $parts[] = $part;
                    $v = true;
                }
            }
        }
        $path = $prefix . join('/', $parts);
        if (($pure) && (substr($path, -1) != '/')) {
            $path .= '/';
        } elseif ((!$pure) && (substr($path, -1) == '/')) {
            $path = substr($path, 0, -1);
        }
        return ($path);
    }

    /**
     * 'Normalize' given path by converting directory separators and add trailing '/' to it
     * Path types can be defined with special path type markers:
     *  - path      - Either absolute path or path with default type
     *  - ^path     - Path calculated from server root
     *  - ~path     - Path calculated from site root
     *  - :path     - Path is prepended with named path "path"
     *
     * @param string $path      Path that needs to be normalized
     * @param boolean $pure     OPTIONAL true for pure paths (without filename),
     *                          false for paths with filename,
     *                          null to auto-detect path type
     * @return string|boolean
     */
    static public function normalize($path, $pure = null)
    {
        // Check, if we get a string.
        if (!is_string($path)) {
            // This is something else, we can't handle this data - trigger error about it
            trigger_error('Given path is not recognized as a string (' . gettype($path) . ')', E_USER_WARNING);
            return (false);
        }
        if ($pure === null) {
            $pure = (boolean)preg_match('/[\/\x5c]$/', $path);
        }
        $path = self::getInstance()->resolve($path);
        if (self::isUnix()) {
            if ($path{0} != '/') {
                $path = getcwd() . '/' . $path;
            }
        } else {
            if (!preg_match('/^[a-z]:[\/\x5c]/i', $path)) {
                $path = getcwd() . "\\" . $path;
            }
        }
        $path = self::build($path, $pure);
        return ($path);
    }

    /**
     * Create all missed directories to make given path physically available in the system
     *
     * @param string $path    path to create (always treated as full path)
     * @param int $permission permissions to set for directories, will be created (optional, default 0755)
     * @return boolean          true if all ok, false if error occured
     */
    static public function create($path, $permission = 0755)
    {
        self::getInstance(); // Instance must be initialized
        $path = self::normalize($path, true);
        // If such path is already available - we have nothing to do here except permissions setting
        if (file_exists($path)) {
            return (true);
        }
        $dirs = explode('/', $path);
        $cwd = getcwd();
        // Here is some differences between handling Unix and Windows paths
        $p = (!self::isUnix()) ? array_shift($dirs) : '';
        $p .= '/';
        if (!chdir($p)) {
            chdir($cwd);
            trigger_error('chdir() is failed during path creating process (Path: ' . $p . ')', E_USER_WARNING);
            return (false);
        }
        if (!is_numeric($permission)) {
            $permission = 0755;
        }
        // Process each directory from given path
        foreach ($dirs as $dir) {
            // If this directory is empty - we need to skip it
            if ($dir == '') {
                continue;
            }
            if (!file_exists($p . $dir)) {
                if (!mkdir($p . $dir, $permission)) {
                    chdir($cwd);
                    trigger_error(
                        'mkdir() is failed during path creating process (Path: ' . $p . $dir . ')',
                        E_USER_WARNING
                    );
                    return (false);
                }
                @chmod($p . $dir, $permission);
                if (!chdir($p . $dir)) {
                    chdir($cwd);
                    trigger_error(
                        'chdir() is failed during path creating process (Path: ' . $p . $dir . ')',
                        E_USER_WARNING
                    );
                }
            }
            $p .= $dir . '/';
        }
        chdir($cwd);
        return (true);
    }

    /**
     * Get "local" path within server root from given path
     *
     * @param string $path Path to make "local"
     * @param string $root OPTIONAL Root path to make given path "local" against (server root by default)
     * @return string
     */
    public static function getLocalPath($path, $root = null)
    {
        $instance = self::getInstance();
        $path = @$instance->normalize($path, false);
        if ($root !== null) {
            $root = $instance->normalize($root, false);
        } else {
            $root = $instance->_serverRoot;
        }
        $path = preg_replace('/^' . preg_quote($root, '/') . '\/?/i', '', $path);
        return ($path);
    }

}
