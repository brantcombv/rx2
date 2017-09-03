<?php

class Rx_Validate_InArray extends Zend_Validate_InArray
{

    /**
     * Sets validator options
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options = null)
    {
        // Validator is basically the same as Zend_Validate_InArray
        // However original validator have a potential problem that occurs when
        // validator is used for Zend_Form_Element_Select form elements that are
        // registered through Zend_Form::addElement()
        //
        // In this case it is not generally possible to pass list of haystack elements
        // to validator at a time of class construction and it causes exception to be thrown
        //
        // To avoid such behavior - missed list of haystack elements is not treated as a problem
        // and Zend_Form_Element_Select::getValidator() is patched to provide list of haystack
        // elements when necessary
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else {
            // Support for old arguments notation
            $options = array(
                'haystack'  => array(),
                'strict'    => $this->getStrict(),
                'recursive' => $this->getRecursive(),
            );
            $args = func_get_args();
            $t = array_shift($args);
            if (is_array($t)) {
                if ((sizeof($t) == 1) && (array_key_exists('haystack', $t))) {
                    $options['haystack'] = $t['haystack'];
                } else {
                    $options['haystack'] = $t;
                }
            }
            $t = array_shift($args);
            if ($t !== null) {
                $options['strict'] = $t;
            }
        }
        parent::__construct($options);
    }

}