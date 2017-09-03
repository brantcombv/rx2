<?php

class Rx_Form_Decorator_FormErrors extends Zend_Form_Decorator_FormErrors
{

    /**
     * true to render form element's labels for error messages, false to skip them
     *
     * @var boolean $_renderLabels
     */
    protected $_renderLabels = null;
    /**
     * true to include errors from "hidden" form elements as form errors
     *
     * @var boolean $_includeHiddenElementsErrors
     */
    protected $_includeHiddenElementsErrors = null;
    /**
     * List of classes that represents hidden form elements
     *
     * @var array $_hiddenElementClasses
     */
    protected $_hiddenElementClasses = null;

    /**
     * Get status of rendering form element's labels for error messages
     *
     * @return boolean
     */
    public function getRenderLabels()
    {
        $value = $this->_renderLabels;
        if ($value === null) {
            $value = $this->getOption('renderLabels');
            if ($value !== null) {
                $this->_renderLabels = (boolean)$value;
                $this->removeOption('renderLabels');
            }
        }
        return ($this->_renderLabels);
    }

    /**
     * Set status of rendering form element's labels for error messages
     *
     * @param boolean $status
     * @return Rx_Form_Decorator_FormErrors
     */
    public function setRenderLabels($status)
    {
        $this->_renderLabels = (boolean)$status;
        return ($this);
    }

    /**
     * Render element label
     *
     * @param  Zend_Form_Element $element
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function renderLabel(Zend_Form_Element $element, Zend_View_Interface $view)
    {
        if (!$this->getRenderLabels()) {
            return ('');
        }
        return (parent::renderLabel($element, $view));
    }

    /**
     * Get status of including errors from "hidden" form elements as form errors
     *
     * @return boolean
     */
    public function getIncludeHiddenElementsErrors()
    {
        $value = $this->_includeHiddenElementsErrors;
        if ($value === null) {
            $value = $this->getOption('includeHiddenElementsErrors');
            if ($value !== null) {
                $this->_includeHiddenElementsErrors = (boolean)$value;
                $this->removeOption('includeHiddenElementsErrors');
            }
        }
        return ($this->_includeHiddenElementsErrors);
    }

    /**
     * Set status of including errors from "hidden" form elements as form errors
     *
     * @param boolean $status
     * @return Rx_Form_Decorator_FormErrors
     */
    public function setIncludeHiddenElementsErrors($status)
    {
        $this->_includeHiddenElementsErrors = (boolean)$status;
        return ($this);
    }

    /**
     * Get list of classes that represents hidden form elements
     *
     * @param Zend_Form $form
     * @return array
     */
    protected function _getHiddenElementClasses($form)
    {
        if (!is_array($this->_hiddenElementClasses)) {
            $classes = array();
            $prefixes = $form->getPluginLoader(Zend_Form::ELEMENT)->getPaths();
            foreach (array_keys($prefixes) as $prefix) {
                $classes = $classes + Rx_Loader::getClassNames('hidden', $prefix, true, false);
            }
            $classes = array_unique($classes);
            $this->_hiddenElementClasses = $classes;
        }
        return ($this->_hiddenElementClasses);
    }

    /**
     * Recurse through a form object, rendering errors
     *
     * @param  Zend_Form $form
     * @param  Zend_View_Interface $view
     * @return string
     */
    protected function _recurseForm(Zend_Form $form, Zend_View_Interface $view)
    {
        $content = '';
        $classes = array();
        if ($this->getIncludeHiddenElementsErrors()) {
            $classes = $this->_getHiddenElementClasses($form);
        }

        $custom = $form->getCustomMessages();
        if ($this->getShowCustomFormErrors() && count($custom)) {
            $content .= $this->getMarkupListItemStart()
                . $view->formErrors($custom, $this->getOptions())
                . $this->getMarkupListItemEnd();
        }
        foreach ($form->getElementsAndSubFormsOrdered() as $subitem) {
            if (($subitem instanceof Zend_Form_Element) &&
                ((!$this->getOnlyCustomFormErrors()) ||
                    (in_array(get_class($subitem), $classes)))
            ) {
                $messages = $subitem->getMessages();
                if (count($messages)) {
                    $subitem->setView($view);
                    $content .= $this->getMarkupListItemStart()
                        . $this->renderLabel($subitem, $view)
                        . $view->formErrors($messages, $this->getOptions())
                        . $this->getMarkupListItemEnd();
                }
            } else {
                if ($subitem instanceof Zend_Form && !$this->ignoreSubForms()) {
                    $markup = $this->_recurseForm($subitem, $view);

                    if (!empty($markup)) {
                        $content .= $this->getMarkupListStart()
                            . $markup
                            . $this->getMarkupListEnd();
                    }
                }
            }
        }
        return $content;
    }

}
