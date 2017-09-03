<?php

abstract class Rx_Form_Element_Multi extends Zend_Form_Element_Multi
{

    /**
     * Retrieve a single validator by name
     *
     * @param  string $name
     * @return Zend_Validate_Interface|false False if not found, validator otherwise
     */
    public function getValidator($name)
    {
        $validator = parent::getValidator($name);
        // If we're retrieving InArray validator which have no haystack defined -
        // initialize its haystack with current set of element's multi options
        $name = (is_object($validator)) ? get_class($validator) : null;
        if (preg_match('/_Validate_(.+)$/i', $name, $t)) {
            $name = $t[1];
        } else {
            $t = explode('_', $name);
            $name = array_pop($t);
        }
        if ($name == 'InArray') {
            $haystack = $validator->getHaystack();
            if (!sizeof($haystack)) {
                $haystack = $this->_getHaystack($this->_getMultiOptions());
                $validator->setHaystack($haystack)
                    ->setRecursive(false);
            }
        }
        return ($validator);
    }

    /**
     * Collect values for InArray validator from given multi-options array
     *
     * @param array $options Multi-options from this element
     * @return array
     */
    protected function _getHaystack($options)
    {
        $haystack = array();
        foreach ($options as $value => $label) {
            if (is_array($label)) {
                $h = $this->_getHaystack($label);
                $haystack = array_merge($haystack, $h);
            } else {
                $haystack[] = $value;
            }
        }
        return ($haystack);
    }

    /**
     * Retrieve all validators
     *
     * @return array
     */
    public function getValidators()
    {
        $validators = array();
        foreach ($this->_validators as $key => $value) {
            if ($value instanceof Zend_Validate_Interface) {
                $validators[$key] = $value;
                continue;
            }
            // Use getValidator() instead of _loadValidator() to apply patch for InArray
            $validator = $this->getValidator($value['validator']);
            $validators[get_class($validator)] = $validator;
        }
        return $validators;
    }

}
