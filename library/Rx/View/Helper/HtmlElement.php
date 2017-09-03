<?php

abstract class Rx_View_Helper_HtmlElement extends Zend_View_Helper_HtmlElement
{
    /**
     * View helper configuration options
     *
     * @var Rx_Configurable_Embedded $_config
     */
    protected $_config = null;
    /**
     * List of empty HTML elements as by HTML5 specification
     *
     * @var array $_emptyElements
     */
    protected $_emptyElements = array(
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    );

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL View helper configuration options
     * @return Rx_View_Helper_HtmlElement
     */
    public function __construct($config = null)
    {
        $this->_config = new Rx_Configurable_Embedded($this, $this->_getConfigOptions(), array(
            'checkConfig'     => '_checkConfig',
            'onConfigChanged' => '_onConfigChanged',
        ), $config);
    }

    /**
     * Render HTML element with given name, attributes and content
     *
     * @param string $name     Element name
     * @param array $attrs     OPTIONAL List of element attributes
     * @param string $content  OPTIONAL Element contents
     * @param boolean $newline OPTIONAL true to add newline character after element code
     * @return string
     */
    public function _element($name, $attrs = array(), $content = null, $newline = false)
    {
        if (!is_array($attrs)) {
            $attrs = array();
        }
        $element = strtolower(trim($name));
        // Remove empty "class" attributes
        $filterAttrs = array('class');
        foreach ($filterAttrs as $attr) {
            if (!array_key_exists($attr, $attrs)) {
                continue;
            }
            $value = $attrs[$attr];
            if (is_array($value)) {
                $value = array_filter($value, array($this, '_filterAttr'));
                if (sizeof($value)) {
                    $attrs[$attr] = $value;
                } else {
                    unset($attrs[$attr]);
                }
            } elseif ((is_string($value)) && (!strlen(trim($value)))) {
                unset($attrs[$attr]);
            }
        }
        $html = '<' . $element . $this->_htmlAttribs($attrs);
        if (!in_array($element, $this->_emptyElements)) {
            $html .= '>' . $content . '</' . $element . '>';
        } else {
            $html .= ' />';
        }
        if ($newline) {
            $html .= "\n";
        }
        return ($html);
    }

    /**
     * Array attributes filter
     *
     * @param string $value
     * @return boolean
     */
    protected function _filterAttr($value)
    {
        if (is_scalar($value)) {
            return (strlen($value) > 0);
        } else {
            return (false);
        }
    }

    /**
     * Alias for _element() method
     *
     * @param string $name     Element name
     * @param array $attrs     OPTIONAL List of element attributes
     * @param string $content  OPTIONAL Element contents
     * @param boolean $newline OPTIONAL true to add newline character after element code
     * @return string
     */
    public function _tag($name, $attrs = array(), $content = null, $newline = false)
    {
        return ($this->_element($name, $attrs, $content, $newline));
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
     * Get list of configuration options for view helper
     *
     * @return array
     */
    protected function _getConfigOptions()
    {
        // This method is mean to be overridden
        // if view helper needs to define some configuration options for itself
        return (array());
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
        // @see Rx_Configurable_Abstract#_checkConfig()
        return (true);
    }

    /**
     * Perform required operations when configuration option value is changed
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Configuration option value
     * @param string $operation Current operation Id
     * @return void
     */
    public function _onConfigChanged($name, $value, $operation)
    {
        // @see Rx_Configurable_Abstract#_onConfigChanged()
    }

}
