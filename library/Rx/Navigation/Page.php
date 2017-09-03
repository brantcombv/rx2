<?php

/**
 * Own version for base class for navigation pages.
 * Main purpose is to improve page type guessing logic
 * and page classes loading in page factory method.
 *
 * @author   Alexander "Flying" Grimalovsky <alexander.grimalovsky@gmail.com>
 * @category Rx
 * @package  Rx_Navigation
 */
abstract class Rx_Navigation_Page extends Zend_Navigation_Page
{

    /**
     * Plugin loader class for loading page classes
     *
     * @var Zend_Loader_PluginLoader $_pageLoader
     */
    protected static $_pageLoader = null;

    /**
     * Get page classes loader
     *
     * @return Zend_Loader_PluginLoader
     */
    public static function getPageLoader()
    {
        if (!self::$_pageLoader) {
            self::$_pageLoader = Rx_Loader::getPluginLoader('Zend_Navigation_Page');
        }
        return (self::$_pageLoader);
    }

    /**
     * Set page classes loader
     *
     * @param Zend_Loader_PluginLoader $loader
     * @return void
     * @throws Zend_Navigation_Exception
     */
    public static function setPageLoader($loader)
    {
        if (!$loader instanceof Zend_Loader_PluginLoader) {
            throw new Zend_Navigation_Exception('Page classes loader must be instance of Zend_Loader_PluginLoader');
        }
        self::$_pageLoader = $loader;
    }

    /**
     * Factory for Zend_Navigation_Page classes
     *
     * @param  array|Zend_Config $options  options used for creating page
     * @return Zend_Navigation_Page        a page instance
     * @throws Zend_Navigation_Exception   if $options is not array/Zend_Config
     * @throws Zend_Exception              if 'type' is specified and
     *                                     Zend_Loader is unable to load the
     *                                     class
     * @throws Zend_Navigation_Exception   if something goes wrong during
     *                                     instantiation of the page
     * @throws Zend_Navigation_Exception   if 'type' is given, and the specified
     *                                     type does not extend this class
     * @throws Zend_Navigation_Exception   if unable to determine which class
     *                                     to instantiate
     */
    public static function factory($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            throw new Zend_Navigation_Exception('Invalid argument: $options must be an array or Zend_Config');
        }

        $type = null;
        $page = null;
        if (isset($options['type'])) { // Page type is defined explicitly
            $type = $options['type'];
        } elseif (self::getDefaultPageType() != null) { // There is default page type defined
            $type = self::getDefaultPageType();
        }

        if ($type) {
            // If page type is known - attempt to load it
            $page = self::getPageLoader()->load($type, false);
        }
        if (!$page) {
            // Still no information about page type - try to guess it
            if (isset($options['uri'])) {
                $type = 'uri';
            } elseif (isset($options['mvc'])) {
                $type = 'mvc';
            } elseif (((isset($options['action']))) || (isset($options['controller'])) ||
                (isset($options['module'])) || (isset($options['route']))
            ) {
                $type = 'mvc';
            }
            if ($type) {
                $page = self::getPageLoader()->load($type, false);
            }
        }
        if (!$page) {
            throw new Zend_Navigation_Exception('Invalid argument: Unable to determine class to instantiate');
        }
        $page = new $page($options);
        return ($page);
    }

    /**
     * Adds a page to the container
     *
     * This method will inject the container as the given page's parent by
     * calling {@link Zend_Navigation_Page::setParent()}.
     *
     * @param  Zend_Navigation_Page|array|Zend_Config $page  page to add
     * @return Zend_Navigation_Container                     fluent interface,
     *                                                       returns self
     * @throws Zend_Navigation_Exception                     if page is invalid
     */
    public function addPage($page)
    {
        if ($page === $this) {
            throw new Zend_Navigation_Exception('A page cannot have itself as a parent');
        }

        if (is_array($page) || $page instanceof Zend_Config) {
            $page = Rx_Navigation_Page::factory($page);
        } elseif (!$page instanceof Zend_Navigation_Page) {
            throw new Zend_Navigation_Exception(
                'Invalid argument: $page must be an instance of ' .
                'Zend_Navigation_Page or Zend_Config, or an array');
        }

        $hash = $page->hashCode();

        if (array_key_exists($hash, $this->_index)) {
            // page is already in container
            return $this;
        }

        // adds page to container and sets dirty flag
        $this->_pages[$hash] = $page;
        $this->_index[$hash] = $page->getOrder();
        $this->_dirtyIndex = true;

        // inject self as page parent
        $page->setParent($this);

        return $this;
    }

}
