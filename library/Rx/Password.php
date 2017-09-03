<?php

class Rx_Password
{
    /**
     * @var Rx_Password $_instance
     */
    protected static $_instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Password
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Generate password by given initialization vector
     *
     * @param string $iv Initialization vector for password generation
     * @return string|false     Password or false if initialization vector is invalid
     */
    public static function get($iv)
    {
        static $cache = array();

        if (isset($cache[$iv])) {
            return ($cache[$iv]);
        }
        $_iv = $iv;
        $iv = @base64_decode($iv);
        if ($iv === false) {
            trigger_error('Failed to decode initialization vector', E_USER_WARNING);
            return (false);
        }
        $data = unpack('Vdata1/Clength/Vdata2/Cstrength/Cshuffle/Vsalt', $iv);
        $length = $data['length'];
        $strength = $data['strength'];
        $shuffle = $data['shuffle'];
        if ($shuffle < 3) {
            $shuffle = 3;
        }
        $source = Rx_Uid::toString($data['data1']) . Rx_Uid::toString($data['data2']);
        $salt = Rx_Uid::toString($data['salt']);
        $chars = '';
        switch ($strength) {
            case 0:
                $chars = range('a', 'z');
                break;
            case 1:
                $chars = array_merge(range('a', 'z'), range('A', 'Z'));
                break;
            case 2:
                $chars = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));
                break;
            case 3:
                $chars = range(chr(33), chr(126));
                break;
            case 4:
                $chars = range(chr(0), chr(255));
                break;
            default:
                trigger_error('Invalid password strength type: ' . $strength, E_USER_ERROR);
                return (false);
                break;
        }
        // Shuffle chars
        $vector = '';
        while (sizeof($chars)) {
            $chars = array_merge(array_slice($chars, $shuffle), array_slice($chars, 0, $shuffle));
            $vector .= array_shift($chars);
        }
        $sz = strlen($vector);
        $start = true;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $source = md5(($start) ? $salt . $source : $source . $salt, true);
            $start = !$start;
            $sum = $i;
            for ($j = 0; $j < 16; $j++) {
                $sum += ord(substr($source, $j, 1));
            }
            $password .= substr($vector, $sum % $sz, 1);
        }
        $cache[$_iv] = $password;
        return ($password);
    }

    /**
     * Generate initialization vector for pasword generation by given parameters
     *
     * @param int $length       Required password length (min. 4, up to 255 chars, default 10)
     * @param int $strength     Password strength level. Possible values:
     *                          0 - only lower case letters
     *                          1 - upper and lower case letters
     *                          2 - upper, lower case letters and digits (default)
     *                          3 - all chars in range [33..126]
     *                          4 - binary string [0..255]
     * @return string
     */
    public static function generateIv($length = 10, $strength = 2)
    {
        $length = min(max($length, 4), 255);
        $strength = min(max($strength, 0), 4);
        $shuffle = mt_rand(3, 15);
        $iv = pack(
            'VCVCCV',
            Rx_Uid::getRandomUid(false, false),
            $length,
            Rx_Uid::getRandomUid(false, false),
            $strength,
            $shuffle,
            Rx_Uid::getRandomUid(false, false)
        );
        $iv = base64_encode($iv);
        return ($iv);
    }

}
