<?php

class Rx_Crypt_Adapter_Xor_Simple extends Rx_Crypt_Adapter_Abstract
{
    /**
     * true if encryption adapter need to get encryption key
     *
     * @var boolean $_needKey
     */
    protected $_needKey = true;

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
        return ($this->_xor($content, $params));
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
        return ($this->_xor($content, $params));
    }

    /**
     * Content encryption/decryption with XOR operation
     *
     * @param string $content Content to process
     * @param array $params   Parameters for processing
     * @return string               Processed content
     */
    protected function _xor($content, $params)
    {
        $key = $params['key'];
        $sz = strlen($key);
        $kPos = 0;
        $cPos = 0;
        $count = strlen($content);
        $result = '';
        while ($count--) {
            $result .= chr(ord($content[$cPos++]) ^ ord($key[$kPos++]));
            $kPos = $kPos % $sz;
        }
        return ($result);
    }

}
