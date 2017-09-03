<?php

class Rx_Struct_Model_Translate_Section extends Rx_Struct_Model_Abstract
{
    /**
     * Name of corresponding Rx_Model_Entity based class (named Id to use for Rx_ModelManager)
     *
     * @var string $_entityClassName
     */
    protected $_entityClassName = 'translate_section';

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'id'          => null,
            // Database Id of translated texts section
            'subids'      => false,
            // true if section items can have sub Ids, false if not
            'patches'     => false,
            // true if section items can be patched, false if not
            'raw'         => false,
            // true if raw texts can be passed for translation in this section, false if only text Ids are supported
            'description' => null,
            // Description of this section
        );
    }

    /**
     * Actual implementation of setting structure field value.
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name  Structure element name to set value of
     * @param mixed $value  New value for this element
     * @param array $config Configuration options
     * @return void
     */
    protected function _set($name, $value, $config)
    {
        switch ($name) {
            case 'subids':
            case 'patches':
            case 'raw':
                $value = (boolean)$value;
                break;
            default:
                break;
        }
        parent::_set($name, $value, $config);
    }

}
