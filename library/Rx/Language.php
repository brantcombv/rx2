<?php

class Rx_Language
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Language $_instance
     */
    protected static $_instance = null;
    /**
     * List of available languages in application
     *
     * @var array $_languages
     */
    protected $_languages = array('en_US');
    /**
     * Default language
     *
     * @var string $_default
     */
    protected $_default = null;
    /**
     * Current language
     *
     * @var string $_current
     */
    protected $_current = null;
    /**
     * true if object was initialized already, false if not
     *
     * @var boolean $_initialized
     */
    protected $_initialized = false;
    /**
     * Mapping table to expand language identifier
     * Generated based on information from likelySubtags.xml at Zend_Locale
     *
     * @var array $_localeUpgrade
     */
    protected $_localeUpgrade = array(
        'aa' => 'aa_ET',
        'ab' => 'ab_GE',
        'af' => 'af_ZA',
        'ak' => 'ak_GH',
        'am' => 'am_ET',
        'ar' => 'ar_EG',
        'as' => 'as_IN',
        'av' => 'av_RU',
        'ay' => 'ay_BO',
        'az' => 'az_AZ',
        'ba' => 'ba_RU',
        'be' => 'be_BY',
        'bg' => 'bg_BG',
        'bi' => 'bi_VU',
        'bn' => 'bn_BD',
        'bo' => 'bo_CN',
        'bs' => 'bs_BA',
        'ca' => 'ca_ES',
        'ce' => 'ce_RU',
        'ch' => 'ch_GU',
        'cs' => 'cs_CZ',
        'cy' => 'cy_GB',
        'da' => 'da_DK',
        'de' => 'de_DE',
        'dv' => 'dv_MV',
        'dz' => 'dz_BT',
        'ee' => 'ee_GH',
        'el' => 'el_GR',
        'en' => 'en_US',
        'es' => 'es_ES',
        'et' => 'et_EE',
        'eu' => 'eu_ES',
        'fa' => 'fa_IR',
        'fi' => 'fi_FI',
        'fj' => 'fj_FJ',
        'fo' => 'fo_FO',
        'fr' => 'fr_FR',
        'fy' => 'fy_NL',
        'ga' => 'ga_IE',
        'gd' => 'gd_GB',
        'gl' => 'gl_ES',
        'gn' => 'gn_PY',
        'gu' => 'gu_IN',
        'ha' => 'ha_NG',
        'he' => 'he_IL',
        'hi' => 'hi_IN',
        'ho' => 'ho_PG',
        'hr' => 'hr_HR',
        'ht' => 'ht_HT',
        'hu' => 'hu_HU',
        'hy' => 'hy_AM',
        'id' => 'id_ID',
        'ig' => 'ig_NG',
        'ii' => 'ii_CN',
        'is' => 'is_IS',
        'it' => 'it_IT',
        'iu' => 'iu_CA',
        'ja' => 'ja_JP',
        'jv' => 'jv_ID',
        'ka' => 'ka_GE',
        'kk' => 'kk_KZ',
        'kl' => 'kl_GL',
        'km' => 'km_KH',
        'kn' => 'kn_IN',
        'ko' => 'ko_KR',
        'ks' => 'ks_IN',
        'ku' => 'ku_IQ',
        'ky' => 'ky_KG',
        'la' => 'la_VA',
        'lb' => 'lb_LU',
        'ln' => 'ln_CD',
        'lo' => 'lo_LA',
        'lt' => 'lt_LT',
        'lv' => 'lv_LV',
        'mg' => 'mg_MG',
        'mh' => 'mh_MH',
        'mi' => 'mi_NZ',
        'mk' => 'mk_MK',
        'ml' => 'ml_IN',
        'mn' => 'mn_MN',
        'mr' => 'mr_IN',
        'mt' => 'mt_MT',
        'my' => 'my_MM',
        'na' => 'na_NR',
        'nb' => 'nb_NO',
        'ne' => 'ne_NP',
        'nl' => 'nl_NL',
        'nn' => 'nn_NO',
        'nr' => 'nr_ZA',
        'ny' => 'ny_MW',
        'oc' => 'oc_FR',
        'om' => 'om_ET',
        'or' => 'or_IN',
        'os' => 'os_GE',
        'pa' => 'pa_IN',
        'pl' => 'pl_PL',
        'ps' => 'ps_AF',
        'pt' => 'pt_BR',
        'qu' => 'qu_PE',
        'rm' => 'rm_CH',
        'rn' => 'rn_BI',
        'ro' => 'ro_RO',
        'ru' => 'ru_RU',
        'rw' => 'rw_RW',
        'sa' => 'sa_IN',
        'sd' => 'sd_IN',
        'se' => 'se_NO',
        'sg' => 'sg_CF',
        'si' => 'si_LK',
        'sk' => 'sk_SK',
        'sl' => 'sl_SI',
        'sm' => 'sm_WS',
        'so' => 'so_SO',
        'sq' => 'sq_AL',
        'sr' => 'sr_RS',
        'ss' => 'ss_ZA',
        'st' => 'st_ZA',
        'su' => 'su_ID',
        'sv' => 'sv_SE',
        'sw' => 'sw_TZ',
        'ta' => 'ta_IN',
        'te' => 'te_IN',
        'tg' => 'tg_TJ',
        'th' => 'th_TH',
        'ti' => 'ti_ET',
        'tk' => 'tk_TM',
        'tl' => 'tl_PH',
        'tn' => 'tn_ZA',
        'to' => 'to_TO',
        'tr' => 'tr_TR',
        'ts' => 'ts_ZA',
        'tt' => 'tt_RU',
        'tw' => 'tw_GH',
        'ty' => 'ty_PF',
        'ug' => 'ug_CN',
        'uk' => 'uk_UA',
        'ur' => 'ur_PK',
        'uz' => 'uz_UZ',
        've' => 've_ZA',
        'vi' => 'vi_VN',
        'wo' => 'wo_SN',
        'xh' => 'xh_ZA',
        'yo' => 'yo_NG',
        'za' => 'za_CN',
        'zh' => 'zh_CN',
        'zu' => 'zu_ZA',
    );

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
     * @return Rx_Language
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
     * Initialize class
     * Can be called directly in bootstrap or indirectly by accessing any method of class
     *
     * @param array|string|Zend_Locale $languages List of available languages in application (optional, default configuration will be used if skipped)
     * @param string|Zend_Locale $default         Language to use by default (optional, first language from languages list will be used as default)
     * @param string|Zend_Locale $current         Currently selected language (optional, will be attempted to get from Rx_AppState if missed)
     * @return void
     */
    public static function bootstrap($languages = null, $default = null, $current = null)
    {
        $instance = self::getInstance();
        if ($instance->_initialized) {
            return;
        }
        // Disable PHP5 warning about setting timezone
        date_default_timezone_set(@date_default_timezone_get());
        // If we don't have Zend_Locale initialized - do it now
        $locale = null;
        if (Zend_Registry::isRegistered('Zend_Locale')) {
            $locale = Zend_Registry::get('Zend_Locale');
        }
        if (!$locale instanceof Zend_Locale) {
            // Locale must be supported with cache so we should expect to get cache from registry
            if (Zend_Registry::isRegistered('cache')) {
                $cache = Zend_Registry::get('cache');
            } elseif (Zend_Registry::isRegistered('Zend_Cache')) {
                $cache = Zend_Registry::get('Zend_Cache');
            } else {
                $cache = null;
            }
            if ($cache instanceof Zend_Cache_Core) {
                if (!Zend_Locale::hasCache()) {
                    $cache = clone($cache);
                    $cache->setOption('cache_id_prefix', 'locale_');
                    Zend_Locale::setCache($cache);
                }
            } else {
                trigger_error(
                    __CLASS__ . ' expects to get cache object from registry by "cache" key',
                    E_USER_WARNING
                );
            }
            $locale = new Zend_Locale();
            Zend_Registry::set('Zend_Locale', $locale);
        }
        // If no languages list is provided - attempt to get it from configuration
        if (!$languages) {
            $languages = trim(Rx_Config::get('rx.language.available'));
            $languages = explode(',', $languages);
            if (!$languages) {
                $languages = $instance->_languages;
            }
        }
        if (!$default) {
            $default = trim(Rx_Config::get('rx.language.default'));
        }
        self::setAvailableLanguages($languages, $default);
        // If no current language is provided - attempt to get it from Rx_AppState
        if (!$current) {
            $current = Rx_AppState::get($instance, self::getDefaultLanguage(true));
        }
        // We must set language to initialize Zend_Locale and fire language selection notification event
        self::setLanguage($current);
        $instance->_initialized = true;
    }

    /**
     * Set list of available languages
     *
     * @param array|string|Zend_Locale $languages List of available languages in application
     * @param string|Zend_Locale $default         Language to use by default (optional, first language from languages list will be used as default)
     * @return void
     */
    public static function setAvailableLanguages($languages, $default = null)
    {
        $instance = self::getInstance();
        if (!is_array($languages)) {
            $languages = array($languages);
        }
        foreach ($languages as $k => $lang) {
            $lang = self::expand($lang);
            if ($lang !== false) {
                $languages[$k] = $lang;
            } else {
                unset($languages[$k]);
            }
        }
        $languages = array_values($languages); // Resetting keys
        if (sizeof($languages)) {
            $instance->_languages = $languages;
        } else {
            trigger_error('No valid language identifiers are found in given languages list', E_USER_WARNING);
        }
        if ($default !== null) {
            $default = self::expand($default);
            if (!in_array($default, $instance->_languages)) {
                trigger_error(
                    'Given default language identifier "' . $default . '" is not found in list of available languages (' . join(
                        ',',
                        $instance->_languages
                    ) . ')',
                    E_USER_WARNING
                );
                $default = null;
            }
        }
        if (!$default) {
            $default = $instance->_languages[0];
        }
        $instance->_default = $default;
        Zend_Locale::setDefault($default);
        // DO NOT set current language here because it may cause duplicated notification event firing
        // and hence duplicated re-initialization of classes that handles language setting event
    }

    /**
     * Get list of available languages
     *
     * @param boolean $complete true to get complete locale identifiers (e.g. en_US), false to just language identifier (e.g. en)
     * @return array
     */
    public static function getAvailableLanguages($complete = false)
    {
        $instance = self::getInstance();
        $languages = $instance->_languages;
        foreach ($languages as $k => $lang) {
            $languages[$k] = $instance->prepare($lang, $complete);
        }
        return ($languages);
    }

    /**
     * Set current language
     *
     * @param string|Zend_Locale $language New language to set or null to use default language
     * @return boolean                          true if language is changed, false if error occurs (e.g. language is not in list of available languages)
     * @event rx_language_changed               Application language was changed
     */
    public static function setLanguage($language = null)
    {
        $instance = self::getInstance();
        if ($language !== null) {
            $language = self::expand($language);
        } else {
            $language = $instance->_default;
        }
        if (!in_array($language, $instance->_languages)) {
            trigger_error(
                'Given language identifier "' . $language . '" is not found in list of available languages (' . join(
                    ',',
                    $instance->_languages
                ) . ')',
                E_USER_WARNING
            );
            return (false);
        }
        if ($language != $instance->_current) {
            $instance->_current = $language;
            Zend_Registry::get('Zend_Locale')->setLocale($language);
            // Notify other objects about new current language in application
            Rx_Notify::notify(
                'rx_language_changed',
                array(
                    'language' => $instance->prepare($language, false),
                    'locale'   => $language,
                ),
                $instance
            );
            // Store language into application state so it will persist among requests
            Rx_AppState::set($instance, $language, true);
        }
        return (true);
    }

    /**
     * Get current language
     *
     * @param boolean $complete true to get complete locale identifier (e.g. en_US), false to just language identifier (e.g. en)
     * @return string
     */
    public static function getLanguage($complete = false)
    {
        $instance = self::getInstance();
        $language = ($instance->_current) ? $instance->_current : $instance->_default;
        $language = $instance->prepare($language, $complete);
        return ($language);
    }

    /**
     * Get default language
     *
     * @param boolean $complete true to get complete locale identifier (e.g. en_US), false to just language identifier (e.g. en)
     * @return string
     */
    public static function getDefaultLanguage($complete = false)
    {
        $instance = self::getInstance();
        $language = $instance->prepare($instance->_default, $complete);
        return ($language);
    }

    /**
     * Convert given locale identifier into language identifier
     *
     * @param string|Zend_Locale $language Language identifier to convert (by default current language will be used)
     * @return string|false
     */
    public static function getLanguageId($language = null)
    {
        static $cache = array();

        if ($language === null) {
            $language = self::getLanguage(true);
        }
        $_key = $language;
        if (isset($cache[$_key])) {
            return ($cache[$_key]);
        }
        if (!Zend_Locale::isLocale($language, true)) {
            trigger_error('Invalid language identifier: ' . $language, E_USER_WARNING);
            $cache[$_key] = false;
            return (false);
        }
        if ($language instanceof Zend_Locale) {
            $language = $language->toString();
        }
        $p = explode('_', $language);
        $lang = strtolower(array_shift($p));
        $cache[$_key] = $lang;
        return ($lang);
    }

    /**
     * Convert given locale identifier into region identifier
     *
     * @param string|Zend_Locale $language      Language identifier to convert (by default current language will be used)
     * @param boolean $expand                   true to expand given language identifier before converting,
     *                                          false to return null if no region Id is available in given language identifier (default)
     * @return string|null|false
     */
    public static function getRegionId($language = null, $expand = false)
    {
        static $cache = array();

        if ($language === null) {
            $language = self::getLanguage(true);
        } elseif ($expand) {
            $language = self::expand($language);
        }
        $_key = $language . '-' . ((int)$expand);
        if (isset($cache[$_key])) {
            return ($cache[$_key]);
        }
        if (!Zend_Locale::isLocale($language, true)) {
            trigger_error('Invalid language identifier: ' . $language, E_USER_WARNING);
            $cache[$_key] = false;
            return (false);
        }
        if ($language instanceof Zend_Locale) {
            $language = $language->toString();
        }
        $p = explode('_', $language);
        array_shift($p);
        $region = array_shift($p);
        if (strlen($region)) {
            $region = strtoupper($region);
        }
        $cache[$_key] = $region;
        return ($region);
    }

    /**
     * Expand given language identifier to full locale identifier
     *
     * @param string|Zend_Locale $language Language identifier to expand
     * @return string|false
     */
    public static function expand($language)
    {
        static $cache = array();

        $language = trim($language);
        $_key = $language;
        if (isset($cache[$_key])) {
            return ($cache[$_key]);
        }
        if (!Zend_Locale::isLocale($language, true)) {
            trigger_error('Invalid language identifier: ' . $language, E_USER_WARNING);
            $cache[$_key] = false;
            return (false);
        }
        if ($language instanceof Zend_Locale) {
            $language = $language->toString();
        }
        $p = explode('_', $language);
        foreach ($p as $k => $v) {
            if ((strlen($v) < 2) || (strlen($v) > 3)) {
                unset($p[$k]);
            }
        }
        $lang = strtolower(array_shift($p));
        $region = strtoupper(array_shift($p));
        if (!strlen($region)) {
            $region = null;
            // If we don't know region - we should attempt to expand locale
            $instance = self::getInstance();
            if (array_key_exists($lang, $instance->_localeUpgrade)) {
                $locale = $instance->_localeUpgrade[$lang];
            } else {
                $locale = Zend_Locale::getTranslation($lang, 'localeUpgrade');
            }
            $p = explode('_', $locale);
            foreach ($p as $k => $v) {
                if ((strlen($v) < 2) || (strlen($v) > 3)) {
                    unset($p[$k]);
                }
            }
            $lang = strtolower(array_shift($p));
            $region = strtoupper(array_shift($p));
        }
        $language = $lang . (($region) ? '_' . $region : '');
        $cache[$_key] = $language;
        $cache[$language] = $language;
        return ($language);
    }

    /**
     * Compare given language identifiers
     *
     * @param string|Zend_Locale $lang1 First language identifier to compare
     * @param string|Zend_Locale $lang2 Second language identifier to compare
     * @param boolean $strict           true to compare both language and regions, false to compare just languages (default)
     * @return boolean
     */
    public static function compare($lang1, $lang2, $strict = false)
    {
        if ($strict) {
            $lang1 = self::expand($lang1);
            $lang2 = self::expand($lang2);
        } else {
            $lang1 = self::getLanguageId($lang1);
            $lang2 = self::getLanguageId($lang2);
        }
        if (($lang1 === false) || ($lang2 === false)) {
            return (false);
        }
        return ($lang1 == $lang2);
    }

    /**
     * Prepare given language for output through getter methods
     *
     * @param string $language  Language identifier to prepare (assume it is complete identifier)
     * @param boolean $complete true to get complete locale identifier (e.g. en_US), false to just language identifier (e.g. en)
     * @return string
     */
    protected function prepare($language, $complete = true)
    {
        if ($complete) {
            return ($language);
        }
        $p = explode('_', $language);
        $lang = array_shift($p);
        return ($lang);
    }

}
