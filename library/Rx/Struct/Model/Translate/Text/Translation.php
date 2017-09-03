<?php

class Rx_Struct_Model_Translate_Text_Translation extends Rx_Struct_Model_Patched
{

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'id'           => null, // Database Id of text translation
            'language'     => null, // Language, text is translated to
            'text'         => null, // Translated text
            'plural'       => false, // true if plural texts are available for this text
            'plural_texts' => array(), // Plural forms of translated text
            // Service structure fields
            '_blob'        => false, // true if content is mean to be stored in BLOB field, false if it is plain text
            // Patch related information
            '_patch_id'    => null, // Database Id of patch information row
            '_patch_owner' => null, // Id of owner of patched version of translation
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
            case 'plural':
            case '_blob':
                $value = (boolean)$value;
                break;
            case 'id':
            case '_patch_id':
                if ($value !== null) {
                    $value = (int)$value;
                }
                break;
            case 'language':
                if (!$config['constructor']) {
                    // Complete language Ids are used for translations
                    $v = Rx_Language::expand($value);
                    if ($v) {
                        $value = $v;
                    } else {
                        trigger_error('Unknown language Id: ' . $value, E_USER_WARNING);
                        $value = null;
                    }
                }
                break;
            case 'text':
                if (!$config['constructor']) {
                    // Determine if we can store text into plain text column or it should be stored in BLOB
                    $blob = ((strlen($value) > 255) || (preg_match('/[\r\n\x00-\x1F]/i', $value)));
                    $this->set('_blob', $blob, $config, true);
                }
                break;
            default:
                break;
        }
        parent::_set($name, $value, $config);
    }

    /**
     * Actual implementation of setting element value into array structure field
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name  Array structure field name
     * @param string $key   Key of element within array structure field to set value of
     * @param mixed $value  Element value to set
     * @param array $config Configuration options
     * @return void
     */
    protected function _arraySet($name, $key, $value, $config)
    {
        switch ($name) {
            case 'plural_texts':
                if (!$config['constructor']) {
                    $this->set('plural', true, null, true);
                }
                break;
        }
        parent::_arraySet($name, $key, $value, $config);
    }

    /**
     * Get list of names of structure fields that should be marked as read-only
     * These fields will be writable only during object construction
     * or by directly passing "constructor" option in config
     *
     * @return array|string
     */
    protected function _getReadOnlyFields()
    {
        return (array_merge(
            parent::_getReadOnlyFields(),
            array('plural', '_blob')
        ));
    }

    /**
     * Get list of names of structure fields that should be marked as write-only
     * These fields will not be readable in any case but it will be possible to write to them
     *
     * @return array|string
     */
    protected function _getWriteOnlyFields()
    {
        return (array('_patch_id', '_patch_owner'));
    }

    /**
     * Get list of array structure fields
     * These fields will be allowed for array operations
     *
     * @return array|string
     */
    protected function _getArrayFields()
    {
        return (array('plural_texts'));
    }

    /**
     * Provide list of structure fields that can be patched
     *
     * @return array|string|boolean     Array of fields, true to allow patching all fields, false to disable patching
     */
    protected function _getPatchFields()
    {
        return (array('text', '_blob'));
    }

}
