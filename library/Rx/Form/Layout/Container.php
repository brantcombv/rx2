<?php

class Rx_Form_Layout_Container extends Rx_Form_Layout_Standard
{
    /**
     * Decorators configuration for form itself
     *
     * @var array $_formDecoratorsConfig
     */
    protected $_formDecoratorsConfig = array(
        'FormElements',
        '_containerTitle',
        '_container',
    );

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
            case '_containerTitle':
                $decorator = 'Legend';
                $options = array(
                    'placement' => 'PREPEND',
                );
                break;
            case '_container':
                $decorator = 'HtmlTag';
                $options = array(
                    'tag'   => 'div',
                    'id'    => 'container_{name}',
                    'class' => 'form_container',
                );
                break;
            default:
                $options = parent::_getDecoratorConfig($decorator, $element);
                break;
        }
        return ($options);
    }

}
