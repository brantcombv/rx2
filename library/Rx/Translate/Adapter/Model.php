<?php

class Rx_Translate_Adapter_Model extends Zend_Translate_Adapter
{

    /**
     * Translation texts collection
     *
     * @var Rx_Model_Translate_Texts $_model
     */
    protected $_model = null;
    /**
     * Id of texts sections that contains raw texts
     *
     * @var array $_rawTextSections
     */
    protected $_rawTextSections = null;

    /**
     * Add translations
     *
     * This may be a new language or additional content for an existing language
     * If the key 'clear' is true, then translations for the specified
     * language will be replaced and added otherwise
     *
     * @param  array|Zend_Config $options Options and translations to be added
     * @throws Zend_Translate_Exception
     * @return Zend_Translate_Adapter Provides fluent interface
     */
    public function addTranslation($options = array())
    {
        // We didn't load translation information in regular way
        // so this method should do nothing about information loading

        return $this;
    }

    /**
     * Load translation data
     *
     * @param  mixed $data
     * @param  string|Zend_Locale $locale
     * @param  array $options (optional)
     * @return array
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        // We don't need to load translation data in regular way
        return (array());
    }

    /**
     * Get normalized locale Id from given locale
     *
     * @param string|Zend_Locale $locale OPTIONAL Locale Id
     * @return string
     */
    protected function _getLocaleId($locale = null)
    {
        if ($locale === null) {
            $locale = $this->_options['locale'];
        }
        // Translated texts uses complete locale Ids
        $locale = Rx_Language::expand($locale);
        // If invalid locale is given - use default locale
        // since outer code expects to get some locale
        if (!$locale) {
            $locale = Rx_Language::getDefaultLanguage(true);
        }
        return ($locale);
    }

    /**
     * Check if given message is actually message id instead of text
     *
     * @param string $message Message to check
     * @return boolean
     */
    public function isMessageId($message)
    {
        return (Rx_Model_Translate_Texts::isTextId($message));
    }

    /**
     * Get list of text sections that may contain given message
     *
     * @param string $message Message Id or text
     * @return array
     */
    protected function _getMessageSections($message)
    {
        $sectionIds = array();
        if ($this->isMessageId($message)) {
            // We're about to translate message Id, so it's section Id and sub Id
            // are part of message Id
            $t = explode('-', $message);
            array_pop($t); // Local message Id part
            $sId = array_shift($t);
            $subId = array_shift($t);
            if ($subId !== null) {
                $sId .= '-' . $subId;
            }
            $sectionIds[] = $sId;
        } else {
            // We need to translate raw text, so need to know Ids of text sections
            // that may contain translation for this text
            if (!is_array($this->_rawTextSections)) {
                // Get list of available text sections and select Ids of sections
                // that may contain raw text
                $this->_rawTextSections = array();
                /* @var $mSections Rx_Model_Translate_Sections */
                $mSections = Rx_ModelManager::get('translate_sections');
                $sections = $mSections->getItems(true);
                /* @var $section Rx_Struct_Model_Translate_Section */
                foreach ($sections as $sId => $section) {
                    if ($section->raw) {
                        $this->_rawTextSections[] = $sId;
                    }
                }
            }
            $sectionIds = $this->_rawTextSections;
        }
        return ($sectionIds);
    }

    /**
     * Load text section from model
     *
     * @param string $sectionId Text section Id to load
     * @param string $locale    Locale Id to load texts for
     * @return void
     */
    protected function _loadTextSection($sectionId, $locale)
    {
        if (!array_key_exists($locale, $this->_translate)) {
            $this->_translate[$locale] = array();
        }
        if (!array_key_exists($sectionId, $this->_translate[$locale])) {
            // Load text section from model
            if (!$this->_model) {
                $this->_model = Rx_ModelManager::get('translate_texts');
            }
            $this->_translate[$locale][$sectionId] = $this->_model->getTranslations($sectionId, $locale);
        }
    }

    /**
     * Translates the given string
     * returns the translation
     *
     * @see                                                          Zend_Locale
     * @param  string|array $messageId       Translation string, or Array for plural translations
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with
     *                                       locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function translate($messageId, $locale = null)
    {
        $locale = $this->_getLocaleId($locale);

        // Determine if we get request for plural translation
        $plural = false;
        $pNumber = 0;
        if (is_array($messageId)) {
            $plural = true;
            $msg = array_shift($messageId);
            $pNumber = array_pop($messageId);
            if (!is_numeric($pNumber)) {
                $pNumber = array_pop($messageId);
            }
            if (is_numeric($pNumber)) {
                $pNumber = Zend_Translate_Plural::getPlural($pNumber, $locale);
            } else {
                $plural = false;
            }
            $messageId = $msg;
        }

        // Get list of text sections that can contain this message
        $sectionIds = $this->_getMessageSections($messageId);
        // Search for message translation among selected text sections
        foreach ($sectionIds as $sectionId) {
            $this->_loadTextSection($sectionId, $locale);
            if (array_key_exists($messageId, $this->_translate[$locale][$sectionId])) {
                // Translation found
                $translation = $this->_translate[$locale][$sectionId][$messageId];
                if (($plural) && (is_array($translation)) && (array_key_exists($pNumber, $translation))) {
                    $translation = $translation[$pNumber];
                } elseif (is_array($translation)) {
                    $translation = array_shift($translation);
                }
                return ($translation);
            }
        }

        // No translation is available - return original message
        $this->_log($messageId, $locale);
        return ($messageId);
    }

    /**
     * Checks if a string is translated within the source or not
     * returns boolean
     *
     * @param  string $messageId             Translation string
     * @param  boolean $original             (optional) Allow translation only for original language
     *                                       when true, a translation for 'en_US' would give false when it can
     *                                       be translated with 'en' only
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with locale identifier,
     *                                       see Zend_Locale for more information
     * @return boolean
     */
    public function isTranslated($messageId, $original = false, $locale = null)
    {
        $locale = $this->_getLocaleId($locale);
        // Get list of text sections that can contain this message
        $sectionIds = $this->_getMessageSections($messageId);
        // Search for message translation among selected text sections
        foreach ($sectionIds as $sectionId) {
            $this->_loadTextSection($sectionId, $locale);
            if (array_key_exists($messageId, $this->_translate[$locale][$sectionId])) {
                return (true);
            }
        }

        // No translation found, return original
        $this->_log($messageId, $locale);
        return (false);
    }

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString()
    {
        return ('Model');
    }

}
