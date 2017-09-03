<?php

class Rx_Currency
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Currency $_instance
     */
    protected static $_instance = null;
    /**
     * List of available currencies in application
     *
     * @var array $_currencies
     */
    protected $_currencies = array('USD');
    /**
     * Default currency
     *
     * @var string $_default
     */
    protected $_default = null;
    /**
     * Current currency
     *
     * @var string $_current
     */
    protected $_current = null;
    /**
     * Exchange rates for currencies
     *
     * @var array $_rates
     */
    protected $_rates = array();
    /**
     * Instance of Zend_Currency to use for currency formatting
     *
     * @var Zend_Currency $_currency
     */
    protected $_currency = null;
    /**
     * Zend_Currency formatting options
     *
     * @var array $_options
     * @see Zend_Currency#$_options
     */
    protected $_options = array();
    /**
     * true if object was initialized already, false if not
     *
     * @var boolean $_initialized
     */
    protected $_initialized = false;
    /**
     * Mapping table from currency to locale identifier.
     * Generated based on information from supplementalData.xml at Zend_Locale
     *
     * @var array $_currencyMap
     */
    protected $_currencyMap = array(
        'AED' => 'ar_AE',
        'AFN' => 'fa_AF',
        'ALL' => 'sq_AL',
        'AMD' => 'hy_AM',
        'ARS' => 'es_AR',
        'AUD' => 'en_AU',
        'AZN' => 'az_AZ',
        'BAM' => 'bs_BA',
        'BDT' => 'bn_BD',
        'BGN' => 'bg_BG',
        'BHD' => 'ar_BH',
        'BND' => 'ms_BN',
        'BOB' => 'es_BO',
        'BRL' => 'pt_BR',
        'BTN' => 'dz_BT',
        'BWP' => 'en_BW',
        'BYR' => 'be_BY',
        'BZD' => 'en_BZ',
        'CAD' => 'en_CA',
        'CDF' => 'ln_CD',
        'CHF' => 'de_CH',
        'CLP' => 'es_CL',
        'CNY' => 'zh_CN',
        'COP' => 'es_CO',
        'CRC' => 'es_CR',
        'CZK' => 'cs_CZ',
        'DJF' => 'aa_DJ',
        'DKK' => 'da_DK',
        'DOP' => 'es_DO',
        'DZD' => 'ar_DZ',
        'EEK' => 'et_EE',
        'EGP' => 'ar_EG',
        'ERN' => 'ti_ER',
        'ETB' => 'am_ET',
        'EUR' => 'de_DE',
        'GBP' => 'en_GB',
        'GEL' => 'ka_GE',
        'GHS' => 'ee_GH',
        'GNF' => 'kpe_GN',
        'GTQ' => 'es_GT',
        'HKD' => 'zh_HK',
        'HNL' => 'es_HN',
        'HRK' => 'hr_HR',
        'HUF' => 'hu_HU',
        'IDR' => 'id_ID',
        'ILS' => 'he_IL',
        'INR' => 'hi_IN',
        'IQD' => 'ar_IQ',
        'IRR' => 'fa_IR',
        'ISK' => 'is_IS',
        'JMD' => 'en_JM',
        'JOD' => 'ar_JO',
        'JPY' => 'ja_JP',
        'KES' => 'sw_KE',
        'KGS' => 'ky_KG',
        'KHR' => 'km_KH',
        'KRW' => 'ko_KR',
        'KWD' => 'ar_KW',
        'KZT' => 'kk_KZ',
        'LAK' => 'lo_LA',
        'LBP' => 'ar_LB',
        'LKR' => 'si_LK',
        'LRD' => 'kpe_LR',
        'LTL' => 'lt_LT',
        'LVL' => 'lv_LV',
        'LYD' => 'ar_LY',
        'MAD' => 'ar_MA',
        'MDL' => 'ro_MD',
        'MKD' => 'mk_MK',
        'MMK' => 'my_MM',
        'MNT' => 'mn_MN',
        'MOP' => 'zh_MO',
        'MVR' => 'dv_MV',
        'MWK' => 'ny_MW',
        'MXN' => 'es_MX',
        'MYR' => 'ms_MY',
        'NAD' => 'en_NA',
        'NGN' => 'yo_NG',
        'NIO' => 'es_NI',
        'NOK' => 'nb_NO',
        'NPR' => 'ne_NP',
        'NZD' => 'en_NZ',
        'OMR' => 'ar_OM',
        'PAB' => 'es_PA',
        'PEN' => 'es_PE',
        'PHP' => 'fil_PH',
        'PKR' => 'ur_PK',
        'PLN' => 'pl_PL',
        'PYG' => 'es_PY',
        'QAR' => 'ar_QA',
        'RON' => 'ro_RO',
        'RSD' => 'sr_RS',
        'RUB' => 'ru_RU',
        'RWF' => 'rw_RW',
        'SAR' => 'ar_SA',
        'SDG' => 'ar_SD',
        'SEK' => 'sv_SE',
        'SGD' => 'en_SG',
        'SOS' => 'so_SO',
        'SYP' => 'ar_SY',
        'SZL' => 'ss_SZ',
        'THB' => 'th_TH',
        'TJS' => 'tg_TJ',
        'TND' => 'ar_TN',
        'TOP' => 'to_TO',
        'TRY' => 'tr_TR',
        'TTD' => 'en_TT',
        'TWD' => 'zh_TW',
        'TZS' => 'sw_TZ',
        'UAH' => 'uk_UA',
        'USD' => 'en_US',
        'UYU' => 'es_UY',
        'UZS' => 'uz_UZ',
        'VEF' => 'es_VE',
        'VND' => 'vi_VN',
        'XAF' => 'ln_CG',
        'XOF' => 'fr_SN',
        'YER' => 'ar_YE',
        'ZAR' => 'en_ZA',
        'ZWL' => 'en_ZW',
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
     * @return Rx_Currency
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
     * @param array|string|null $currencies List of available currencies in application (optional, default configuration will be used if skipped)
     * @param string|null $default          Default currency (optional, first currency from currencies list will be used as default)
     * @param string|null $current          Currently selected currency (optional, will be attempted to get from Rx_AppState if missed)
     * @param array|null $rates             Exchange rates between default and available currencies (optional, will be attempted to take from configuration)
     * @param array|null $options           Additional options for Zend_Currency (optional)
     * @return void
     */
    public static function bootstrap(
        $currencies = null,
        $default = null,
        $current = null,
        $rates = null,
        $options = null
    ) {
        $instance = self::getInstance();
        if ($instance->_initialized) {
            return;
        }
        // Zend_Currency depends on Zend_Locale and Zend_Locale itself is initialized by Rx_Language,
        // so force Rx_Language to be bootstrapped at a time of our bootstrap. Also Rx_Language initializes
        // cache for Zend_Locale which is also used by Zend_Currency so we don't need to initialize it separately
        Rx_Language::getInstance();
        // Register cache for Zend_Currency if it is not registered yet
        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
        } elseif (Zend_Registry::isRegistered('Zend_Cache')) {
            $cache = Zend_Registry::get('Zend_Cache');
        } else {
            $cache = null;
        }
        if ($cache instanceof Zend_Cache_Core) {
            if (!Zend_Currency::hasCache()) {
                Zend_Currency::setCache($cache);
            }
        }
        // If no information about currencies is provided - attempt to get it from configuration
        if (!$currencies) {
            $currencies = trim(Rx_Config::get('rx.currency.available'));
            $currencies = explode(',', $currencies);
            if (!$currencies) {
                $currencies = $instance->_currencies;
            }
        }
        if (!$default) {
            $default = trim(Rx_Config::get('rx.currency.default'));
        }
        self::setAvailableCurrencies($currencies, $default);

        // Prepare list of exchange rates
        $_rates = array();
        $cfgRates = Rx_Config::getArray('rx.currency.exchange');
        foreach ($cfgRates as $name => $rate) {
            $_rates[strtoupper(trim($name))] = $rate;
        }
        if (!is_array($rates)) {
            $rates = $_rates;
        } else {
            $r = array();
            foreach ($rates as $k => $v) {
                $r[strtoupper(trim($k))] = $k;
            }
            foreach ($_rates as $name => $rate) {
                if (!array_key_exists($name, $r)) {
                    $rates[$name] = $rate;
                }
            }
        }
        self::setExchangeRate($rates);

        // If no options are provided - use default ones
        if (!is_array($options)) {
            $options = array(
                'position'  => Zend_Currency::STANDARD,
                'display'   => Zend_Currency::USE_SYMBOL,
                'precision' => 2,
            );
        }
        self::setFormatOptions($options);
        // If no current currency is provided - attempt to get it from Rx_AppState
        if (!$current) {
            $current = Rx_AppState::get($instance, self::getDefaultCurrency());
        }
        // We must set currency to fire currency selection notification event
        self::setCurrency($current);
        $instance->_initialized = true;
    }

    /**
     * Set list of available currencies
     *
     * @param array|string $currencies List of available currencies in application
     * @param string $default          Default currency (optional, first currency from currencies list will be used as default)
     * @return void
     */
    public static function setAvailableCurrencies($currencies, $default = null)
    {
        $instance = self::getInstance();
        if (!is_array($currencies)) {
            $currencies = array($currencies);
        }
        foreach ($currencies as $k => $currency) {
            $currency = $instance->normalize($currency);
            if ($currency !== false) {
                $currencies[$k] = $currency;
            } else {
                unset($currencies[$k]);
            }
        }
        $currencies = array_values($currencies); // Resetting keys
        if (sizeof($currencies)) {
            $instance->_currencies = $currencies;
        } else {
            trigger_error('No valid currency identifiers are found in given currencies list', E_USER_WARNING);
        }
        if ($default !== null) {
            $default = $instance->normalize($default, true);
        }
        if (!$default) {
            $default = $instance->_currencies[0];
        }
        $instance->_default = $default;
        // DO NOT set current currency here because it may cause duplicated notification event firing
        // and hence duplicated re-initialization of classes that handles currency setting event
    }

    /**
     * Get list of available currencies
     *
     * @return array
     */
    public static function getAvailableCurrencies()
    {
        return (self::getInstance()->_currencies);
    }

    /**
     * Set new current currency
     *
     * @param string $currency New currency to use as current
     * @return boolean              true if currency is changed, false if error occurs (e.g. currency is not in list of available currencies)
     * @event rx_currency_changed   Application currency was changed
     */
    public static function setCurrency($currency)
    {
        $instance = self::getInstance();
        if ($currency !== null) {
            $currency = $instance->normalize($currency, true);
        } else {
            $currency = $instance->_default;
        }
        if (!$currency) {
            return (false);
        }
        if ($currency != $instance->_current) {
            $instance->_current = $currency;
            // We must tune currency formatting options for internal Zend_Currency object
            // to be sure that we will have correct output for new currency
            // Since currency is normalized and normalize() uses $_currencyMap - we can be sure that we have currency in it
            $locale = $instance->_currencyMap[$currency];
            try {
                $obj = new Zend_Currency($currency, $locale);
                $obj->setFormat($instance->_options);
            } catch (Zend_Currency_Exception $e) {
                trigger_error(
                    'Failed to initialize internal currency object (' . $e->getMessage() . ')',
                    E_USER_WARNING
                );
                return (false);
            }
            $instance->_currency = $obj;
            // Notify other objects about new current currency in application
            Rx_Notify::notify(
                'rx_currency_changed',
                array(
                    'currency' => $currency,
                ),
                $instance
            );
            // Store currency into application state so it will persist among requests
            Rx_AppState::set($instance, $currency, true);
        }
        return (true);
    }

    /**
     * Get current currency
     *
     * @return string
     */
    public static function getCurrency()
    {
        return (self::getInstance()->_current);
    }

    /**
     * Get default currency
     *
     * @return string
     */
    public static function getDefaultCurrency()
    {
        return (self::getInstance()->_default);
    }

    /**
     * Get Zend_Currency object that is used by Rx_Currency
     *
     * @return Zend_Currency
     */
    public static function getCurrencyObj()
    {
        return (self::getInstance()->_currency);
    }

    /**
     * Set exchange rate between currencies
     *
     * @param string|array $currency        Either name of currency or array for setting exchange rate for multiple currencies (in a form currency=>rate)
     *                                      Currency identifier can be in a form of:
     *                                      - Single identifier (e.g. EUR) of TARGET currency, in this case rate will be treated as exchange rate from DEFAULT to GIVEN currency
     *                                      - Double identifier (e.g. USDEUR or USD-EUR), in this case rate will be treated as exchange rate between given currencies (USD->EUR in this example)
     * @param float|null $rate              Exchange rate for given currency (used only if currency is passed as string)
     * @return void
     */
    public static function setExchangeRate($currency, $rate = null)
    {
        $instance = self::getInstance();
        $rates = (is_array($currency)) ? $currency : array($currency => $rate);
        foreach ($rates as $currency => $rate) {
            if (preg_match('/^([a-z]{3})\-?([a-z]{3})$/i', $currency, $t)) {
                $from = $instance->normalize($t[1], true);
                $to = $instance->normalize($t[2], true);
            } else {
                $from = $instance->_default;
                $to = $instance->normalize($currency, true);
            }
            if ((!$from) || (!$to)) {
                continue;
            }
            if (!is_numeric($rate)) {
                trigger_error('Exchange rate must be numeric (given: ' . $rate . ')', E_USER_WARNING);
                continue;
            }
            $instance->_rates[$from . '-' . $to] = $rate;
        }
    }

    /**
     * Get exchange rate for given currency
     *
     * @param string $to        Target currency to get exchange rate for
     * @param string|null $from Source currency to to get exchange for (or null to use default currency as source)
     * @return float|null
     */
    public static function getExchangeRate($to, $from = null)
    {
        $instance = self::getInstance();
        $to = $instance->normalize($to, true);
        if ($from !== null) {
            $from = $instance->normalize($from, true);
        } else {
            $from = $instance->_default;
        }
        if ((!$from) || (!$to)) {
            return (false);
        }
        if ($from == $to) {
            return (1);
        }
        $id = $from . '-' . $to;
        if (array_key_exists($id, $instance->_rates)) {
            return ($instance->_rates[$id]);
        }
        return (null);
    }

    /**
     * Get all available exchange rates for given currency
     *
     * @param string|null $currency     Currency to get exchange rates for (or null to get all defined exchange rates)
     * @return array                    If currency is given - result will be returned in a form: currency=>rate (e.g. EUR=>1.34 where EUR is TARGET currency)
     *                                  If no currency is given - result will be returned in a form: currencyPair=> rate (e.g. USD-EUR=>1.34)
     */
    public static function getExchangeRates($currency = null)
    {
        $rates = array();
        $instance = self::getInstance();
        if ($currency === null) {
            return ($instance->_rates);
        }
        $currency = $instance->normalize($currency, true);
        if (!$currency) {
            return ($rates);
        }
        foreach ($instance->_rates as $id => $rate) {
            $t = explode('-', $id, 2);
            $src = array_shift($t);
            $dest = array_shift($t);
            if ($t == $currency) {
                $rates[$dest] = $rate;
            }
        }
        return ($rates);
    }

    /**
     * Set formatting options for currency formatting
     *
     * @param array $options Formatting options to set
     * @return void
     * @see Zend_Currency#setFormat()
     */
    public static function setFormatOptions($options)
    {
        if (!is_array($options)) {
            return;
        }
        $instance = self::getInstance();
        foreach ($options as $k => $v) {
            $instance->_options[$k] = $v;
        }
    }

    /**
     * Format given value as a currency
     *
     * @param float $value          Value to format
     * @param string|null $currency Currency to use for value formatting (optional, current currency will be used by default)
     * @param boolean $exchange     true to exchange given value from DEFAULT to currency defined into $currency before formatting, false to skip exchanging
     * @param array|null $options   Additional options to use for currency formatting
     * @return string|false             Formatted value or false in a case of error (e.g. invalid currency or formatting option)
     * @see Zend_Currency#toCurrency()
     */
    public static function format($value, $currency = null, $exchange = true, $options = null)
    {
        $instance = self::getInstance();
        if ($currency !== null) {
            $currency = $instance->normalize($currency, true);
        } else {
            $currency = $instance->_current;
        }
        if ($currency === false) {
            return (false);
        }
        if ($exchange) {
            $value = $instance->exchange($value, $currency);
        }
        if ($value === false) {
            return (false);
        }
        if (!is_array($options)) {
            $options = array();
        }
        $_options = $instance->_options;
        foreach ($options as $k => $v) {
            $_options[$k] = $v;
        }
        if ($currency) {
            $_options['currency'] = $currency;
        }
        try {
            // We should only set locale if it differs from locale, currently set in Zend_Currency object
            // because setting locale is relatively expensive operation due to work with Zend_Locale_Data
            $_locale = $instance->_currencyMap[$_options['currency']];
            if ($instance->_currency->getLocale() != $_locale) {
                $instance->_currency->setLocale($_locale);
            }
            $result = $instance->_currency->toCurrency($value, $_options);
        } catch (Zend_Currency_Exception $e) {
            trigger_error('Failed to format currency value (' . $e->getMessage() . ')', E_USER_WARNING);
            return (false);
        }
        return ($result);
    }

    /**
     * Exchange given value into different currency
     *
     * @param float $value      Value to exchange
     * @param string|null $to   Currency to exchange value to (optional, by default exchange to current currency)
     * @param string|null $from Currency of value (optional, by default value is treated as default currency)
     * @return float|boolean        Exchanged value or false in a case of error (e.g. invalid currency or missed exchange rate)
     */
    public static function exchange($value, $to = null, $from = null)
    {
        $instance = self::getInstance();
        if ($from !== null) {
            $from = $instance->normalize($from, true);
        } else {
            $from = $instance->_default;
        }
        if ($to !== null) {
            $to = $instance->normalize($to, true);
        } else {
            $to = $instance->_current;
        }
        if (($from === false) || ($to === false)) {
            return (false);
        }
        if ($from == $to) {
            return ($value);
        }
        $id = $from . '-' . $to;
        $rid = $to . '-' . $from;
        if (isset($instance->_rates[$id])) {
            $result = $value * $instance->_rates[$id];
        } elseif (isset($instance->_rates[$rid])) {
            $result = $value / $instance->_rates[$rid];
        } else {
            trigger_error('Exchange rate is not defined (' . $from . '->' . $to . ')', E_USER_WARNING);
            return (false);
        }
        return ($result);
    }

    /**
     * Normalize given currency identifier
     *
     * @param string $currency     Currency identifier to normalize
     * @param boolean $checkExists true to check if given currency identifier is exists into list of available currencies, false to skip this check (default)
     * @return string|boolean
     */
    protected function normalize($currency, $checkExists = false)
    {
        static $cache = array();

        $currency = trim($currency);
        $_key = $currency;
        if (isset($cache[$_key])) {
            return ($cache[$_key]);
        }
        $currency = strtoupper($currency);
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            trigger_error('Invalid currency identifier: ' . $currency, E_USER_WARNING);
            $cache[$_key] = false;
            return (false);
        }
        // @TRICK This is actually a hack, correct way would be to call Zend_Locale::getTranslation($currency,'regionToCurrency')
        // but this is too slow and we have list of supported currencies directly into object :-)
        if (!isset($this->_currencyMap[$currency])) {
            trigger_error('Unknown currency identifier: ' . $currency, E_USER_WARNING);
            $cache[$_key] = false;
            return (false);
        }
        $cache[$_key] = $currency;
        if ($checkExists) {
            if (!in_array($currency, $this->_currencies)) {
                trigger_error(
                    'Currency is not exists in list of available currencies (given: ' . $currency . ')',
                    E_USER_WARNING
                );
                $currency = false;
            }
        }
        return ($currency);
    }

}
