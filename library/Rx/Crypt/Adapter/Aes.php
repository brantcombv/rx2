<?php

/**
 * AES (FIPS-197) cryptographic algorithm implementation
 *
 * @link   http://csrc.nist.gov/groups/ST/toolkit/block_ciphers.html#Approved%20Algorithms
 * @link   http://en.wikipedia.org/wiki/Advanced_Encryption_Standard
 *
 * Uses nCFB mode of operation
 * @link   http://csrc.nist.gov/groups/ST/toolkit/BCM/current_modes.html
 * @link   http://en.wikipedia.org/wiki/Output_feedback#Cipher_feedback_.28CFB.29
 *
 * Uses PKCS7 padding method
 * @link   http://tools.ietf.org/html/rfc5652#section-6.3
 * @link   http://en.wikipedia.org/wiki/Padding_%28cryptography%29#Byte_padding
 *
 * @author Portions are based on AES implementation from Chris Veness
 * @link   http://www.movable-type.co.uk/scripts/aes-php.html
 *
 * WARNING: Only AES-128 is compatible with MCrypt implementation!
 */
class Rx_Crypt_Adapter_Aes extends Rx_Crypt_Adapter_Abstract
{
    // Constants for available AES encryption modes
    const AES128 = 128;
    const AES192 = 192;
    const AES256 = 256;

    /**
     * true if encryption adapter need to get encryption key
     *
     * @var boolean $_needKey
     */
    protected $_needKey = true;
    /**
     * true if encryption adapter need to get encryption initialization vector
     *
     * @var boolean $_needIv
     */
    protected $_needIv = true;
    /**
     * true if encryption adapter need to get encryption strength
     *
     * @var boolean $_needStrength
     */
    protected $_needStrength = true;
    /**
     * Number of rounds in AES algorithm (Nr)
     *
     * @var int $nRounds
     */
    private $nRounds;
    /**
     * Number of columns (32-bit words) comprising the State (Nb)
     *
     * @var int $szBlock
     */
    private $szBlock;
    /**
     * Number of 32-bit words comprising the Cipher Key (Nk)
     *
     * @var int $szKey
     */
    private $szKey;
    /**
     * Initialization vector size
     *
     * @var int $szIv
     */
    private $szIv;
    /**
     * Encryption strength
     *
     * @var int $strength
     */
    private $strength;
    /**
     * S-Box: Pre-computed multiplicative inverse in GF(2^8)
     *
     * @var array $sBox
     */
    private $sBox;
    /**
     * Round Constant used for the key expansion [1st col is 2^(r-1) in GF(2^8)]
     *
     * @var array $rCon
     */
    private $rCon;
    /**
     * State array
     *
     * @var array $state
     */
    private $state;
    /**
     * List of available encryption modes.
     * Value is true to use native MCrypt function, false to use PHP implementation
     *
     * @var array $modes
     */
    private $modes = array();
    /**
     * Mapping table between supported AES strengths and MCrypt algorithms
     *
     * @var array $mcryptMap
     */
    private $mcryptMap = array();

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config Configuration options for class (optional)
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->sBox = array(
            0x63,
            0x7C,
            0x77,
            0x7B,
            0xF2,
            0x6B,
            0x6F,
            0xC5,
            0x30,
            0x01,
            0x67,
            0x2B,
            0xFE,
            0xD7,
            0xAB,
            0x76,
            0xCA,
            0x82,
            0xC9,
            0x7D,
            0xFA,
            0x59,
            0x47,
            0xF0,
            0xAD,
            0xD4,
            0xA2,
            0xAF,
            0x9C,
            0xA4,
            0x72,
            0xC0,
            0xB7,
            0xFD,
            0x93,
            0x26,
            0x36,
            0x3F,
            0xF7,
            0xCC,
            0x34,
            0xA5,
            0xE5,
            0xF1,
            0x71,
            0xD8,
            0x31,
            0x15,
            0x04,
            0xC7,
            0x23,
            0xC3,
            0x18,
            0x96,
            0x05,
            0x9A,
            0x07,
            0x12,
            0x80,
            0xE2,
            0xEB,
            0x27,
            0xB2,
            0x75,
            0x09,
            0x83,
            0x2C,
            0x1A,
            0x1B,
            0x6E,
            0x5A,
            0xA0,
            0x52,
            0x3B,
            0xD6,
            0xB3,
            0x29,
            0xE3,
            0x2F,
            0x84,
            0x53,
            0xD1,
            0x00,
            0xED,
            0x20,
            0xFC,
            0xB1,
            0x5B,
            0x6A,
            0xCB,
            0xBE,
            0x39,
            0x4A,
            0x4C,
            0x58,
            0xCF,
            0xD0,
            0xEF,
            0xAA,
            0xFB,
            0x43,
            0x4D,
            0x33,
            0x85,
            0x45,
            0xF9,
            0x02,
            0x7F,
            0x50,
            0x3C,
            0x9F,
            0xA8,
            0x51,
            0xA3,
            0x40,
            0x8F,
            0x92,
            0x9D,
            0x38,
            0xF5,
            0xBC,
            0xB6,
            0xDA,
            0x21,
            0x10,
            0xFF,
            0xF3,
            0xD2,
            0xCD,
            0x0C,
            0x13,
            0xEC,
            0x5F,
            0x97,
            0x44,
            0x17,
            0xC4,
            0xA7,
            0x7E,
            0x3D,
            0x64,
            0x5D,
            0x19,
            0x73,
            0x60,
            0x81,
            0x4F,
            0xDC,
            0x22,
            0x2A,
            0x90,
            0x88,
            0x46,
            0xEE,
            0xB8,
            0x14,
            0xDE,
            0x5E,
            0x0B,
            0xDB,
            0xE0,
            0x32,
            0x3A,
            0x0A,
            0x49,
            0x06,
            0x24,
            0x5C,
            0xC2,
            0xD3,
            0xAC,
            0x62,
            0x91,
            0x95,
            0xE4,
            0x79,
            0xE7,
            0xC8,
            0x37,
            0x6D,
            0x8D,
            0xD5,
            0x4E,
            0xA9,
            0x6C,
            0x56,
            0xF4,
            0xEA,
            0x65,
            0x7A,
            0xAE,
            0x08,
            0xBA,
            0x78,
            0x25,
            0x2E,
            0x1C,
            0xA6,
            0xB4,
            0xC6,
            0xE8,
            0xDD,
            0x74,
            0x1F,
            0x4B,
            0xBD,
            0x8B,
            0x8A,
            0x70,
            0x3E,
            0xB5,
            0x66,
            0x48,
            0x03,
            0xF6,
            0x0E,
            0x61,
            0x35,
            0x57,
            0xB9,
            0x86,
            0xC1,
            0x1D,
            0x9E,
            0xE1,
            0xF8,
            0x98,
            0x11,
            0x69,
            0xD9,
            0x8E,
            0x94,
            0x9B,
            0x1E,
            0x87,
            0xE9,
            0xCE,
            0x55,
            0x28,
            0xDF,
            0x8C,
            0xA1,
            0x89,
            0x0D,
            0xBF,
            0xE6,
            0x42,
            0x68,
            0x41,
            0x99,
            0x2D,
            0x0F,
            0xB0,
            0x54,
            0xBB,
            0x16,
        );
        $rCon = array(0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x80, 0x1B, 0x36);
        $this->rCon = array();
        foreach ($rCon as $v) {
            $this->rCon[] = array($v, 0x00, 0x00, 0x00);
        }
        // Determine if we can use native functions for AES
        $r = new ReflectionObject($this);
        $modes = $r->getConstants();
        $this->modes = array();
        foreach ($modes as $k => $v) {
            if (substr($k, 0, 3) != 'AES') {
                continue;
            }
            $this->modes[$v] = false;
        }
        if (extension_loaded('mcrypt')) {
            // Our implementation corresponds to NCFB mode of MCrypt
            $this->mcryptMap = array(
                self::AES128 => MCRYPT_RIJNDAEL_128,
                self::AES192 => MCRYPT_RIJNDAEL_192,
                self::AES256 => MCRYPT_RIJNDAEL_256,
            );
            $modes = mcrypt_list_modes();
            if (in_array('ncfb', $modes)) {
                $algorithms = mcrypt_list_algorithms();
                foreach ($this->modes as $mode => $status) {
                    if (in_array('rijndael-' . $mode, $algorithms)) {
                        $this->modes[$mode] = true;
                    }
                }
            }
        }
    }

    /**
     * Actual implementation of content encryption
     *
     * @param string $content Content for encryption
     * @param array $params   Parameters for encryption
     * @return string               Encrypted content
     * @throws Rx_Crypt_Exception
     */
    protected function _encrypt($content, $params)
    {
        $output = '';
        $this->init($params['strength']);
        if (strlen($params['iv']) != $this->szIv) {
            throw new Rx_Crypt_Exception('Initialization vector size doesn\'t match selected encryption strength');
        }
        $key = $this->generateKey($params['key']);
        // Add padding to source text so it will fit into AES block size (16 bytes)
        $sz = strlen($content);
        $padding = (ceil($sz / 16) * 16) - $sz;
        if ($padding > 0) {
            $content .= str_repeat(chr($padding), $padding);
        }
        // Check if we can use native AES implementation from MCrypt
        if ((array_key_exists($params['strength'], $this->modes)) &&
            ($this->modes[$params['strength']])
        ) {
            // Attempt to encrypt given content with MCrypt AES implementation
            $mcrypt = mcrypt_module_open($this->mcryptMap[$params['strength']], '', 'ncfb', '');
            if (is_resource($mcrypt)) {
                mcrypt_generic_init($mcrypt, $key, $params['iv']);
                $output = mcrypt_generic($mcrypt, $content);
                mcrypt_generic_deinit($mcrypt);
                mcrypt_module_close($mcrypt);
                return ($output);
            }
        }
        // Native implementation is either not available or failed, so use PHP implementation
        $key = $this->keyExpansion($this->strToBytes($key));
        $parts = str_split($content, 16); // 16 is fixed block size for AES
        $chunk = $this->strToBytes($params['iv']);
        foreach ($parts as $part) {
            $part = $this->strToBytes($part);
            $chunk = $this->cipher($chunk, $key);
            $chunk = $this->xorBlock($chunk, $part);
            $output .= $this->bytesToStr($chunk);
        }
        return ($output);
    }

    /**
     * Actual implementation of content decryption
     *
     * @param string $content Content to decrypt
     * @param array $params   Parameters for encryption
     * @return string               Decrypted content
     * @throws Rx_Crypt_Exception
     */
    protected function _decrypt($content, $params)
    {
        $output = null;
        $this->init($params['strength']);
        if (strlen($params['iv']) != $this->szIv) {
            throw new Rx_Crypt_Exception('Initialization vector size doesn\'t match selected encryption strength');
        }
        $key = $this->generateKey($params['key']);
        // Check if we can use native AES implementation from MCrypt
        if ((array_key_exists($params['strength'], $this->modes)) &&
            ($this->modes[$params['strength']])
        ) {
            // Attempt to decrypt given content with MCrypt AES implementation
            $mcrypt = mcrypt_module_open($this->mcryptMap[$params['strength']], '', 'ncfb', '');
            if (is_resource($mcrypt)) {
                mcrypt_generic_init($mcrypt, $key, $params['iv']);
                $output = mdecrypt_generic($mcrypt, $content);
                mcrypt_generic_deinit($mcrypt);
                mcrypt_module_close($mcrypt);
            }
        }
        if ($output === null) {
            // Native implementation is either not available or failed, so use PHP implementation
            $key = $this->keyExpansion($this->strToBytes($key));
            $parts = str_split($content, 16); // 16 is fixed block size for AES
            $chunk = $this->strToBytes($params['iv']);
            foreach ($parts as $part) {
                $part = $this->strToBytes($part);
                $chunk = $this->cipher($chunk, $key);
                $chunk = $this->xorBlock($chunk, $part);
                $output .= $this->bytesToStr($chunk);
                $chunk = $part;
            }
        }
        // Check if we have padding (using PKCS7 padding method) applied to decrypted content
        $b = substr($output, -1);
        $c = ord($b);
        if (($c > 0) && ($c < 16)) {
            $padding = true;
            $p = substr($output, 0 - $c);
            for ($i = 0; $i < $c; $i++) {
                if (substr($p, $i, 1) != $b) {
                    $padding = false;
                    break;
                }
            }
            if ($padding) {
                $output = substr($output, 0, 0 - $c);
            }
        }
        return ($output);
    }

    /**
     * Get default encryption strength for current adapter
     *
     * @return int|null
     */
    public static function getDefaultStrength()
    {
        return (self::AES128);
    }

    /**
     * Get size of initialization vector for given encryption strength
     * Actually this method must be overridden in every adapter that uses initialization vector,
     * but it is not abstract because not all adapters may use initialization vectors
     *
     * @param string $strength OPTIONAL Encryption strength constant
     * @return int|null
     */
    public static function getIvSize($strength = null)
    {
        if ($strength === null) {
            $strength = self::getDefaultStrength();
        }
        $size = null;
        switch ($strength) {
            case self::AES128:
                $size = 16;
                break;
            case self::AES192:
                $size = 24;
                break;
            case self::AES256:
                $size = 32;
                break;
        }
        return ($size);
    }

    /**
     * AES cipher function: encrypt input with Rijndael algorithm
     *
     * @param array $input  Message as byte-array (16 bytes)
     * @param array $key    Key schedule as 2D byte-array (Nr+1 x Nb bytes) -
     *                      generated from the cipher key by keyExpansion()
     * @return array        Ciphertext as byte-array (16 bytes)
     */
    private function cipher($input, $key)
    {
        // Initialize state array
        $this->state = array();
        for ($i = 0; $i < 4 * $this->szBlock; $i++) {
            $this->state[$i & 0x03][$i >> 2] = $input[$i];
        }

        $this->addRoundKey($key);

        // Run through required amount of rounds
        for ($round = 1; $round < $this->nRounds; $round++) {
            $this->subBytes();
            $this->shiftRows();
            $this->mixColumns();
            $this->addRoundKey($key, $round);
        }

        $this->subBytes();
        $this->shiftRows();
        $this->addRoundKey($key, $this->nRounds);

        // Convert state back to array
        $output = array();
        for ($i = 0; $i < 4 * $this->szBlock; $i++) {
            $output[$i] = $this->state[$i & 0x03][$i >> 2];
        }
        return ($output);
    }

    /**
     * Add round key to state via xor operation
     *
     * @param string $key   Round key
     * @param string $round Round number
     * @return void
     */
    private function addRoundKey($key, $round = 0)
    {
        for ($r = 0; $r < 4; $r++) {
            for ($c = 0; $c < $this->szBlock; $c++) {
                $this->state[$r][$c] ^= $key[$round * 4 + $c][$r];
            }
        }
    }

    /**
     * Transformation in the Cipher that processes the State using a nonlinear
     * byte substitution table (S-box) that operates on each of the
     * State bytes independently
     *
     * @return void
     */
    private function subBytes()
    {
        for ($r = 0; $r < 4; $r++) {
            for ($c = 0; $c < $this->szBlock; $c++) {
                $this->state[$r][$c] = $this->sBox[$this->state[$r][$c]];
            }
        }
    }

    /**
     * Transformation in the Cipher that processes the State by cyclically
     * shifting the last three rows of the State by different offsets
     *
     * @return void
     */
    private function shiftRows()
    {
        $t = array();
        for ($r = 1; $r < 4; $r++) {
            for ($c = 0; $c < 4; $c++) {
                $t[$c] = $this->state[$r][($c + $r) % $this->szBlock];
            }
            for ($c = 0; $c < 4; $c++) {
                $this->state[$r][$c] = $t[$c];
            }
        }
    }

    /**
     * Transformation in the Cipher that takes all of the columns of the
     * State and mixes their data (independently of one another) to
     * produce new columns.
     *
     * @return void
     */
    private function mixColumns()
    {
        for ($c = 0; $c < 4; $c++) {
            $a = array();
            $b = array();
            for ($i = 0; $i < 4; $i++) {
                $a[$i] = $this->state[$i][$c];
                $b[$i] = ($this->state[$i][$c] & 0x80) ? $this->state[$i][$c] << 1 ^ 0x011B : $this->state[$i][$c] << 1;
            }
            $this->state[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3];
            $this->state[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3];
            $this->state[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3];
            $this->state[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3];
        }
    }

    /**
     * Key expansion for Rijndael Cipher(): performs key expansion on cipher key
     * to generate a key schedule
     *
     * @param array $key Cipher key byte-array (16 bytes)
     * @return array        key schedule as 2D byte-array (Nr+1 x Nb bytes)
     */
    private function keyExpansion($key)
    {
        $w = array();
        $temp = array();

        for ($i = 0; $i < $this->szKey; $i++) {
            $w[$i] = array($key[4 * $i], $key[4 * $i + 1], $key[4 * $i + 2], $key[4 * $i + 3]);
        }

        $sz = ($this->szBlock * ($this->nRounds + 1));
        for ($i = $this->szKey; $i < $sz; $i++) {
            $w[$i] = array();
            for ($t = 0; $t < 4; $t++) {
                $temp[$t] = $w[$i - 1][$t];
            }
            if ($i % $this->szKey == 0) {
                $temp = $this->subWord($this->rotWord($temp));
                for ($t = 0; $t < 4; $t++) {
                    $temp[$t] ^= $this->rCon[$i / $this->szKey][$t];
                }
            } elseif (($this->szKey > 6) && ($i % $this->szKey == 4)) {
                $temp = $this->subWord($temp);
            }
            for ($t = 0; $t < 4; $t++) {
                $w[$i][$t] = $w[$i - $this->szKey][$t] ^ $temp[$t];
            }
        }
        return ($w);
    }

    /**
     * Function used in the Key Expansion routine that takes a four-byte
     * input word and applies an S-box to each of the four bytes to
     * produce an output word
     *
     * @param array $w Word to apply S-box to
     * @return array
     */
    private function subWord($w)
    {
        for ($i = 0; $i < 4; $i++) {
            $w[$i] = $this->sBox[$w[$i]];
        }
        return ($w);
    }

    /**
     * Function used in the Key Expansion routine that takes a four-byte
     * word and performs a cyclic permutation.
     *
     * @param array $w Word to rotate
     * @return array
     */
    private function rotWord($w)
    {
        $tmp = $w[0];
        for ($i = 0; $i < 3; $i++) {
            $w[$i] = $w[$i + 1];
        }
        $w[3] = $tmp;
        return ($w);
    }

    /**
     * Generate encryption key from given password string
     *
     * @param string $password Password string to generate key from
     * @return array                Encryption key
     */
    private function generateKey($password)
    {
        $key = null;
        switch ($this->strength) {
            case self::AES128:
                $key = md5($password, true);
                break;
            case self::AES192:
                $key = sha1($password, true);
                $c = crc32($password);
                $key .= chr(($c >> 24) & 0xFF);
                $key .= chr(($c >> 16) & 0xFF);
                $key .= chr(($c >> 8) & 0xFF);
                $key .= chr($c & 0xFF);
                break;
            case self::AES256:
                $key = md5($password, true) . md5($password . $password, true);
                break;
        }
        return ($key);
    }

    /**
     * Convert given string into byte array
     *
     * @param string $str String to convert
     * @return array
     */
    private function strToBytes($str)
    {
        $bytes = array();
        $sz = strlen($str);
        for ($i = 0; $i < $sz; $i++) {
            $bytes[] = ord(substr($str, $i, 1)) & 0xFF;
        }
        return ($bytes);
    }

    /**
     * Convert given byte array into string
     *
     * @param array $bytes Byte array to convert
     * @return string
     */
    private function bytesToStr($bytes)
    {
        $str = '';
        $sz = sizeof($bytes);
        for ($i = 0; $i < $sz; $i++) {
            $str .= chr($bytes[$i] & 0xFF);
        }
        return ($str);
    }

    /**
     * Compute $a ^ $b for given byte arrays
     *
     * @param array $a First byte array
     * @param array $b Second byte array
     * @return array
     */
    private function xorBlock($a, $b)
    {
        foreach ($a as $ak => $av) {
            $bv = (isset($b[$ak])) ? $b[$ak] : 0;
            $a[$ak] ^= $bv;
        }
        return ($a);
    }

    /**
     * AES parameters initialization
     *
     * @param string $strength Key strength (any of AES128, AES192 or AES256 class constants, default AES128)
     * @return void
     */
    private function init($strength = self::AES128)
    {
        if (!array_key_exists($strength, $this->modes)) {
            $strength = self::AES128;
        }
        $this->szBlock = 4; // Block size (number of columns in state), always 4 for AES
        $this->strength = $strength;
        switch ($strength) {
            case self::AES128:
                $this->szKey = 4;
                $this->szIv = 16;
                $this->nRounds = 10;
                break;
            case self::AES192:
                $this->szKey = 6;
                $this->szIv = 24;
                $this->nRounds = 12;
                break;
            case self::AES256:
                $this->szKey = 8;
                $this->szIv = 32;
                $this->nRounds = 14;
                break;
        }
    }

}
