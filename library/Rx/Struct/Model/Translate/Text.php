<?php

class Rx_Struct_Model_Translate_Text extends Rx_Struct_Model_Abstract
{
    /**
     * Name of corresponding Rx_Model_Entity based class (named Id to use for Rx_ModelManager)
     *
     * @var string $_entityClassName
     */
    protected $_entityClassName = 'translate_text';
    /**
     * Calculated Id of translated text
     *
     * @var string $_textId
     */
    protected $_textId = null;

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'id'           => null,
            // Translated text Id
            'section'      => null,
            // Named Id of texts section, translated text belongs to
            'subid'        => null,
            // Sub Id of translated text
            'name'         => null,
            // Named Id of translated text
            'translations' => array(),
            // Available translations of this text (Rx_Struct_Model_Translate_Text_Translation)
            // Service structure fields
            'db_id'        => null,
            // Database Id of text information
            '_raw'         => false,
            // true if raw text is used as key
        );
        $this->_textId = null;
    }

    /**
     * Actual implementation of structure field retrieving by name
     * Implemented as separate method for easier overriding into actual structure classes
     *
     * @param string $name   Structure element name to get value of
     * @param mixed $default Default value to return in a case if element is not available
     * @param array $config  Configuration options
     * @return mixed
     */
    protected function _get($name, $default, $config)
    {
        $result = $default;
        switch ($name) {
            case 'id':
                if ($this->_textId === null) {
                    if (!parent::_get('_raw', false, $config)) {
                        $id = array();
                        $t = parent::_get('section', null, $config);
                        if ($t !== null) {
                            $id[] = $t;
                        }
                        $t = parent::_get('subid', null, $config);
                        if ($t !== null) {
                            $id[] = $t;
                        }
                        $t = parent::_get('name', null, $config);
                        if ($t !== null) {
                            $id[] = $t;
                        }
                        $this->_textId = join('-', $id);
                    } else {
                        $this->_textId = parent::_get('name', null, $config);
                    }
                }
                $result = $this->_textId;
                break;
            default:
                $result = parent::_get($name, $default, $config);
                break;
        }
        return ($result);
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
            case '_raw':
                $value = (boolean)$value;
                break;
            case 'db_id':
                if ($value !== null) {
                    $value = (int)$value;
                }
                break;
            case 'section':
            case 'subid':
                if (!strlen($value)) {
                    $value = null;
                }
                break;
            case 'name':
                // Determine if we have raw text as text Id
                $raw = (!preg_match('/^[a-z0-9][a-z0-9\_]*$/', $value));
                $this->set('_raw', $raw, $config, true);
                break;
            default:
                break;
        }
        if (in_array($name, array('section', 'subid', 'name', '_raw'))) {
            $this->_textId = null;
        } // Reset text Id so it will be recalculated
        parent::_set($name, $value, $config);
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
            array('db_id', '_raw')
        ));
    }

    /**
     * Get list of calculated structure fields
     * These fields will not be writable in any case
     *
     * @return array|string
     */
    protected function _getCalculatedFields()
    {
        return (array('id'));
    }

    /**
     * Get list of linkable structure fields
     * Structures in these fields will be linked with main structure
     *
     * @return array|string
     */
    protected function _getLinkableFields()
    {
        return (array('translations'));
    }

    /**
     * Get list of array structure fields
     * These fields will be allowed for array operations
     *
     * @return array|string
     */
    protected function _getArrayFields()
    {
        return (array('translations' => 'Rx_Struct_Model_Translate_Text_Translation'));
    }

    /**
     * Initialize meanings of certain structure fields
     * Passing meanings as argument is useful for inheritance
     *
     * @param array $meanings OPTIONAL Meanings to add (in a form "meaning Id"=>"structure field name")
     * @return array
     */
    protected function initMeanings($meanings = array())
    {
        // Add meaning for patch information database Id
        return (parent::initMeanings(array_merge($meanings, array(
            'db_id' => 'db_id',
        ))));
    }

    /**
     * Get translation object of this text on given language
     *
     * @param string $language               OPTIONAL Language to get translation for (current language by default)
     * @param boolean $create                OPTIONAL true to create empty translation object if no translation is available yet
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return Rx_Struct_Model_Translate_Text_Translation|null
     */
    public function getTranslation($language = null, $create = true, $config = null)
    {
        if ($language !== null) {
            $language = Rx_Language::expand($language);
        } else {
            $language = Rx_Language::getLanguage(true);
        }
        $translation = $this->arrayGet('translations', $language, null, $config);
        if ((!$translation) && ($create)) {
            $translation = new Rx_Struct_Model_Translate_Text_Translation(array(
                'language' => $language,
            ));
            $this->arraySet('translations', $language, $translation, $config);
        }
        return ($translation);
    }

    /**
     * Get translation of this text on given language
     *
     * @param string $language               OPTIONAL Language to get translation for (current language by default)
     * @param string $default                OPTIONAL Default text to return in a case of missed translation
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return string
     */
    public function getText($language = null, $default = null, $config = null)
    {
        $text = $default;
        if ($language !== null) {
            $language = Rx_Language::expand($language);
        } else {
            $language = Rx_Language::getLanguage(true);
        }
        $translation = $this->arrayGet('translations', $language, null, $config);
        if (!$translation) {
            return ($text);
        }
        $text = $translation->get('text', $default, $config);
        return ($text);
    }

    /**
     * Get plural forms of this text on given language
     *
     * @param string $language               OPTIONAL Language to get plural forms for (current language by default)
     * @param string $default                OPTIONAL Default text to return in a case of missed translation
     * @param array|Zend_Config|null $config Configuration options to override default object's configuration
     * @return array
     */
    public function getPlural($language = null, $default = null, $config = null)
    {
        $plural = array();
        if ($default !== null) {
            $plural[] = $default;
        }
        if ($language !== null) {
            $language = Rx_Language::expand($language);
        } else {
            $language = Rx_Language::getLanguage(true);
        }
        $translation = $this->arrayGet('translations', $language, null, $config);
        if (!$translation) {
            return ($plural);
        }
        $plural = array($translation->get('text', $default, $config));
        $pt = $translation->get('plural_texts', array(), $config);
        foreach ($pt as $t) {
            $plural[] = $t;
        }
        return ($plural);
    }

    /**
     * Add translation of this text
     *
     * @param string|array $text Translated text (single text or array with plural forms)
     * @param string $language   OPTIONAL Language of new translation (current language is used by default)
     * @param boolean $patch     OPTIONAL true if it is patched translation, false if it is default translation
     * @return boolean                  true if translation was added, false in a case of error
     */
    public function addTranslation($text, $language = null, $patch = false)
    {
        if (is_array($text)) {
            // Pre-check: we have no support for patched plural texts,
            // neither for more then 3 plural forms of translation
            if ($patch) {
                trigger_error('Plural text forms are not supported for patched texts', E_USER_WARNING);
                return (false);
            }
            if (sizeof($text) > 3) {
                trigger_error('Maximum of 3 plural text forms are supported', E_USER_WARNING);
                return (false);
            }
        }
        $l = ($language !== null) ? Rx_Language::expand($language) : Rx_Language::getLanguage(true);
        if (!$l) {
            trigger_error('Invalid language Id is given: ' . $language, E_USER_WARNING);
            return (false);
        }
        $language = $l;
        $translation = $this->getTranslation($language, true);
        if ($patch) {
            $translation->set(array(
                'text' => $text,
            ), null, array('use_patch' => true));
        } else {
            $plural = array();
            if (is_array($text)) {
                $plural = $text;
                $text = array_shift($plural);
            }
            $translation->set(array(
                'text'         => $text,
                'plural_texts' => $plural,
            ), null, array('use_patch' => false));
        }
        return (true);
    }

}
