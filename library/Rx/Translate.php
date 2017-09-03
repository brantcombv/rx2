<?php

class Rx_Translate implements Rx_Notify_Observer
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Translate $_instance
     */
    protected static $_instance = null;
    /**
     * Name of CSS class to set for <span> tag that marks missed translations.
     * Configuration option: rx.translate.missed.class (used only if "rx.translate.missed.mark" option enabled)
     *
     * @var string $_missedClass
     */
    protected $_missedClass = null;
    /**
     * true to throw warning error about missed translation
     * Configuration option: rx.translate.missed.error
     *
     * @var boolean $_missedError
     */
    protected $_missedError = null;
    /**
     * Path to directory where to store reports about missed translations
     * Configuration option: rx.translate.missed.report.path (used only if "rx.translate.missed.report.enabled" option enabled)
     *
     * @var string $_reportPath
     */
    protected $_reportPath = null;
    /**
     * true to report missed translations that are marked as "not required"
     * Configuration option: rx.translate.missed.report.notRequired (used only if "rx.translate.missed.report.enabled" option enabled)
     *
     * @var boolean $_reportNotRequired
     */
    protected $_reportNotRequired = null;
    /**
     * true to report missed translations that are not available on current language,
     * false to report only translations that are missed in both current and default languages
     * Configuration option: rx.translate.missed.report.currentLanguage (used only if "rx.translate.missed.report.enabled" option enabled)
     *
     * @var boolean $_reportCurrentLanguage
     */
    protected $_reportCurrentLanguage = null;
    /**
     * List of hashes of already reported missed translations (to avoid duplicated reports)
     *
     * @var array $_reported
     */
    protected $_reported = array();
    /**
     * Options for loading translated resources by Zend_Translate_Adapter.
     * Required for automatic loading of translation vocabularies on language switching
     *
     * @see Zend_Translate_Adapter#addTranslation()
     * @var array $_translateOptions
     */
    protected $_translateOptions = array();
    /**
     * Instance of Zend_Translate_Adapter object to use for translation
     *
     * @var Zend_Translate_Adapter $_adapter
     */
    protected $_adapter = null;
    /**
     * true if object was initialized already, false if not
     *
     * @var boolean $_initialized
     */
    protected $_initialized = false;

    private function __construct()
    {
        $this->_initialized = false;
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Translate
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::bootstrap();
        }
        return (self::$_instance);
    }

    /**
     * Class bootstrap
     */
    public static function bootstrap()
    {
        $instance = self::getInstance();
        if ($instance->_initialized) {
            return;
        }
        // Subscribe to language changing events so we will be able to keep translating content using correct language
        Rx_Notify::subscribe($instance, 'rx_language_changed');
        // Register cache for Zend_Translate if it is not registered yet
        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
        } elseif (Zend_Registry::isRegistered('Zend_Cache')) {
            $cache = Zend_Registry::get('Zend_Cache');
        } else {
            $cache = null;
        }
        if ($cache) {
            if (!Zend_Translate::hasCache()) {
                Zend_Translate::setCache($cache);
            }
            if (!Zend_Translate_Adapter::hasCache()) {
                Zend_Translate_Adapter::setCache($cache);
            }
        }
        // Install and configure translation adapter
        // Translation adapter can be:
        // - defined in configuration file (rx.translate.adapter.name option)
        // - passed through "Zend_Translate" key in Zend_Registry
        // - set through Rx_Translate::setAdapter()
        $translate = null;
        $adapter = Rx_Config::get('rx.translate.adapter.name');
        $options = $instance->normalizeOptions(Rx_Config::getArray('rx.translate.adapter.options'));
        self::setTranslateOptions($options);
        if ($adapter) {
            // Attempt to create translate object with parameters from configuration
            try {
                $options = array_merge(
                    $options,
                    array(
                        'adapter' => $adapter,
                        'locale'  => Rx_Language::getLanguage(),
                    )
                );
                $translate = new Zend_Translate($options);
            } catch (Zend_Translate_Exception $e) {
                trigger_error('Failed to initialize translation adapter by information from application config: ' . $e->getMessage(), E_USER_ERROR);
                $translate = null;
            }
        }
        // If no translation adapter is available yet - attempt to take it from registry
        if ((!$translate) && (Zend_Registry::isRegistered('Zend_Translate'))) {
            $translate = Zend_Registry::get('Zend_Translate');
        }
        if ($translate instanceof Zend_Translate) {
            $translate = $translate->getAdapter();
        }
        if ($translate instanceof Zend_Translate_Adapter) {
            self::setAdapter($translate, true);
        }

        // Configure reporting of missed translations
        $cfg = Rx_Config::getConfig('rx.translate.missed');
        if (Rx_Config::get('mark', false, $cfg)) {
            $instance->_missedClass = Rx_Config::get('class', null, $cfg);
        }
        $instance->_missedError = (boolean)Rx_Config::get('error', false, $cfg);
        $cfg = Rx_Config::getConfig('rx.translate.missed.report');
        if (Rx_Config::get('enabled', false, $cfg)) {
            $path = Rx_Config::getPath('path', true, $cfg);
            if ((!is_dir($path)) || (!is_writeable($path))) {
                trigger_error(
                    'Path defined in rx.translate.missed.report.path is either missed or not writable',
                    E_USER_WARNING
                );
            }
            $instance->_reportPath = $path;
            $instance->_reportNotRequired = (boolean)Rx_Config::get('notRequired', false, $cfg);
            $instance->_reportCurrentLanguage = (boolean)Rx_Config::get('currentLanguage', true, $cfg);
        }

        $instance->_initialized = true;
    }

    /**
     * Set translation adapter to use
     *
     * @param Zend_Translate_Adapter $adapter Adapter to use for translation
     * @param boolean $register               OPTIONAL true to register given adapter as default adapter for Zend components
     * @return void
     * @throws Rx_Exception
     */
    public static function setAdapter($adapter, $register = true)
    {
        if ($adapter instanceof Zend_Translate) {
            $adapter = $adapter->getAdapter();
        }
        if (!$adapter instanceof Zend_Translate_Adapter) {
            throw new Rx_Exception('Translation adapter must be instance of Zend_Translate_Adapter');
        }
        $instance = self::getInstance();
        $instance->_adapter = $adapter;
        // If no logger is set for adapter - install custom logger
        // that will log missed translations through Rx_Translate
        if (!$adapter->getOptions('log')) {
            $log = new Zend_Log(new Rx_Log_Writer_Translate());
            $adapter->setOptions(array(
                'disableNotices'  => true,
                'logUntranslated' => true,
                'log'             => $log,
                'logMessage'      => '%message%|%locale%',
            ));
        }
        if ($register) {
            Zend_Registry::set('Zend_Translate', $adapter);
            Zend_Form::setDefaultTranslator($adapter);
            Zend_Validate::setDefaultTranslator($adapter);
        }
    }

    /**
     * Get translation adapter that is used by Rx_Translate itself
     *
     * @return Zend_Translate_Adapter
     * @throws Rx_Exception
     */
    public static function getAdapter()
    {
        $instance = self::getInstance();
        if (!$instance->_adapter instanceof Zend_Translate_Adapter) {
            throw new Rx_Exception(__CLASS__ . ' must be initialized by defining translation adapter in any of acceptable ways before first use');
        }
        return ($instance->_adapter);
    }

    /**
     * Set information required for automatic loading of translation vocabularies on language switching
     *
     * @param array|Zend_Config $options Options for loading translated resources by Zend_Translate_Adapter
     * @return void
     * @see Zend_Translate_Adapter#addTranslation()
     */
    public static function setTranslateOptions($options)
    {
        $instance = self::getInstance();
        $options = $instance->normalizeOptions($options);
        $instance->_translateOptions = array_merge($instance->_translateOptions, $options);
    }

    /**
     * Add translation data for translation adapter
     *
     * @param array|Zend_Config $options Options for loading translated resources by Zend_Translate_Adapter
     * @param boolean $useAsDefault      OPTIONAL true to use $data and $options for adding translation data for other languages too
     * @return void
     * @see Zend_Translate_Adapter#addTranslation()
     * @throws Zend_Translate_Exception
     */
    public static function addTranslation($options, $useAsDefault = true)
    {
        $instance = self::getInstance();
        $options = $instance->normalizeOptions($options);
        if ($useAsDefault) {
            $instance->setTranslateOptions($options);
        }
        $instance->getAdapter()->addTranslation($options);
    }

    /**
     * Normalize given options for Zend_Translate_Adapter
     *
     * @param array|Zend_Config $options Options for loading translated resources by Zend_Translate_Adapter
     * @return array
     */
    protected function normalizeOptions($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            return (array());
        }
        unset($options['adapter']);
        if (array_key_exists('content', $options)) {
            $options['content'] = Rx_Path::normalize($options['content']);
        }
        return ($options);
    }

    /**
     * Get translation of message with given Id in given locale
     *
     * @param string $message                   Message or message Id to translate
     * @param boolean $required                 OPTIONAL true to throw warning message if no translation can be found, false to skip all warnings.
     *                                          Can be skipped, true will be mean in this case
     * @param string|Zend_Locale|null $locale   OPTIONAL Locale to translate message to (current language will be used by default)
     * @param Zend_Translate|null $translator   OPTIONAL Zend_Translate object to use for translation
     * @return string                           Translated message
     */
    public static function translate($message)
    {
        $instance = self::getInstance();
        $_error = false;
        $required = true;
        $args = func_get_args();
        array_shift($args);
        $a = array_shift($args);
        if (($a === true) || ($a === false)) {
            $required = $a;
            $a = array_shift($args);
        }
        $locale = $a;
        if ($locale === null) {
            $locale = Rx_Language::getLanguage(true);
        }
        $translator = array_shift($args);
        if (!$translator instanceof Zend_Translate) {
            $translator = $instance->getAdapter();
        }
        // We should keep message text for further use
        $_message = $message;
        if (is_array($message)) {
            $_message = array_shift($_message);
        }

        $result = $translator->translate($message, $locale);
        if (($result === null) || ($result === false) || ($result == $_message)) {
            $_error |= $instance->reportMissedTranslation($_message, $locale, $required, true);
            $result = $translator->translate($message, Rx_Language::getDefaultLanguage(true));
            if (($result === null) || ($result === false) || ($result == $_message)) {
                $_error |= $instance->reportMissedTranslation($_message, $locale, $required, false);
                $result = $_message;
            }
        }

        if (($_error) && ($instance->_missedClass)) {
            $result = '<span class="' . htmlentities($instance->_missedClass) . '">' . $result . '</span>';
        }

        // If we get not required message Id for translation and not obligated to report
        // translation errors for non-required messages - then return null so message Id
        // will not be sent to page
        if ((self::isMessageId(
                $_message
            )) && ($_message == $result) && (!$required) && (!$instance->_reportNotRequired)
        ) {
            $result = null;
        }

        return ($result);
    }

    /**
     * Check if given message is actually message id instead of text
     *
     * @param string $message Message to check
     * @return boolean
     */
    public static function isMessageId($message)
    {
        $adapter = self::getInstance()->_adapter;
        if (method_exists($adapter, 'isMessageId')) {
            return ($adapter->isMessageId($message));
        } else {
            return (preg_match('/^[a-z0-9\-\_\.]+(_[a-z0-9\-\_\.]+)+$/', $message));
        }
    }

    /**
     * Report missed translation
     *
     * @param string $message   Message that miss translation
     * @param string $language  Language, translation is missed for
     * @param boolean $required OPTIONAL true if message was required to be translated, false if its translation was optional
     * @param boolean $current  OPTIONAL true if message translation is missed in current language, false if it is missed in default language
     * @return boolean              true to treat missed translation as error, false if not
     */
    public static function reportMissedTranslation($message, $language, $required = true, $current = true)
    {
        $instance = self::getInstance();
        $report = ((($required) || ($instance->_reportNotRequired)) &&
            ((!$current) || ($instance->_reportCurrentLanguage)));
        if (!$report) {
            return (false);
        }
        // We must not treat as errors texts that are same as original message
        // if they're not Ids in a case if they're being translated to default
        // language since no translation is required at all
        if ((!self::isMessageId($message)) && (Rx_Language::compare($language, Rx_Language::getDefaultLanguage()))) {
            return (false);
        }
        $hash = strtolower(md5($message));
        if (in_array($hash, $instance->_reported)) {
            return (true);
        }
        $_reported = false;
        if ($instance->_reportPath) {
            $path = $instance->_reportPath . $hash . '.dat';
            if (!file_exists($path)) {
                $info = array(
                    'message'  => $message,
                    'required' => $required,
                    'language' => $language,
                    'uri'      => null,
                    'trace'    => Rx_ErrorsHandler::formatBacktrace(debug_backtrace()),
                );
                $request = Zend_Controller_Front::getInstance()->getRequest();
                if ($request instanceof Zend_Controller_Request_Http) {
                    $info['uri'] = $request->getRequestUri();
                }
                file_put_contents($path, serialize($info));
            } else {
                $_reported = true;
            }
        }
        if (($instance->_missedError) && (!$_reported)) {
            trigger_error(
                'Missed translation for message "' . $message . '", language: "' . $language . '"',
                E_USER_WARNING
            );
        }

        $instance->_reported[] = $hash;
        return (true);
    }

    /**
     * Implementation of Rx_Notify_Observer interface
     * Handle given notification event
     *
     * @param Rx_Notify_Event $event Notification event object
     * @return void
     */
    public function handleNotify($event)
    {
        switch ($event->getType()) {
            case 'rx_language_changed':
                $language = $event->language;
                // Load translations for new current language if we don't have them yet
                $adapter = $this->getAdapter();
                $list = $adapter->getList();
                if (!is_array($list)) // Workaround for ZF-10676
                {
                    $list = array();
                }
                if ((!in_array($language, $list)) &&
                    (array_key_exists('content', $this->_translateOptions))
                ) // Only add translation if we have translation content defined
                {
                    $options = array_merge(
                        $this->_translateOptions,
                        array(
                            'locale' => $language,
                        )
                    );
                    $adapter->addTranslation($options);
                }
                $adapter->setLocale($language);
                break;
        }
    }

}

if (!function_exists('_t')) {
    function _t($message)
    {
        return (Rx_Translate::translate($message));
    }
}
