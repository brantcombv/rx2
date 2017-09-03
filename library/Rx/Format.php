<?php

class Rx_Format
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Format $_instance
     */
    protected static $_instance = null;
    /**
     * Formatting plugins loader
     *
     * @var Zend_Loader_PluginLoader $_loader
     */
    protected $_loader = null;
    /**
     * Already loaded formatting plugins
     *
     * @var array $_formatters
     */
    protected $_formatters = array();

    private function __construct()
    {
        $this->_loader = Rx_Loader::getPluginLoader('Rx_Format');
        $this->_formatters = array();
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Format
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    /**
     * Format given value with requested formatter
     *
     * @param string $formatterId            Formatter type Id to use for formatting value
     * @param string $value                  Value to format
     * @param array $params                  OPTIONAL Additional parameters to use for formatting
     * @param array|Zend_Config|null $config OPTIONAL Configuration options for formatter plugin
     * @return string
     */
    public static function format($formatterId, $value, $params = null, $config = null)
    {
        $formatter = self::getInstance()->getFormatter($formatterId);
        if (!$formatter) {
            trigger_error('Failed to format value, unknown formatter plugin Id: ' . $formatterId, E_USER_WARNING);
            return ($value);
        }
        $result = $formatter->format($value, $params, $config);
        return ($result);
    }

    /**
     * Magic function for formatting values by calling formatters by their names
     *
     * @param string $name     Formatter type Id to use for formatting value
     * @param array $arguments Additional arguments for formatting
     * @see Rx_Format#format()
     * @return string
     */
    public function __call($name, $arguments)
    {
        array_unshift($arguments, $name);
        return (call_user_func_array(array(self::getInstance(), 'format'), $arguments));
    }

    /**
     * Magic function for formatting values by calling formatters by their names
     *
     * @param string $name     Formatter type Id to use for formatting value
     * @param array $arguments Additional arguments for formatting
     * @see Rx_Format#format()
     * @return string
     */
    public function __callStatic($name, $arguments)
    {
        array_unshift($arguments, $name);
        return (call_user_func_array(array(self::getInstance(), 'format'), $arguments));
    }

    /**
     * Register new prefix path for formatting plugins loader
     *
     * @param string $prefix Plugin class prefix
     * @param string $path   Path matching class prefix
     * @return Zend_Loader_PluginLoader
     * @see Zend_Loader_PluginLoader#addPrefixPath()
     */
    public static function addPrefixPath($prefix, $path)
    {
        return (self::getInstance()->_loader->addPrefixPath($prefix, $path));
    }

    /**
     * Register custom formatter
     *
     * @param string $id                    Formatter type Id
     * @param Rx_Format_Abstract $formatter Formatter plugin instance
     * @return void
     * @throws Rx_Exception
     */
    public static function addFormatter($id, $formatter)
    {
        if (!$formatter instanceof Rx_Format_Abstract) {
            throw new Rx_Exception('Formatter plugin should be instance of Rx_Format_Abstract');
        }
        self::getInstance()->_formatters[$id] = $formatter;
    }

    /**
     * Get formatter plugin by given Id
     *
     * @param string $id Formatter type Id to get
     * @return Rx_Format_Abstract|null
     * @throws Rx_Exception
     */
    public function getFormatter($id)
    {
        $instance = self::getInstance();
        if (!array_key_exists($id, $instance->_formatters)) {
            $class = Rx_Loader::loadPlugin($id, $instance->_loader);
            if ($class) {
                $class = new $class();
                if (!$class instanceof Rx_Format_Abstract) {
                    throw new Rx_Exception('Formatter plugin class of type "' . $id . '" must be instance of Rx_Format_Abstract');
                }
            } else {
                trigger_error('Unavailable formatter plugin type: ' . $id, E_USER_WARNING);
            }
            $instance->_formatters[$id] = $class;
        }
        return ($instance->_formatters[$id]);
    }

}
