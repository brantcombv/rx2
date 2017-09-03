<?php

class Rx_Form_Layout_Standard extends Rx_Form
{
    /**
     * Decorators configuration for form itself
     *
     * @var array $_formDecoratorsConfig
     */
    protected $_formDecoratorsConfig = array(
        'FormElements',
        '_formErrors',
        'Form',
        '_formContainer',
    );
    /**
     * Decorators configuration for form elements
     *
     * @var array $_elementDecoratorsConfig
     */
    protected $_elementsDecoratorsConfig = array(
        'default'  => array(
            'ViewHelper',
            '_element',
            '_description',
            '_label',
            '_errors',
            '_line',
        ),
        'hidden'   => array(
            'ViewHelper',
        ),
        'html'     => array(
            '_viewscript',
            '_element',
            '_label',
            '_line',
        ),
        'checkbox' => array(
            'ViewHelper',
            '_label',
            '_element',
            '_description',
            '_fake_label',
            '_errors',
            '_line',
        ),
        'radio'    => array(
            'ViewHelper',
            '_element',
            '_description',
            '_label',
            '_errors',
            '_line',
        ),
        'submit'   => array(
            'ViewHelper',
            '_description',
            '_submitLine',
        ),
    );
    /**
     * true to include decorator for element description, false to skip
     *
     * @var boolean $_includeElementDescriptions
     */
    protected $_includeElementDescriptions = false;

    /**
     * Initialize form
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        // Configure decorators for form itself
        $this->setDecorators($this->_parseDecoratorsConfig($this->_formDecoratorsConfig, false));
        // Register decorators for form elements
        $config = $this->_parseDecoratorsConfig($this->_elementsDecoratorsConfig, true);
        foreach ($config as $element => $decorators) {
            $this->registerElementDecorators($element, $decorators);
        }
    }

    /**
     * Parse given decorators configuration
     * to get decorators configuration required by Zend_Form classes
     *
     * @param array $config     Decorators configuration to parse
     * @param boolean $multiple true if multiple configurations are passed
     * @return array
     */
    protected function _parseDecoratorsConfig($config, $multiple)
    {
        $result = array();
        if (!$multiple) {
            $config = array($config);
        }
        foreach ($config as $element => $elementConfig) {
            $decorators = array();
            foreach ($elementConfig as $decorator) {
                $id = $decorator;
                if (is_array($id)) {
                    $id = array_shift($id);
                    if (is_array($id)) {
                        $id = key($id);
                    }
                }
                $options = $this->_getDecoratorConfig($decorator, $element);
                if ($options === false) {
                    continue;
                }
                if ($id != $decorator) {
                    $id = array($id => $decorator);
                }
                $decorators[] = ($options !== null) ? array($id, $options) : $id;
            }
            $result[$element] = $decorators;
        }
        if (!$multiple) {
            $result = array_shift($result);
        }
        return ($result);
    }

    /**
     * Get configuration for decorator with given Id for given element
     *
     * @param string|array $decorator   Reference to decorator name
     *                                  In a case if decorator Id is passed - it is mean
     *                                  to be replaced with real name of decorator
     *                                  Complete decorator config can also be passed
     * @param string $element           Form element Id to configure decorator for
     * @return array|boolean|null       Decorator options, null if no options is required, false to not include decorator
     */
    protected function _getDecoratorConfig(&$decorator, $element)
    {
        $options = null;
        if (is_array($decorator)) {
            $name = array_shift($decorator);
            $options = array_shift($decorator);
            $decorator = (is_array($name)) ? array_shift($name) : $name;
        }
        switch ($decorator) {
            case '_element':
                $decorator = 'HtmlTag';
                $options = array(
                    'tag'   => 'div',
                    'class' => 'form_element',
                );
                if ($element === 'radio') {
                    $options['class'] = join(' ', array($options['class'], 'form_element_radio'));
                }
                break;
            case '_label':
                $decorator = 'Label';
                $options = array(
                    'placement' => 'PREPEND',
                );
                if ($element === 'checkbox') {
                    $options = array(
                        'placement' => 'APPEND',
                        'class'     => 'append',
                    );
                }
                break;
            case '_fake_label': // Fake label for checkbox to avoid breaking form layout
                $decorator = 'HtmlTag';
                $options = array(
                    'tag'       => 'label',
                    'placement' => 'PREPEND',
                    'before'    => '&nbsp;',
                );
                break;
            case '_errors':
                $decorator = 'Errors';
                $options = array(
                    'placement' => 'PREPEND',
                );
                break;
            case '_formContainer':
                $decorator = 'HtmlTag';
                $options = array(
                    'tag'   => 'div',
                    'id'    => 'form_{id}',
                    'class' => 'form',
                );
                break;
            case '_formErrors':
                $decorator = 'FormErrors';
                $options = array(
                    'placement'                   => 'PREPEND',
                    'showCustomFormErrors'        => true,
                    'onlyCustomFormErrors'        => true,
                    'includeHiddenElementsErrors' => true,
                    'renderLabels'                => false,
                );
                break;
            case '_description':
                $decorator = 'Description';
                if ($this->_includeElementDescriptions) {
                    $options = array(
                        'tag'       => 'div',
                        'class'     => 'form_description',
                        'placement' => 'APPEND',
                    );
                } else {
                    $options = false;
                }
                break;
            case '_line': // Form element line
                $decorator = 'ElementLine';
                $options = array(
                    'tag'        => 'fieldset',
                    'id'         => 'fs_{id}',
                    'class'      => 'form_element_line',
                    'errorClass' => 'form_errors',
                );
                break;
            case '_submitLine': // Form line for form submission button
                $decorator = 'HtmlTag';
                $options = array(
                    'tag'   => 'fieldset',
                    'id'    => 'fs_{id}',
                    'class' => 'form_element_line form_submit_line',
                );
                break;
            case '_viewscript': // View script rendered as form element
                $decorator = 'ViewScript';
                break;
        }
        return ($options);
    }

}
