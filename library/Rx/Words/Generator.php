<?php

class Rx_Words_Generator
{
    /**
     * Implementation of Singleton pattern
     *
     * @var Rx_Words_Generator $_instance
     */
    protected static $_instance = null;
    /**
     * Words generation dictionary
     *
     * @var array $_dictionary
     */
    protected $_dictionary = null;
    /**
     * Max.size of single word part in dictionary
     *
     * @var int $_maxPartSize
     */
    protected $_maxPartSize = 5;
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
     * @return Rx_Words_Generator
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
     *
     * @throws Rx_Exception
     */
    public static function bootstrap()
    {
        $instance = self::getInstance();
        if ($instance->_initialized) {
            return;
        }
        $dictionary = null;
        // Read configuration and configure class
        $path = Rx_Config::getPath('rx.words.generator.dictionary', false);
        if (!file_exists($path)) {
            throw new Rx_Exception('Unable to find words generator dictionary: ' . $path);
        }

        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
        } elseif (Zend_Registry::isRegistered('Zend_Cache')) {
            $cache = Zend_Registry::get('Zend_Cache');
        } else {
            $cache = null;
        }
        /* @var $cache Zend_Cache_Core */
        if ($cache) {
            $cacheKey = join('_', array(
                get_class($instance),
                Rx_Uid::getUid($path . filesize($path) . filemtime($path)),
            ));
            if ($cache->test($cacheKey)) {
                $dictionary = $cache->load($cacheKey);
                if (!is_array($dictionary)) {
                    $cache->remove($cacheKey);
                    $dictionary = null;
                }
            }
        }
        if (!is_array($dictionary)) {
            $data = @file_get_contents($path);
            if (!strlen($data)) {
                throw new Rx_Exception('Failed to load words generator dictionary: ' . $path);
            }
            $dictionary = $instance->_parseDictionary($data);
            if ($cache) {
                $cache->save($dictionary, $cacheKey);
            }
        }
        $instance->_dictionary = $dictionary;
        $instance->_maxPartSize = Rx_Config::get('rx.words.generator.maxPartSize', $instance->_maxPartSize);

        $instance->_initialized = true;
    }

    /**
     * Generate word with given size limit
     *
     * @param int $size Size limit for generated word
     * @return string|null
     */
    public static function generate($size = 10)
    {
        $instance = self::getInstance();
        $word = null;
        $c = 100;
        while ($c--) {
            $word = $instance->_getWordPart('', $size);
            if ($word !== null) {
                return ($word);
            }
        }
        return ($word);
    }

    /**
     * Parse dictionary for words generation
     *
     * @param string $data Crunched dictionary information
     * @return array
     */
    protected function _parseDictionary($data)
    {
        $dictionary = array(
            'start' => array(),
            'end'   => array(),
            'parts' => array(),
        );
        $data = explode("\n", $data);
        foreach ($data as $str) {
            if (!preg_match('/^([A-Z][a-z]*)([\+\-])([\+\-])/', $str, $t)) {
                continue;
            }
            $pn = $t[1];
            if ($t[2] == '+') {
                $dictionary['start'][] = $pn;
            }
            if ($t[3] == '+') {
                $dictionary['end'][] = $pn;
            }
            $part = array();
            $str = substr($str, strlen($t[0]));
            if (preg_match_all('/[A-Z][a-z]*/', $str, $t)) {
                $part = array_shift($t);
            }
            $dictionary['parts'][$pn] = $part;
        }
        return ($dictionary);
    }

    /**
     * Generate part of word
     *
     * @param string $word Currently generated word part
     * @param int $size    Remaining size limit
     * @param string $part Current word part
     * @return string|null
     */
    protected function _getWordPart($word, $size, $part = null)
    {
        $instance = self::getInstance();
        $parts = ($part !== null) ? $instance->_dictionary['parts'][$part] : $instance->_dictionary['start'];
        $oPSz = sizeof($parts);
        $attempts = 100;
        while ($attempts--) {
            $cWord = $word;
            $cSz = $size;
            $next = null;
            $c = 100;
            while ((sizeof($parts)) && ($c--)) {
                $r = mt_rand(0, sizeof($parts) - 1);
                while (!array_key_exists($r, $parts)) {
                    $r = ($r + 1) % $oPSz;
                }
                $t = $parts[$r];
                unset($parts[$r]);
                if (strlen($t) > $cSz) {
                    continue;
                }
                if (($cSz < $instance->_maxPartSize) &&
                    ((strlen($t) != $cSz) || (!in_array($t, $instance->_dictionary['end'])))
                ) {
                    continue;
                }
                $next = $t;
                break;
            }
            if ($next === null) {
                return (null);
            }
            $cWord .= $next;
            $cSz -= strlen($next);
            if ($cSz == 0) {
                return (strtolower($cWord));
            }
            $r = $instance->_getWordPart($cWord, $cSz, $next);
            if ($r !== null) {
                return (strtolower($r));
            }
        }
        return (null);
    }

}
