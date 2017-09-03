<?php

class Rx_Form_Element_Multiselect extends Rx_Form_Element_Select
{
    /**
     * 'multiple' attribute
     *
     * @var string
     */
    public $multiple = 'multiple';

    /**
     * Use formSelect view helper by default
     *
     * @var string
     */
    public $helper = 'formSelect';

    /**
     * Multiselect is an array of values by default
     *
     * @var bool
     */
    protected $_isArray = true;

}
