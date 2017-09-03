<?php

abstract class Rx_Router_Route_Base extends Zend_Controller_Router_Route_Abstract
{

    /**
     * List of available languages in application
     *
     * @var array $languages
     */
    protected $languages = array();
    /**
     * true if language identifier should be added to URL, false if not
     *
     * @var boolean $addLanguage
     */
    protected $addLanguage = false;
    /**
     * Regular expression to match languages into URL
     *
     * @var boolean $languageRegexp
     */
    protected $languageRegexp = null;

    /**
     * Implementation of Zend_Controller_Router_Route_Interface
     *
     * @param Zend_Config $config
     * @return null
     */
    public static function getInstance(Zend_Config $config)
    {
        return (null);
    }

    /**
     * Class constructor
     *
     * @return Rx_Router_Route_Base
     */
    public function __construct()
    {
        $this->addLanguage = (boolean)Rx_Config::get('rx.url.language.enabled', true);
        if (!$this->addLanguage) {
            return;
        }
        $this->languages = Rx_Language::getAvailableLanguages();
        if (sizeof($this->languages) < 2) {
            // If there is only one language - disable "language in URL" feature
            $this->addLanguage = false;
            return;
        }
        $t = array();
        foreach ($this->languages as $v) {
            $t[] = preg_quote($v, '/');
        }
        $this->languageRegexp = '/^(\/?)(' . join('|', $t) . ')\//i';
    }

    /**
     * Check request matching for route
     *
     * @param Zend_Controller_Request_Http $request
     * @throws Rx_Exception
     * @return array|boolean
     */
    public function match($request)
    {
        // Method can't be set as abstract, PHP shows fatal error in this case...
        throw new Rx_Exception(__METHOD__ . ' must be overridden');
    }

    /**
     * Assemble URL from given information
     *
     * @param array $data     Information for url assembling
     * @param boolean $reset  OPTIONAL true to reset url information
     * @param boolean $encode OPTIONAL true to encode resulted url
     * @throws Rx_Exception
     * @return string
     */
    public function assemble($data = array(), $reset = false, $encode = false)
    {
        // See comment above
        throw new Rx_Exception(__METHOD__ . ' must be overridden');
    }

    /**
     * Handle transparent language passing in urls at url matching phase
     *
     * @param Zend_Controller_Request_Http $request
     * @return void
     */
    protected function _match($request)
    {
        if (!$this->addLanguage) {
            return;
        }
        $path = $request->getPathInfo();
        if ((preg_match($this->languageRegexp, $path, $t)) &&
            (in_array(strtolower($t[2]), $this->languages))
        ) {
            Rx_Language::setLanguage($t[2]);
            $path = preg_replace('/^' . preg_quote($t[0], '/') . '/i', $t[1], $path);
            $request->setPathInfo($path);
        }
    }

    /**
     * Handle transparent language passing in urls at url assembling phase
     *
     * @param string $url                   URL to patch
     * @param boolean $pure                 OPTIONAL true if url is "pure" (means there is no filename in it)
     * @param string|boolean|null $language OPTIONAL Language identifier for url or false to avoid adding language Id
     * @return string
     */
    protected function _assemble($url, $pure = true, $language = null)
    {
        if ((($this->addLanguage) || ($language)) &&
            ($language !== false) &&
            (!preg_match($this->languageRegexp, $url)) &&
            (!preg_match('/^[a-z0-9\-\+]+\:\/\//i', $url))
        ) // URL is not absolute, e.g. http://www.example.com/
        {
            if ($language === null) {
                $language = Rx_Language::getLanguage();
            }
            if (substr($url, 0, 1) != '/') {
                $url = '/' . $url;
            }
            $url = $language . $url;
        }
        if (substr($url, 0, 1) == '/') {
            $url = substr($url, 1);
        }
        if (($pure) && (strlen($url)) && (substr($url, -1) != '/')) {
            $url .= '/';
        }

        return ($url);
    }

}
