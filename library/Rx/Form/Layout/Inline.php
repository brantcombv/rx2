<?php

class Rx_Form_Layout_Inline extends Rx_Form_Layout_Standard
{
    /**
     * Decorators configuration for form itself
     *
     * @var array $_formDecoratorsConfig
     */
    protected $_formDecoratorsConfig = array(
        'FormElements',
        '_element',
        '_description',
        '_label',
        '_errors',
        '_line',
    );

    /**
     * Initialize form
     *
     * @return void
     */
    public function init()
    {
        // Remove decorators from default form element because they're applied to subform
        $this->_elementsDecoratorsConfig['default'] = array(
            'ViewHelper',
        );
        parent::init();
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
        switch ($decorator) {
            case '_label':
                $decorator = 'Legend';
                $options = array(
                    'placement' => 'PREPEND',
                );
                break;
            case '_line':
                $options = parent::_getDecoratorConfig($decorator, $element);
                if (is_array($options)) {
                    $options['id'] = 'fs_{name}';
                }
                break;
            case '_element':
                $options = parent::_getDecoratorConfig($decorator, $element);
                if (is_array($options)) {
                    $options['id'] = '{name}';
                }
                break;
            default:
                $options = parent::_getDecoratorConfig($decorator, $element);
                break;
        }
        return ($options);
    }

}
