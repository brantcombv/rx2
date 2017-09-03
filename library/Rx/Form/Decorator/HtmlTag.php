<?php

class Rx_Form_Decorator_HtmlTag extends Zend_Form_Decorator_HtmlTag
{

    /**
     * Content to insert before given contents
     *
     * @var string $_before
     */
    protected $_before = null;
    /**
     * Content to insert after given contents
     *
     * @var string $_after
     */
    protected $_after = null;

    /**
     * @return string
     */
    public function getBefore()
    {
        if ($this->_before === null) {
            $this->_before = $this->getOption('before');
            unset($this->_options['before']);
        }
        return ($this->_before);
    }

    /**
     * @return string
     */
    public function getAfter()
    {
        if ($this->_after === null) {
            $this->_after = $this->getOption('after');
            unset($this->_options['after']);
        }
        return ($this->_after);
    }

    /**
     * Set content to insert before given contents
     *
     * @param $content
     */
    public function setBefore($content)
    {
        $this->_before = $content;
    }

    /**
     * Set content to insert after given contents
     *
     * @param $content
     */
    public function setAfter($content)
    {
        $this->_after = $content;
    }

    /**
     * Get the formatted open tag
     *
     * @param  string $tag
     * @param  array $attribs
     * @return string
     */
    protected function _getOpenTag($tag, array $attribs = null)
    {
        $html = '<' . $tag;
        if (null !== $attribs) {
            $html .= $this->_htmlAttribs($attribs);
        }
        $html .= '>';
        $html .= $this->getBefore();
        return $html;
    }

    /**
     * Get formatted closing tag
     *
     * @param  string $tag
     * @return string
     */
    protected function _getCloseTag($tag)
    {
        return $this->getAfter() . '</' . $tag . '>';
    }

    /**
     * Render content wrapped in an HTML tag
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        // Need to remove before/after options
        $this->getBefore();
        $this->getAfter();
        // Look list of options and replace {attr} placeholders with element's attributes
        $options = $this->getOptions();
        /* @var $element Zend_Form_Element */
        $element = null;
        foreach ($options as $name => $value) {
            if (!preg_match('/\{([^\|]+)\}/', $value, $t)) {
                continue;
            }
            if (!$element) {
                $element = $this->getElement();
            }
            $attr = $element->getAttrib($t[1]);
            if (($name == 'id') && (!$element->getId())) {
                // If no Id is assigned to element
                // and there is placeholder for it - remove attribute completely
                $this->removeOption($name);
                continue;
            }
            $value = str_replace('{' . $t[1] . '}', $attr, $value);
            $this->setOption($name, $value);
        }

        return (parent::render($content));
    }

}
