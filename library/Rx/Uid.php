<?php

/**
 * UIDs generation and management class
 */
class Rx_Uid
{
    /**
     * Normalize given string to let it to be used as UID
     *
     * @param  string $string string to normalize
     * @return string
     */
    static public function normalize($string)
    {
        if (preg_match('//u', $string)) {
            if (function_exists('mb_strtolower')) {
                $string = mb_strtolower($string, 'utf-8');
            } else {
                $string = strtolower($string);
            }
            $string = preg_replace('/^\s*(.*?)\s*$/su', '\1', $string);
            $string = preg_replace('/\s+/su', ' ', $string);
        } else {
            $string = trim(strtolower($string));
            $string = preg_replace('/\s+/s', ' ', $string);
        }
        return ($string);
    }

    /**
     * Generate UID for given string with given parameters
     *
     * @param  string $string      string to generate UID for
     * @param  boolean $long       true to generate long (md5()-based) UID, false to generate short (crc32()-based) UID
     * @param  boolean $hex        true to return crc32()-based UID as hex string, false to return it as a number
     * @param  boolean $normalized true if string is already normalized, false to normalize string before conversion
     * @return mixed
     */
    static protected function _getUid($string, $long = false, $hex = true, $normalized = true)
    {
        if (!$normalized) {
            $string = Rx_Uid::normalize($string);
        }
        $uid = (($long) && (!$hex)) ? md5($string, true) : strtolower(md5($string));
        if (!$long) {
            // We calculate short UID from md5()-based UID since in this case
            // collisions risk is about 20x times less (experimentally proven)
            $uid = crc32($uid);
            if ($hex) {
                $uid = Rx_Uid::toString($uid);
            }
        }
        return ($uid);
    }

    /**
     * Generate short (crc32()-based) UID for given string
     *
     * @param  string $string      string to generate UID for
     * @param  boolean $hex        true to return UID as hex string, false to return it as a number
     * @param  boolean $normalized true if string is already normalized, false to normalize string before conversion
     * @return mixed
     */
    static public function getUid($string, $hex = true, $normalized = true)
    {
        return (Rx_Uid::_getUid($string, false, $hex, $normalized));
    }

    /**
     * Generate long (md5()-based) UID for given string
     *
     * @param  string $string      string to generate UID for
     * @param  boolean $normalized true if string is already normalized, false to normalize string before conversion
     * @return string
     */
    static public function getLongUid($string, $normalized = true)
    {
        return (Rx_Uid::_getUid($string, true, true, $normalized));
    }

    /**
     * Check if given string contains UID
     *
     * @param string $string    String to check for UID
     * @param boolean $getType  true to get UID type, false to just check for UID
     * @return boolean|string   If $getType is false then return true/false depending if given string is recognized as UID or not
     *                          If $getType is true - return strings 'short','long' or false
     */
    static public function isUid($string, $getType = false)
    {
        $result = false;
        if (preg_match('/^[k-z][0-9a-f]{7}$/i', $string)) {
            $result = 'short';
        } elseif (preg_match('/^[0-9a-f]{32}$/i', $string)) {
            $result = 'long';
        }
        if (!$getType) {
            $result = (boolean)$result;
        }
        return ($result);
    }

    /**
     * Convert given UID into string
     *
     * @param  mixed $uid     UID to convert into string
     * @param  string $prefix Prefix to generate UID with
     * @return string
     */
    static public function toString($uid, $prefix = null)
    {
        if (is_numeric($uid)) {
            $uid = strtolower(str_pad(dechex($uid), 8, 0, STR_PAD_LEFT));
            $map = array(
                '0' => 'k',
                '1' => 'l',
                '2' => 'm',
                '3' => 'n',
                '4' => 'o',
                '5' => 'p',
                '6' => 'q',
                '7' => 'r',
                '8' => 's',
                '9' => 't',
                'a' => 'u',
                'b' => 'v',
                'c' => 'w',
                'd' => 'x',
                'e' => 'y',
                'f' => 'z'
            );
            $uid = $map[substr($uid, 0, 1)] . substr($uid, 1);
        }
        if ($prefix !== null) {
            $uid = $prefix . $uid;
        }
        return ($uid);
    }

    /**
     * Convert given string UID into number
     *
     * @param  string $uid    UID to convert into number (only short, crc32()-based)
     * @param  boolean $check true to check if given UID have correct format for conversion, false to try to convert in any case
     * @return int
     */
    static public function toNumber($uid, $check = true)
    {
        if (is_numeric($uid)) {
            return ($uid);
        }
        $valid = ($check) ? (Rx_Uid::isUid($uid, true) == 'short') : true;
        if ($valid) {
            $map = array(
                'k' => '0',
                'l' => '1',
                'm' => '2',
                'n' => '3',
                'o' => '4',
                'p' => '5',
                'q' => '6',
                'r' => '7',
                's' => '8',
                't' => '9',
                'u' => 'a',
                'v' => 'b',
                'w' => 'c',
                'x' => 'd',
                'y' => 'e',
                'z' => 'f'
            );
            $uid = $map[substr($uid, 0, 1)] . substr($uid, 1);
            return ((int)hexdec($uid));
        } else {
            return (null);
        }
    }

    /**
     * Convert "pure crc32()" UID into UID format supported by this class
     *
     * @param  string $uid UID to convert into number (only short, crc32()-based)
     * @return int
     */
    static public function convert($uid)
    {
        if (Rx_Uid::isUid($uid, true) == 'short') {
            return ($uid);
        } elseif (!preg_match('/^[0-9a-f]{8}$/i', $uid)) {
            return (null);
        }
        $map = array(
            '0' => 'k',
            '1' => 'l',
            '2' => 'm',
            '3' => 'n',
            '4' => 'o',
            '5' => 'p',
            '6' => 'q',
            '7' => 'r',
            '8' => 's',
            '9' => 't',
            'a' => 'u',
            'b' => 'v',
            'c' => 'w',
            'd' => 'x',
            'e' => 'y',
            'f' => 'z'
        );
        $uid = $map[substr($uid, 0, 1)] . substr($uid, 1);
        return ($uid);
    }

    /**
     * Calculate sharding ID of requested level for given UID
     *
     * @param  mixed $uid   UID to calculate sharding ID for
     * @param  int $level   Sharding level to calculate (0 for "complete" 1st+2nd levels)
     * @param  boolean $hex true to return ID as hex string, false to return it as a number
     * @return mixed
     */
    static protected function _getShardingId($uid, $level = 1, $hex = true)
    {
        if (strlen($uid) == 32) {
            // md5()-based UID, sharding ids are counted from start
            $id = ($level == 0) ? substr($uid, 0, 2) : substr($uid, $level - 1, 1);
            if (!$hex) {
                $id = hexdec($id);
            }
        } else {
            $uid = Rx_Uid::toNumber($uid, true);
            // crc32()-based numeric UID, sharding ids are taken from end of number
            $id = ($level == 0) ? $uid & 0xFF : ($uid >> (($level - 1) * 4)) & 0x0F;
            if ($hex) {
                $id = strtolower(sprintf('%0' . (($level == 0) ? 2 : 1) . 's', dechex($id)));
            }
        }
        return ($id);
    }

    /**
     * Calculate 1st level sharding ID for given UID
     *
     * @param  mixed $uid   UID to calculate sharding ID for
     * @param  boolean $hex true to return ID as hex string, false to return it as a number
     * @return mixed
     */
    static public function getShardingId($uid, $hex = true)
    {
        return (Rx_Uid::_getShardingId($uid, 1, $hex));
    }

    /**
     * Calculate 2nd level sharding ID for given UID
     *
     * @param  mixed $uid   UID to calculate sharding ID for
     * @param  boolean $hex true to return ID as hex string, false to return it as a number
     * @return mixed
     */
    static public function getShardingSubId($uid, $hex = true)
    {
        return (Rx_Uid::_getShardingId($uid, 2, $hex));
    }

    /**
     * Calculate "full" (combined 1st+2nd) sharding ID for given UID
     *
     * @param  mixed $uid   UID to calculate sharding ID for
     * @param  boolean $hex true to return ID as hex string, false to return it as a number
     * @return mixed
     */
    static public function getShardingFullId($uid, $hex = true)
    {
        return (Rx_Uid::_getShardingId($uid, 0, $hex));
    }

    /**
     * Generate range of value for sharding iteration with given prefix
     *
     * @param  string $prefix Prefix to generate range with
     * @param  boolean $full  true to return ID "full" (2 digits) sharding range, false to generate usual 1 digit range
     * @param  boolean $hex   true to return ID as hex string, false to return it as a number
     * @return array
     */
    static public function getShardingRange($prefix = null, $full = false, $hex = true)
    {
        $range = array();
        $m = ($full) ? 256 : 16;
        $s = '%0' . (($full) ? 2 : 1) . 's';
        for ($i = 0; $i < $m; $i++) {
            $v = ($hex) ? strtolower(sprintf($s, dechex($i))) : $i;
            $range[$v] = $prefix . $v;
        }
        return ($range);
    }

    /**
     * Generate random UID with given parameters
     *
     * @param  boolean $long true to generate long (md5()-based) UID, false to generate short (crc32()-based) UID
     * @param  boolean $hex  true to return crc32()-based UID as hex string, false to return it as a number
     * @return mixed
     */
    static public function getRandomUid($long = false, $hex = true)
    {
        static $init = false;

        if (!$init) {
            mt_srand((double)microtime() * 100000);
            $init = true;
        }
        return (Rx_Uid::_getUid(
            microtime() . mt_rand(100000, 999999) . mt_rand(100000, 999999) . mt_rand(100000, 999999) . mt_rand(
                100000,
                999999
            ),
            $long,
            $hex,
            true
        ));
    }

    /**
     * Get secure salted hash for given string
     *
     * @param string $string String to get secure UID for
     * @param string $salt   Salt to use for generating secure UID
     * @param int $rounds    OPTIONAL Number of hashing rounds to use
     * @return string
     */
    public static function getSecureUid($string, $salt, $rounds = 50)
    {
        $string = trim($string);
        $before = true;
        while ($rounds--) {
            $string = ($before) ? $salt . $string : $string . $salt;
            $string = md5($string);
            $before = !$before;
        }
        return ($string);
    }

}

;
