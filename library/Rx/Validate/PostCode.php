<?php

class Rx_Validate_PostCode extends Rx_Validate_Abstract
{
    const INVALID = 'invalidPostCode';
    const NO_COUNTRY = 'noCountry';

    protected $_messageTemplates = array(
        self::INVALID    => 'Post code format is not valid for selected country (possible format(s): %format%)',
        self::NO_COUNTRY => 'Unable to validate post code because country is not selected',
    );

    protected $_messageVariables = array(
        'format' => 'validFormats',
    );

    protected $country = null;
    protected $validNoCountry = false;

    protected $validFormats = null;

    public function isValid($value, $context = null)
    {
        $country = ((is_array($context)) && (array_key_exists(
                $this->country,
                $context
            ))) ? $context[$this->country] : null;
        if (!$country) {
            if ($this->validNoCountry) {
                return (true);
            } else {
                $this->_error(self::NO_COUNTRY);
                return (false);
            }
        }

        $country = trim(strtoupper($country));
        $regexp = null;
        $format = null;

        switch ($country) {
            case 'FO': // Faeroe Islands
            case 'IS': // Iceland
                $regexp = '/^(' . $country . '[\s\-]*)?\d{3}$/i';
                $format = array(
                    '999',
                    $country . '-999',
                );
                break;

            case 'AL': // Albania
            case 'BG': // Bulgaria
            case 'CH': // Switzerland
            case 'CY': // Cyprus
            case 'DK': // Denmark
            case 'GL': // Greenland
            case 'GE': // Georgia
            case 'HT': // Haiti
            case 'LV': // Latvia
            case 'MD': // Moldova
            case 'MK': // Macedonia
            case 'NO': // Norway
            case 'SI': // Slovenia
                $regexp = '/^(' . $country . '[\s\-]*)?\d{4}$/i';
                $format = array(
                    '9999',
                    $country . '-9999',
                );
                break;

            case 'AX': // Aland Islands
            case 'BA': // Bosnia and Herzegovina
            case 'EE': // Estonia
            case 'FI': // Finland
            case 'GR': // Greece
            case 'HR': // Croatia
            case 'LT': // Lithuania
            case 'MC': // Monaco
            case 'ME': // Montenegro
            case 'RS': // Serbia
            case 'SM': // San Marino
            case 'TR': // Turkey
                $regexp = '/^(' . $country . '[\s\-]*)?\d{5}$/i';
                $format = array(
                    '99999',
                    $country . '-99999',
                );
                break;

            case 'BY': // Belarus
            case 'RO': // Romania
            case 'TJ': // Tajikistan
                $regexp = '/^(' . $country . '[\s\-]*)?\d{6}$/i';
                $format = array(
                    '999999',
                    $country . '-999999',
                );
                break;

            case 'AT': // Austria
            case 'BE': // Belgium
            case 'HU': // Hungary
            case 'LU': // Luxembourg
                $regexp = '/^(' . $country . '?[\s\-]*)?\d{4}$/i';
                $format = array(
                    '9999',
                    substr($country, 0, 1) . '-9999',
                    $country . '-9999',
                );
                break;

            case 'DE': // Germany
            case 'FR': // France
            case 'ES': // Spain
            case 'BL': // Saint Barthelemy
            case 'GF': // French Guiana
            case 'GP': // Guadeloupe
            case 'MF': // Saint Martin
            case 'MQ': // Martinique
            case 'NC': // New Caledonia
            case 'PF': // French Polynesia
            case 'PM': // Saint Pierre and Miquelon
            case 'RE': // Reunion
            case 'WF': // Wallis and Futuna
            case 'YT': // Mayotte
                $regexp = '/^(' . $country . '?[\s\-]*)?\d{5}$/i';
                $format = array(
                    '99999',
                    substr($country, 0, 1) . '-99999',
                    $country . '-99999',
                );
                break;

            case 'CO': // Colombia
            case 'GA': // Gabon
                $regexp = '/^\d{2}$/i';
                $format = array(
                    '99',
                );
                break;

            case 'MG': // Madagascar
            case 'LS': // Lesotho
            case 'PG': // Papua New Guinea
            case 'OM': // Oman
                $regexp = '/^\d{3}$/i';
                $format = array(
                    '999',
                );
                break;

            case 'AU': // Australia
            case 'NZ': // New Zealand
            case 'BO': // Bolivia
            case 'GW': // Guinea-Bissau
            case 'NE': // Niger
            case 'PY': // Paraguay
            case 'SD': // Sudan
            case 'TN': // Tunisia
            case 'CV': // Cape Verde
            case 'AM': // Armenia
            case 'ET': // Ethiopia
            case 'LR': // Liberia
            case 'BD': // Bangladesh
            case 'PH': // Philippines
            case 'LB': // Lebanon
            case 'ZA': // South Africa
            case 'CC': // Cocos Islands
            case 'CX': // Christmas Island
            case 'NF': // Norfolk Island
            case 'HM': // Heard Island and Mcdonald Islands
                $regexp = '/^\d{4}$/i';
                $format = array(
                    '9999',
                );
                break;

            case 'IT': // Italy
            case 'DZ': // Algeria
            case 'HN': // Honduras
            case 'KH': // Cambodia
            case 'LA': // Laos
            case 'LY': // Libya
            case 'MA': // Morocco
            case 'MZ': // Mozambique
            case 'TD': // Chad
            case 'UY': // Uruguay
            case 'IL': // Israel
            case 'MY': // Malaysia
            case 'CR': // Costa Rica
            case 'IQ': // Iraq
            case 'KE': // Kenya
            case 'ZM': // Zambia
            case 'MX': // Mexico
            case 'GT': // Guatemala
            case 'EG': // Egypt
            case 'KW': // Kuwait
            case 'MM': // Myanmar
            case 'NP': // Nepal
            case 'PK': // Pakistan
            case 'TH': // Thailand
            case 'UA': // Ukraine
            case 'SA': // Saudi Arabia
            case 'ID': // Indonesia
            case 'LK': // Sri Lanka
            case 'DO': // Dominican Republic
            case 'JO': // Jordan
                $regexp = '/^\d{5}$/i';
                $format = array(
                    '99999',
                );
                break;

            case 'RU': // Russian Federation
            case 'CN': // China
            case 'MN': // Mongolia
            case 'TM': // Turkmenistan
            case 'KG': // Kyrgyzstan
            case 'KZ': // Kazakhstan
            case 'UZ': // Uzbekistan
            case 'NG': // Nigeria
            case 'SG': // Singapore
            case 'VN': // Viet Nam
                $regexp = '/^\d{6}$/i';
                $format = array(
                    '999999',
                );
                break;

            case 'US': // United States
            case 'PR': // Puerto Rico
            case 'MH': // Marshall Islands
            case 'PW': // Palau
            case 'AS': // American Samoa
            case 'GU': // Guam
            case 'MP': // Northern Mariana Islands
            case 'VI': // Virgin Islands
                $regexp = '/^\d{5}([\s\-]*\d{4})?$/i';
                $format = array(
                    '99999',
                    '99999-9999',
                );
                break;

            case 'MV': // Maldives
                $regexp = '/^\d{2}[\s\-]*\d{2}$/i';
                $format = array(
                    '99-99',
                );
                break;

            case 'IN': // India
            case 'KR': // South Korea
                $regexp = '/^\d{3}[\s\-]*\d{3}$/i';
                $format = array(
                    '999-999',
                );
                break;

            case 'JP': // Japan
                $regexp = '/^\d{3}[\s\-]*\d{4}$/i';
                $format = array(
                    '999-9999',
                );
                break;

            case 'IR': // Iran
                $regexp = '/^\d{5}[\s\-]*\d{5}$/i';
                $format = array(
                    '99999 99999',
                );
                break;

            case 'BR': // Brazil
                $regexp = '/^\d{5}[\s\-]*\d{3}$/i';
                $format = array(
                    '99999-999',
                );
                break;

            case 'NI': // Nicaragua
                $regexp = '/^\d{3}[\s\-]*\d{3}[\s\-]*\d$/i';
                $format = array(
                    '999-999-9',
                );
                break;

            case 'LI': // Liechtenstein
                $regexp = '/^((FL|LI)[\s\-]*)?\d{4}$/i';
                $format = array(
                    '9999',
                    'FL-9999',
                    'LI-9999',
                );
                break;

            case 'CZ': // Czech Republic
            case 'SE': // Sweden
            case 'SK': // Slovakia
                $regexp = '/^(' . $country . '[\s\-]*)?\d{3}[\s\-]*\d{2}$/i';
                $format = array(
                    '999 99',
                    $country . '-999 99',
                );
                break;

            case 'NL': // Netherlands
                $regexp = '/^(' . $country . '[\s\-]*)?\d{4}[\s\-]*[A-Z]{2}$/i';
                $format = array(
                    '9999 AA',
                    $country . '-9999 AA',
                );
                break;

            case 'PL': // Poland
                $regexp = '/^(' . $country . '[\s\-]*)?\d{2}[\s\-]*\d{3}$/i';
                $format = array(
                    '99-999',
                    $country . '-99-999',
                );
                break;

            case 'PT': // Portugal
                $regexp = '/^(' . $country . '[\s\-]*)?\d{4}[\s\-]*\d{3}$/i';
                $format = array(
                    '9999-999',
                    $country . '-9999-999',
                );
                break;

            case 'VA': // Vatican City
                $regexp = '/^(' . $country . '[\s\-]*)?00120$/i';
                $format = array(
                    '00120',
                    $country . '-00120',
                );
                break;

            case 'PE': // Peru
                $regexp = '/^(LIMA|CALL?AO\s*)?\d{2}$/i';
                $format = array(
                    '99',
                    'LIMA 99',
                    'CALLAO 99',
                );
                break;

            case 'SV': // El Salvador
                $regexp = '/^(C\.?P\.?\s*)?\d{4}$/i';
                $format = array(
                    '9999',
                    'CP 9999',
                );
                break;

            case 'SN': // Senegal
            case 'CU': // Cuba
                $regexp = '/^(C\.?P\.?\s*)?\d{5}$/i';
                $format = array(
                    '99999',
                    'CP 99999',
                );
                break;

            case 'BH': // Bahrain
                $regexp = '/^\d{3,4}$/i';
                $format = array(
                    '999',
                    '9999',
                );
                break;

            case 'VE': // Venezuela
                $regexp = '/^\d{4}[\s\-]*[A-Z]$/i';
                $format = array(
                    '9999',
                    '9999 A',
                );
                break;

            case 'TW': // Taiwan
                $regexp = '/^\d{3}(\d{1,2})?$/i';
                $format = array(
                    '999',
                    '99999',
                );
                break;

            case 'CL': // Chile
                $regexp = '/^\d{7}$/i';
                $format = array(
                    '9999999',
                );
                break;

            case 'GB': // United Kingdom
                $regexp = '/^[A-Z]{1,2}(\d[A-Z]?|\d{2})[\s\-]*\d[A-Z]{2}$/i';
                $format = array(
                    'A9 9AA',
                    'A99 9AA',
                    'A9A 9AA',
                    'AA9 9AA',
                    'AA99 9AA',
                    'AA9A 9AA',
                );
                break;

            case 'SZ': // Swaziland
                $regexp = '/^[A-Z]{1,2}\d{3}$/i';
                $format = array(
                    'A999',
                );
                break;

            case 'AR': // Argentina
                $regexp = '/^[A-Z]\d{4}[A-Z]{3}$/i';
                $format = array(
                    'A9999AAA',
                );
                break;

            case 'CA': // Canada
                $regexp = '/^((NL|NS|PE|NB|QC|ON|MB|SK|AB|BC|NU|NT|YT)\s+)?[A-Z]\d[A-Z][\s\-]*\d[A-Z]\d$/i';
                $format = array(
                    'A9A 9A9',
                );
                break;

            case 'BM': // Bermuda
                $regexp = '/^[A-Z]{2}\s*(\d{2}|[A-Z]{2})$/i';
                $format = array(
                    'AA 99',
                    'AA AA',
                );
                break;

            case 'BN': // Brunei Darussalam
                $regexp = '/^[A-Z]{2}\d{4}$/i';
                $format = array(
                    'AA9999',
                );
                break;

            case 'MT': // Malta
                $regexp = '/^[A-Z]{3}\s*\d{4}$/i';
                $format = array(
                    'AAA 9999',
                );
                break;

            case 'AD': // Andorra
                $regexp = '/^' . $country . '\d{3}$/i';
                $format = array(
                    $country . '999',
                );
                break;

            case 'AZ': // Azerbaijan
                $regexp = '/^' . $country . '\d{4}$/i';
                $format = array(
                    $country . '9999',
                );
                break;

            case 'BB': // Barbados
                $regexp = '/^' . $country . '\d{5}$/i';
                $format = array(
                    $country . '99999',
                );
                break;

            case 'EC': // Ecuador
                $regexp = '/^' . $country . '\d{6}$/i';
                $format = array(
                    $country . '999999',
                );
                break;

            case 'GG': // Guernsey
            case 'IM': // Isle of Man
            case 'JE': // Jersey
                $c = ($country == 'GG') ? 'GY' : $country;
                $regexp = '/^' . $c . '\d{1,2}\s+\d[A-Z]{2}$/i';
                $format = array(
                    $c . '9 9AA',
                    $c . '99 9AA',
                );
                break;

            case 'KY': // Cayman Islands
                $regexp = '/^' . $country . '\d[\s\-]*\d{4}$/i';
                $format = array(
                    $country . '9-9999',
                );
                break;

            case 'PN': // Pitcairn
            case 'GS': // South Georgia and The South Sandwich Islands
            case 'SH': // Saint Helena
            case 'TC': // Turks and Caicos Islands
            case 'AI': // Anguilla
            case 'FK': // Falkland Islands
                $map = array(
                    'PN' => 'PCRN 1ZZ',
                    'GS' => 'SIQQ 1ZZ',
                    'SH' => 'STHL 1ZZ',
                    'TC' => 'TKCA 1ZZ',
                    'AI' => 'AI-2640',
                    'FK' => 'FIQQ 1ZZ',
                );
                $regexp = '/^' . preg_quote($map[$country], '/') . '$/i';
                $format = array(
                    $map[$country],
                );
                break;

            case 'AE': // United Arab Emirates
            case 'AF': // Afghanistan
            case 'AG': // Antigua and Barbuda
            case 'AN': // Netherlands Antilles
            case 'AO': // Angola
            case 'AW': // Aruba
            case 'BF': // Burkina Faso
            case 'BI': // Burundi
            case 'BJ': // Benin
            case 'BS': // Bahamas
            case 'BT': // Bhutan
            case 'BW': // Botswana
            case 'BZ': // Belize
            case 'CD': // Congo
            case 'CF': // Central African Republic
            case 'CG': // Republic of Congo
            case 'CI': // Cote D'ivoire
            case 'CK': // Cook Islands
            case 'CM': // Cameroon
            case 'DJ': // Djibouti
            case 'DM': // Dominica
            case 'ER': // Eritrea
            case 'FJ': // Fiji
            case 'GD': // Grenada
            case 'GI': // Gibraltar
            case 'GM': // Gambia
            case 'GN': // Guinea
            case 'GQ': // Equatorial Guinea
            case 'GY': // Guyana
            case 'HK': // Hong Kong
            case 'IE': // Ireland
            case 'JM': // Jamaica
            case 'KI': // Kiribati
            case 'KM': // Comoros
            case 'KN': // Saint Kitts and Nevis
            case 'KP': // North Korea
            case 'LC': // Saint Lucia
            case 'ML': // Mali
            case 'MO': // Macao
            case 'MR': // Mauritania
            case 'MS': // Montserrat
            case 'MU': // Mauritius
            case 'MW': // Malawi
            case 'NA': // Namibia
            case 'NR': // Nauru
            case 'NU': // Niue
            case 'PA': // Panama
            case 'QA': // Qatar
            case 'RW': // Rwanda
            case 'SB': // Solomon Islands
            case 'SC': // Seychelles
            case 'SL': // Sierra Leone
            case 'SO': // Somalia
            case 'SR': // Suriname
            case 'ST': // Sao Tome and Principe
            case 'SY': // Syrian Arab Republic
            case 'TF': // French Southern Territories
            case 'TK': // Tokelau
            case 'TO': // Tonga
            case 'TT': // Trinidad and Tobago
            case 'TV': // Tuvalu
            case 'TZ': // Tanzania
            case 'UG': // Uganda
            case 'VC': // Saint Vincent and The Grenadines
            case 'VU': // Vanuatu
            case 'YE': // Yemen
            case 'ZW': // Zimbabwe
                $regexp = '/^$/i';
                $format = array(
                    '',
                );
                break;
        }

        $this->validFormats = join(', ', $format);

        if (!strlen($regexp)) {
            if ($this->validNoCountry) {
                return (true);
            } else {
                $this->_error(self::NO_COUNTRY);
                return (false);
            }
        }

        if (!preg_match($regexp, $value)) {
            $this->_error(self::INVALID);
            return (false);
        }

        return (true);
    }

    /**
     * Set validator configuration option
     *
     * @param string $country Name of form field with country code
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Set validator configuration option
     *
     * @param boolean $validNoCountry true to treat any postal code as valid if no country is selected
     */
    public function setValidNoCountry($validNoCountry)
    {
        $this->validNoCountry = (boolean)$validNoCountry;
    }

}
