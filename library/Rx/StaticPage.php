<?php

class Rx_StaticPage
{
    /**
     * List of default paths where to search for static pages
     *
     * @var array $_defaultPaths
     */
    protected static $_defaultPaths = array();
    /**
     * List of paths where to search for static pages
     *
     * @var array $_paths
     */
    protected $_paths = array();
    /**
     * List of assigned variables for static page rendering
     *
     * @var array $_vars
     */
    protected $_vars = array();
    /**
     * true to use Zend_View for rendering static pages,
     * false to disable Zend_View usage (e.g. for non-web environments)
     *
     * @var boolean $_useView
     */
    protected static $_useView = true;
    /**
     * Default view object to use for rendering static page
     *
     * @var Zend_View_Abstract $_defaultView
     */
    protected static $_defaultView = null;
    /**
     * View object to use for rendering static page
     *
     * @var Zend_View_Abstract $_view
     */
    protected $_view = null;
    /**
     * View object to use during page rendering process
     *
     * @var Zend_View_Abstract $_renderView
     */
    protected $_renderView = null;

    /**
     * Class constructor
     *
     * @param string|array|null $paths          OPTIONAL Path or list of additional paths to look for static page
     *                                          If no paths are given - default path will be taken
     *                                          from "pages" named path
     * @param Zend_View_Abstract|null $view     OPTIONAL View object to use for rendering page
     */
    public function __construct($paths = null, $view = null)
    {
        if ($paths !== null) {
            $this->setPaths($paths);
        }
        if ($view !== null) {
            $this->setView($view);
        }
    }

    /**
     * Set default paths where to search for static page
     *
     * @param string|array $paths Path or list of additional paths to look for static page
     * @return void
     */
    public static function setDefaultPaths($paths)
    {
        self::$_defaultPaths = self::_normalizePaths($paths);
    }

    /**
     * Normalize given list of paths to use for searching static pages
     *
     * @param string|array $paths Path or list of additional paths to look for static page
     * @return array
     */
    protected static function _normalizePaths($paths)
    {
        $result = array();
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        foreach ($paths as $path) {
            if (!strlen($path)) {
                continue;
            }
            $path = Rx_Path::normalize($path, true);
            if (!is_dir($path)) {
                trigger_error('Invalid or unavailable path: ' . $path, E_USER_WARNING);
                continue;
            }
            $result[] = $path;
        }
        return ($result);
    }

    /**
     * Set paths where to search for static page
     *
     * @param string|array $paths Path or list of additional paths to look for static page
     * @return void
     */
    public function setPaths($paths)
    {
        $this->_paths = self::_normalizePaths($paths);
    }

    /**
     * Set "use view" flag
     *
     * @param boolean $flag View usage status flag
     * @return void
     */
    public static function setUseView($flag)
    {
        self::$_useView = (boolean)$flag;
    }

    /**
     * Set view object to use for rendering page
     *
     * @param Zend_View_Abstract $view View object to use for rendering page
     * @return void
     * @throws Rx_Exception
     */
    public static function setDefaultView($view)
    {
        if (!$view instanceof Zend_View_Abstract) {
            throw new Rx_Exception('View object should be instance of Zend_View_Abstract');
        }
        self::$_defaultView = $view;
    }

    /**
     * Set view object to use for rendering page
     *
     * @param Zend_View_Abstract $view View object to use for rendering page
     * @return void
     * @throws Rx_Exception
     */
    public function setView($view)
    {
        if (!$view instanceof Zend_View_Abstract) {
            throw new Rx_Exception('View object should be instance of Zend_View_Abstract');
        }
        $this->_view = $view;
    }

    /**
     * Get view object to use for page rendering
     *
     * @param Zend_View_Abstract|null $view OPTIONAL View object to use for rendering page
     * @return Zend_View_Abstract
     */
    public function getView($view = null)
    {
        if ($view instanceof Zend_View_Abstract) {
            return ($view);
        }
        if ($this->_view instanceof Zend_View_Abstract) {
            return ($this->_view);
        }
        if (self::$_defaultView instanceof Zend_View_Abstract) {
            return (self::$_defaultView);
        }
        $view = null;
        if (!self::$_useView) {
            return ($view);
        }
        if (Zend_Registry::isRegistered('Zend_View')) {
            $view = Zend_Registry::get('Zend_View');
        } else {
            /** @var $vr Zend_Controller_Action_Helper_ViewRenderer */
            $vr = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $view = $vr->view;
        }
        if ($view instanceof Zend_View_Abstract) {
            $this->setView($view);
            return ($view);
        }
        return (new Zend_View());
    }

    /**
     * Assign one or multiple variables for view rendering
     *
     * @param string|array $name Either variable name or list of variables to assign
     * @param mixed $value       OPTIONAL Variable value
     * @return void
     */
    public function assign($name, $value = null)
    {
        if (!is_array($name)) {
            $name = array($name => $value);
        }
        foreach ($name as $k => $v) {
            $this->_vars[$k] = $v;
        }
    }

    /**
     * Get static page text by given parameters
     *
     * @param string $id            Static page Id
     * @param string $language      OPTIONAL Language to get page on, null to use current language
     * @param bool|string $fallback OPTIONAL true to get page on other languages (current/default)
     *                              if page is not available in given language, false to return null in this case
     * @param boolean $render       OPTIONAL true to render embedded view helper calls, false to leave them as is
     * @return string|null          Static page text or null if page is not found
     */
    public function getPage($id, $language = null, $fallback = true, $render = true)
    {
        $path = $this->findPage($id, $language);
        if ((!$path) && ($fallback)) {
            $current = Rx_Language::getLanguage();
            $default = Rx_Language::getDefaultLanguage();
            if (($language != $current) && ($language != $default)) {
                $path = $this->findPage($id, $current);
            }
            if ((!$path) && ($language != $default)) {
                $path = $this->findPage($id, $default);
            }
        }
        if (!$path) {
            return (null);
        }
        $page = file_get_contents($path);
        if ($render) {
            $page = $this->render($page);
        }
        return ($page);
    }

    /**
     * Try to find static page file by given Id and language
     *
     * @param string $id       Static page Id
     * @param string $language Language to get page on
     * @return string|null      Path to static page file or null if file was not found
     */
    protected function findPage($id, $language)
    {
        $filenames = array($id);
        if ($language) {
            $filenames[] = $id . '_' . $language;
            $filenames[] = $id . '.' . $language;
            $language = Rx_Language::expand($language);
            $filenames[] = $id . '_' . $language;
            $filenames[] = $id . '.' . $language;
        }
        $paths = $this->_paths + self::$_defaultPaths;
        foreach ($paths as $path) {
            $files = new DirectoryIterator($path);
            /* @var $file SplFileInfo */
            foreach ($files as $file) {
                if ((!$file->isFile()) ||
                    (!$file->isReadable())
                ) {
                    continue;
                }
                $filename = pathinfo($file->getPathname(), PATHINFO_FILENAME);
                if (in_array($filename, $filenames)) {
                    $file = Rx_Path::normalize($file->getPathname(), false);
                    return ($file);
                }
            }
        }
        return (null);
    }

    /**
     * Render given page text by executing embedded calls to view helpers
     *
     * @param string $page                  Page text to render
     * @param Zend_View_Abstract|null $view OPTIONAL View object to use for rendering page
     * @return string
     */
    public function render($page, $view = null)
    {
        $this->_renderView = null;
        if (self::$_useView) {
            $this->_renderView = $this->getView($view);
            $this->_renderView->assign($this->_vars);
        }
        $page = preg_replace_callback(
            '/\{[\$\=][a-z0-9\_]+(\|[^\}\|]*)*\}/i',
            array($this, 'parseViewHelperInfo'),
            $page
        );
        $this->_renderView = null;
        return ($page);
    }

    /**
     * Parse view helper call information and replace it with actual view helper call
     * View helper call looks like:
     * call     ::= "{" ("=" helper | "$" var ) "}"
     * var      ::= name ("|" value)?
     * helper   ::= name ("|" (value | ( name ":" value ( "," name ":" value )* ) )*
     * name     ::= [a-z0-9\_]+
     * value    ::= [a-z0-9\_]+ | "'" [^\']* "'" | '"' [^\"]* '"'
     *
     * Examples:
     * {$variableName} or {=var|variableName}       - Will be replaced by value of view variable with given name
     * {$variableName|default value}                - Same as above but with default value provided
     * {=url|some.page|id:123,comment:"Some text"}  - Call to "url" view helper with name (quotes can be omitted) and array of parameters
     * {=image|"media:image.common/logotype"}       - Call to "image" view helper with string parameter
     *
     * @param array $data Regular expression matching information with view helper call info
     * @return string
     */
    protected function parseViewHelperInfo($data)
    {
        $data = $data[0];
        if (!preg_match('/^\{([\$\=])(.+?)\}$/', $data, $t)) {
            trigger_error('View helper call parser: invalid format: ' . $data, E_USER_WARNING);
            return (null);
        }
        $type = $t[1];
        $data = $t[2];
        if ($type == '$') {
            $data = 'var|' . $data;
        }
        $name = null;
        $args = array();
        $result = null;
        $_counter = 100;
        while (($_counter--) && (strlen($data))) {
            $t = explode('|', $data, 2);
            $param = array_shift($t);
            $data = array_shift($t);
            if ($name === null) {
                if (!preg_match('/^[a-z0-9\_]+$/i', $param)) {
                    trigger_error(
                        'View helper call parser: invalid view helper name format: ' . $param,
                        E_USER_WARNING
                    );
                    return (null);
                }
                $name = $param;
            } else {
                $arg = null;
                // Check if we have single argument value or array of values
                // Special case: for "var" pseudo view helper and for "url" and "image" view helpers
                // we allow to omit quotes for first argument
                if ((($name == 'var') ||
                        ((in_array($name, array('url', 'image'))) && (!sizeof($args)))) &&
                    (!preg_match('/^([\'\"]).*?\1$/', $param))
                ) {
                    $param = '"' . $param . '"';
                }
                if (preg_match('/^([a-z0-9\_\-\.]+|\"[^\"]*\"|\'[^\']*\')$/i', $param)) {
                    $arg = $this->fromString($param);
                } else {
                    $arg = array();
                    $_cnt = 100;
                    while (($_cnt--) && (strlen($param))) {
                        if (!preg_match('/^([a-z0-9\_]+):([a-z0-9\_]+|\"[^\"]*\"|\'[^\']*\')/i', $param, $t)) {
                            trigger_error(
                                'View helper call parser: invalid view helper parameter format: ' . $param,
                                E_USER_WARNING
                            );
                            return (null);
                        }
                        $arg[$t[1]] = $this->fromString($t[2]);
                        $param = preg_replace('/^' . preg_quote($t[0], '/') . '/', '', $param);
                        if (substr($param, 0, 1) == ',') {
                            $param = substr($param, 1);
                        }
                    }
                }
                $args[] = $arg;
            }
        }
        if ($name !== 'var') {
            if (self::$_useView) {
                $result = call_user_func_array(array($this->_renderView, $name), $args);
            } else {
                trigger_error(
                    'View helper reference is found in static page, but view usage is disabled',
                    E_USER_WARNING
                );
            }
        } else {
            $name = array_shift($args);
            if ($name !== null) {
                if (($this->_renderView instanceof Zend_View_Abstract) &&
                    (isset($this->_renderView->{$name}))
                ) {
                    $result = $this->_renderView->{$name};
                } elseif (array_key_exists($name, $this->_vars)) {
                    $result = $this->_vars[$name];
                } else {
                    $result = array_shift($args);
                }
            }
        }
        return ($result);
    }

    /**
     * Convert given value from string to real value
     *
     * @param string $value Value to convert
     * @return mixed
     */
    protected function fromString($value)
    {
        if ($value == 'null') {
            $value = null;
        } elseif ($value == 'true') {
            $value = true;
        } elseif ($value == 'false') {
            $value = false;
        }
        if (preg_match('/^([\'\"])(.*?)\1$/si', $value, $t)) {
            $value = $t[2];
        }
        return ($value);
    }

}
