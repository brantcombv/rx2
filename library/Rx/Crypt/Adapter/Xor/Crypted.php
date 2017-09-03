<?php

/**
 * Header format:
 * +0  50 bytes     Key for XOR encryption
 * +50 4 bytes LE   Content size
 * +54 2 bytes LE   Total number of blocks, content is splitted on
 * +56 2 bytes LE   Size of single content block
 * +58 2 bytes LE   Index of first block into content
 * +60 1 byte       Delta for moving current block index
 * +61 1 byte       Initial value of "additional XOR" byte
 * +62 1 byte       Delta for "additional XOR" byte
 * +63 1 byte       0xAA just to avoid cutting header by AES padding remove
 */
class Rx_Crypt_Adapter_Xor_Crypted extends Rx_Crypt_Adapter_Abstract
{
    /**
     * Block size for XOR encryption. Its size is defined to make size of whole header
     * to be multiple of 16 (which is size of single AES block)
     */
    const KEY_SZ = 50;
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
     * Actual implementation of content encryption
     *
     * @param string $content Content for encryption
     * @param array $params   Parameters for encryption
     * @return string               Encrypted content
     * @throws Rx_Crypt_Exception
     */
    protected function _encrypt($content, $params)
    {
        $maxBlocks = 1000; // Too much blocks will cause blocks shuffling to take longer time
        $blockSz = self::KEY_SZ;
        $sz = strlen($content);
        // Generate key for XOR encryption
        $key = Rx_Password::get(Rx_Password::generateIv(self::KEY_SZ, 4));
        // Remove trailing 0x00 from key to avoid losing it during unpack()
        if (ord(substr($key, -1)) == 0x00) {
            $key = substr($key, 0, -1) . chr(rand(1, 255));
        }
        // Prepare list of blocks
        $nBlocks = ceil($sz / $blockSz);
        if ($nBlocks > $maxBlocks) {
            $blockSz = ceil($sz / $maxBlocks);
            $nBlocks = ceil($sz / $blockSz);
        }
        $start = mt_rand(0, $nBlocks - 1);
        // Choose delta from prime numbers for better blocks shuffling
        // Larger numbers will cause blocks shuffling to take longer time and hence not used
        $primes = array(
            3,
            5,
            7,
            11,
            13,
            17,
            19,
            23,
            29,
            31,
            37,
            41,
            43,
            47,
            53,
            59,
            61,
            67,
            71,
            73,
            79,
            83,
            89,
            97,
            101
        );
        shuffle($primes);
        $delta = array_shift($primes);
        // Prepare "additional XOR" value and its delta
        $xorValue = ord(substr($key, 0, 1));
        $xorDelta = max(ord(substr($key, 1, 1)), 1);
        $blocks = $this->_buildBlocks($nBlocks, $start, $delta);
        // Encrypt and store header information in result
        $result = '';
        $header = pack(
            'a' . self::KEY_SZ . 'VvvvCCCC',
            $key,
            $sz,
            $nBlocks,
            $blockSz,
            $start,
            $delta,
            $xorValue,
            $xorDelta,
            0xAA
        );
        $header = Rx_Crypt::encrypt(
            'aes',
            $header,
            array(
                'use_provider' => false,
                'key'          => $params['key'],
                'iv'           => $params['iv'],
                'strength'     => $params['strength'],
            )
        );
        $result .= $header;
        // Split content into blocks and we will be ready to encrypt it
        $content = str_pad($content, $nBlocks * $blockSz, chr(0x00), STR_PAD_RIGHT);
        $content = str_split($content, $blockSz);
        // Encrypt each content block with XOR and append it to result
        $kPos = 0;
        foreach ($blocks as $blockId) {
            $block = $content[$blockId];
            $cPos = 0;
            for ($i = 0; $i < $blockSz; $i++) {
                $result .= chr(ord($block[$cPos++]) ^ ord($key[$kPos++]) ^ $xorValue);
                $kPos = $kPos % self::KEY_SZ;
                $xorValue = ($xorValue + $xorDelta) % 0xFF;
            }
        }
        return ($result);
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
        $result = '';
        $header = substr($content, 0, 64);
        $content = substr($content, 64);
        $header = Rx_Crypt::decrypt(
            'aes',
            $header,
            array(
                'use_provider' => false,
                'key'          => $params['key'],
                'iv'           => $params['iv'],
                'strength'     => $params['strength'],
            )
        );
        $info = unpack(
            'a' . self::KEY_SZ . 'key/Vsz/vnBlocks/vblockSz/vstart/Cdelta/CxorValue/CxorDelta/Cpadding',
            $header
        );
        $contentBlocks = range(0, $info['nBlocks'] - 1);
        $blocks = $this->_buildBlocks($info['nBlocks'], $info['start'], $info['delta']);
        // Decrypt content blocks with XOR
        $contentIndex = 0;
        $kPos = 0;
        foreach ($blocks as $blockId) {
            $block = substr($content, $contentIndex, $info['blockSz']);
            $contentIndex += $info['blockSz'];
            $resultBlock = '';
            $cPos = 0;
            for ($i = 0; $i < $info['blockSz']; $i++) {
                $resultBlock .= chr(ord($block[$cPos++]) ^ ord($info['key'][$kPos++]) ^ $info['xorValue']);
                $kPos = $kPos % self::KEY_SZ;
                $info['xorValue'] = ($info['xorValue'] + $info['xorDelta']) % 0xFF;
            }
            $contentBlocks[$blockId] = $resultBlock;
        }
        $result = substr(join('', $contentBlocks), 0, $info['sz']);
        return ($result);
    }

    /**
     * Build shuffled list of indexes of content blocks
     *
     * @param int $nBlocks Total number of content blocks
     * @param int $start   Index of first content block
     * @param int $delta   Delta for moving current block index
     * @return array
     */
    protected function _buildBlocks($nBlocks, $start, $delta)
    {
        if ($nBlocks == 1) {
            return (array(0));
        }
        $blocks = array();
        $list = array();
        $prev = $nBlocks - 1;
        $next = 1;
        for ($i = 0; $i < $nBlocks; $i++) {
            $list[] = array($prev, $next);
            $prev = ++$prev % $nBlocks;
            $next = ++$next % $nBlocks;
        }
        $current = $start;
        for ($i = 0; $i < $nBlocks; $i++) {
            for ($j = 0; $j < $delta; $j++) {
                $current = $list[$current][1];
            }
            $blocks[] = $current;
            $list[$list[$current][0]][1] = $list[$current][1];
            $list[$list[$current][1]][0] = $list[$current][0];
        }
        return ($blocks);
    }

    /**
     * Get default encryption strength for current adapter
     *
     * @return int|null
     */
    public static function getDefaultStrength()
    {
        return (Rx_Crypt_Adapter_Aes::getDefaultStrength());
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
        return (Rx_Crypt_Adapter_Aes::getIvSize($strength));
    }

}
