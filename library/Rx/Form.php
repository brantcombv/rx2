<?php

class Rx_Form extends Zend_Form
{
    /**
     * Registered decorators collections for elements
     *
     * @var array $_elementDecoratorsRegistry
     */
    protected $_elementDecoratorsRegistry = array();

    /**
     * Registered decorators collections for display groups
     *
     * @var array $_displayGroupDecoratorsRegistry
     */
    protected $_displayGroupDecoratorsRegistry = array();

    /**
     * Initialize form
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Register prefixes for loading custom validators/decorators/filters
        $prefixes = Rx_Loader::getPrefixPath('Rx_Form_Element');
        foreach ($prefixes as $prefix => $path) {
            $this->addPrefixPath($prefix, $path, Zend_Form::ELEMENT);
        }

        $prefixes = Rx_Loader::getPrefixPath('Rx_Form_Decorator');
        foreach ($prefixes as $prefix => $path) {
            $this->addPrefixPath($prefix, $path, Zend_Form::DECORATOR);
        }

        $prefixes = Rx_Loader::getPrefixPath('Rx_Filter');
        foreach ($prefixes as $prefix => $path) {
            $this->addElementPrefixPath($prefix, $path, Zend_Form_Element::FILTER);
        }

        $prefixes = Rx_Loader::getPrefixPath('Rx_Validate');
        foreach ($prefixes as $prefix => $path) {
            $this->addElementPrefixPath($prefix, $path, Zend_Form_Element::VALIDATE);
        }

        $this->setDisableLoadDefaultDecorators(true);
    }

    /**
     * Clone form object and all children
     *
     * @return void
     */
    public function __clone()
    {
        $elements = $this->getElements(false);
        $subForms = $this->getSubForms(false);
        $displayGroups = $this->_displayGroups;
        $order = $this->_order;
        $this->clearElements();
        $this->clearSubForms();
        $this->clearDisplayGroups();
        foreach ($order as $id => $priority) {
            if (array_key_exists($id, $elements)) {
                /** @var $element Zend_Form_Element */
                $element = clone $elements[$id];
                $this->addElement($element, $id);
            } elseif (array_key_exists($id, $subForms)) {
                /** @var $subForm Zend_Form_SubForm */
                $subForm = clone $subForms[$id];
                $this->addSubForm($subForm, $id);
            } elseif (array_key_exists($id, $displayGroups)) {
                /** @var $displayGroup Zend_Form_DisplayGroup */
                $displayGroup = clone $displayGroups[$id];
                $elements = array();
                foreach ($displayGroup->getElements() as $name => $element) {
                    $elements[] = $this->getElement($name);
                }
                $this->addDisplayGroup($elements, $id);
            }
        }
    }

    /**
     * Register element decorators collection
     *
     * @param string $id        Decorators collection Id
     * @param array $decorators Collection of decorators
     * @return void
     */
    public function registerElementDecorators($id, $decorators)
    {
        if (!is_array($decorators)) {
            $decorators = array($decorators);
        }
        $this->_elementDecoratorsRegistry[$id] = $decorators;
    }

    /**
     * Check if element decorators collection with given Id is available
     *
     * @param string $id Decorators collection Id
     * @return boolean
     */
    public function hasElementDecorators($id)
    {
        return (array_key_exists($id, $this->_elementDecoratorsRegistry));
    }

    /**
     * Get element decorators collection by given Id
     *
     * @param string $id Decorators collection Id
     * @return array|null
     */
    public function getElementDecorators($id)
    {
        if (array_key_exists($id, $this->_elementDecoratorsRegistry)) {
            return ($this->_elementDecoratorsRegistry[$id]);
        }
        return (null);
    }

    /**
     * Register display group decorators collection
     *
     * @param string $id        Decorators collection Id
     * @param array $decorators Collection of decorators
     * @return void
     */
    public function registerDisplayGroupDecorators($id, $decorators)
    {
        if (!is_array($decorators)) {
            $decorators = array($decorators);
        }
        $this->_displayGroupDecoratorsRegistry[$id] = $decorators;
    }

    /**
     * Check if display group decorators collection with given Id is available
     *
     * @param string $id Decorators collection Id
     * @return boolean
     */
    public function hasDisplayGroupDecorators($id)
    {
        return (array_key_exists($id, $this->_displayGroupDecoratorsRegistry));
    }

    /**
     * Get display group decorators collection by given Id
     *
     * @param string $id Decorators collection Id
     * @return array|null
     */
    public function getDisplayGroupDecorators($id)
    {
        if (array_key_exists($id, $this->_displayGroupDecoratorsRegistry)) {
            return ($this->_displayGroupDecoratorsRegistry[$id]);
        }
        return (null);
    }

    /**
     * Add a new element
     *
     * @param  string|Zend_Form_Element $element
     * @param  string $name
     * @param  array|Zend_Config $options
     * @return Zend_Form
     */
    public function addElement($element, $name = null, $options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            $options = array();
        }
        if (array_key_exists('decorators', $options)) {
            if (is_string($options['decorators'])) {
                if ($this->hasElementDecorators($options['decorators'])) {
                    $options['disableLoadDefaultDecorators'] = true;
                    $options['decorators'] = $this->getElementDecorators($options['decorators']);
                } else {
                    trigger_error(
                        'Reference to unregistered element decorators collection: ' . $options['decorators'],
                        E_USER_WARNING
                    );
                    unset($options['decorators']);
                }
            }
        } elseif ($this->hasElementDecorators('default')) {
            $options['disableLoadDefaultDecorators'] = true;
            $options['decorators'] = $this->getElementDecorators('default');
        }
        // Set breakChainOnFailure option by default since normally we only need to get 1 error message per element
        if (array_key_exists('validators', $options)) {
            foreach ($options['validators'] as $k => $v) {
                if (!is_array($v)) {
                    continue;
                }
                if (array_key_exists('breakChainOnFailure', $v)) {
                    continue;
                }
                $options['validators'][$k]['breakChainOnFailure'] = true;
            }
        }
        return (parent::addElement($element, $name, $options));
    }

    /**
     * Add a display group
     *
     * @param  array $elements
     * @param  string $name
     * @param  array|Zend_Config $options
     * @return Zend_Form
     * @throws Zend_Form_Exception if no valid elements provided
     */
    public function addDisplayGroup(array $elements, $name, $options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            $options = array();
        }
        if (array_key_exists('decorators', $options)) {
            if (is_string($options['decorators'])) {
                if ($this->hasDisplayGroupDecorators($options['decorators'])) {
                    $options['disableLoadDefaultDecorators'] = true;
                    $options['decorators'] = $this->getDisplayGroupDecorators($options['decorators']);
                } else {
                    trigger_error(
                        'Reference to unregistered display group decorators collection: ' . $options['decorators'],
                        E_USER_WARNING
                    );
                    unset($options['decorators']);
                }
            }
        } elseif ($this->hasDisplayGroupDecorators('default')) {
            $options['disableLoadDefaultDecorators'] = true;
            $options['decorators'] = $this->getDisplayGroupDecorators('default');
        }
        return (parent::addDisplayGroup($elements, $name, $options));
    }

    /**
     * Retrieve a single element
     *
     * @param  string $name       Element name to get
     * @param  boolean $recursive OPTIONAL true to search for element in subforms, false to skip them
     * @return Zend_Form_Element|null
     */
    public function getElement($name, $recursive = true)
    {
        if (!$recursive) {
            return (parent::getElement($name));
        }

        if (array_key_exists($name, $this->_elements)) {
            return ($this->_elements[$name]);
        }
        /** @var $subForm Zend_Form_SubForm */
        foreach ($this->_subForms as $subForm) {
            $element = $subForm->getElement($name, $recursive);
            if ($element) {
                return ($element);
            }
        }

        return (null);
    }

    /**
     * Retrieve all elements
     *
     * @param  boolean $recursive OPTIONAL true to search for elements in subforms, false to skip them
     * @return array
     */
    public function getElements($recursive = true)
    {
        if (!$recursive) {
            return ($this->_elements);
        }
        $elements = array();
        $this->_sort();
        foreach (array_keys($this->_order) as $name) {
            if (array_key_exists($name, $this->_elements)) {
                $elements[$name] = $this->_elements[$name];
            } elseif (array_key_exists($name, $this->_subForms)) {
                $elements = array_merge($elements, $this->_subForms[$name]->getElements($recursive));
            }
        }
        return ($elements);
    }

    /**
     * Get form elements by given type
     *
     * @param string $type        Type of form elements to retrieve
     * @param  boolean $recursive OPTIONAL true to search for elements in subforms, false to skip them
     * @return array
     */
    public function getElementsByType($type, $recursive = true)
    {
        $classes = array();
        $prefixes = $this->getPluginLoader(Zend_Form::ELEMENT)->getPaths();
        foreach (array_keys($prefixes) as $prefix) {
            $classes = $classes + Rx_Loader::getClassNames($type, $prefix, true, false);
        }
        $classes = array_unique($classes);
        $elements = $this->getElements($recursive);
        foreach ($elements as $name => $element) {
            $found = false;
            foreach ($classes as $class) {
                if ($element instanceof $class) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                unset($elements[$name]);
            }
        }
        return ($elements);
    }

    /**
     * Retrieve a form subForm/subform
     *
     * @param  string $name
     * @param  boolean $recursive OPTIONAL true to search for subform recursively, false to skip them
     * @return Zend_Form|null
     */
    public function getSubForm($name, $recursive = true)
    {
        if (!$recursive) {
            return (parent::getSubForm($name));
        }

        if (array_key_exists($name, $this->_subForms)) {
            return ($this->_subForms[$name]);
        }
        /** @var $sf Zend_Form_SubForm */
        foreach ($this->_subForms as $sf) {
            $subForm = $sf->getSubForm($name, $recursive);
            if ($subForm) {
                return ($subForm);
            }
        }

        return (null);
    }

    /**
     * Retrieve all form subForms/subforms
     *
     * @param  boolean $recursive OPTIONAL true to search for subforms recursively, false to skip them
     * @return array
     */
    public function getSubForms($recursive = true)
    {
        if (!$recursive) {
            return ($this->_subForms);
        }
        $subForms = array();
        $this->_sort();
        foreach (array_keys($this->_order) as $name) {
            if (array_key_exists($name, $this->_subForms)) {
                $subForms = array_merge($subForms, $this->_subForms[$name]->getSubForms($recursive));
            }
        }
        return ($subForms);
    }

    /**
     * Validate the form
     *
     * @param  array $data
     * @throws Zend_Form_Exception
     * @return boolean
     */
    public function isValid($data)
    {
        if (!is_array($data)) {
            throw new Zend_Form_Exception(__CLASS__ . '::' . __METHOD__ . ' expects an array');
        }
        $translator = $this->getTranslator();
        $valid = true;

        if ($this->isArray()) {
            $data = $this->_dissolveArrayValue($data, $this->getElementsBelongTo());
        }

        /** @var $element Zend_Form_Element */
        foreach ($this->getElements() as $key => $element) {
            // Fix for ZF-10056 - ignored form elements should not be validated
            if ($element->getIgnore()) {
                continue;
            }
            $element->setTranslator($translator);
            if (!isset($data[$key])) {
                $valid = $element->isValid(null, $data) && $valid;
            } else {
                $valid = $element->isValid($data[$key], $data) && $valid;
            }
        }
        /** @var $form Zend_Form_SubForm */
        foreach ($this->getSubForms() as $key => $form) {
            $form->setTranslator($translator);
            if (isset($data[$key])) {
                $valid = $form->isValid($data[$key]) && $valid;
            } else {
                $valid = $form->isValid($data) && $valid;
            }
        }

        $this->_errorsExist = !$valid;

        // If manually flagged as an error, return invalid status
        if ($this->_errorsForced) {
            return false;
        }

        return $valid;
    }

    /**
     * Export form configuration so it will be used on client side via jquery.rxform.js
     *
     * @return array
     */
    public function exportConfig()
    {
        $config = array();
        $elements = $this->getElements(true);
        /* @var $element Zend_Form_Element */
        foreach ($elements as $element) {
            $info = array(
                'filters'    => array(),
                'validators' => array(),
            );
            $methodsFilter = array();
            $filters = $element->getFilters();
            /* @var $filter Zend_Filter_Interface */
            foreach ($filters as $filter) {
                $name = get_class($filter);
                if (preg_match('/_Filter_(.+)$/i', $name, $t)) {
                    $name = $t[1];
                } else {
                    $t = explode('_', $name);
                    $name = array_pop($t);
                }
                $name = strtolower(substr($name, 0, 1)) . substr($name, 1);
                $params = array();
                $getters = $this->_getClassGetters($filter);
                foreach ($getters as $param => $method) {
                    if (in_array($param, $methodsFilter)) {
                        continue;
                    }
                    $value = $filter->$method();
                    // If method returns object - we should filter it out because methods are useless on client side
                    if (is_object($value)) {
                        continue;
                    }
                    $params[$param] = $value;
                }
                $info['filters'][$name] = $params;
            }

            $methodsFilter = array(
                'translator',
                'defaultTranslator',
                'messageLength',
            );
            $validators = $element->getValidators();
            /* @var $validator Zend_Validate_Interface */
            foreach ($validators as $validator) {
                $name = get_class($validator);
                if (preg_match('/_Validate_(.+)$/i', $name, $t)) {
                    $name = $t[1];
                } else {
                    $t = explode('_', $name);
                    $name = array_pop($t);
                }
                $name = strtolower(substr($name, 0, 1)) . substr($name, 1);
                $params = array();
                $getters = $this->_getClassGetters($validator);
                foreach ($getters as $param => $method) {
                    if (in_array($param, $methodsFilter)) {
                        continue;
                    }
                    // We actually need getMessageTemplates() instead of getMessages()
                    if ($method == 'getMessages') {
                        $method = 'getMessageTemplates';
                    }
                    $value = $validator->$method();
                    // If method returns object - we should filter it out because methods are useless on client side
                    if (is_object($value)) {
                        continue;
                    }
                    $params[$param] = $value;
                }
                $info['validators'][$name] = $params;
            }

            $config[$element->getId()] = $info;
        }
        return ($config);
    }

    /**
     * Get list of getter methods for given class
     *
     * @param string|object $class Either class name or class instance
     * @return array                    Array of getters in a form paramName=>methodName
     */
    protected function _getClassGetters($class)
    {
        static $cache = array();

        if (is_object($class)) {
            $class = get_class($class);
        }
        if (!isset($cache[$class])) {
            $reflection = new ReflectionClass($class);
            $methods = array();
            $getters = array();
            $setters = array();
            $_methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            /* @var $method ReflectionMethod */
            foreach ($_methods as $method) {
                $name = $method->getName();
                $p = substr($name, 0, 3);
                $n = strtolower(substr($name, 3, 1)) . substr($name, 4);
                if ($p == 'get') {
                    // Since we can't predict arguments for methods
                    // we skip all potential getter methods with required parameters
                    if ($method->getNumberOfRequiredParameters() > 0) {
                        continue;
                    }
                    $getters[$n] = $name;
                } elseif ($p == 'set') {
                    $setters[$n] = $name;
                }
            }
            // We should only use getters with corresponding setters
            foreach ($getters as $name => $method) {
                if (!isset($setters[$name])) {
                    continue;
                }
                $methods[$name] = $method;
            }
            $cache[$class] = $methods;
        }
        $getters = $cache[$class];
        return ($getters);
    }

}
