<?php

class Rx_Crypt_Adapter_Null extends Rx_Crypt_Adapter_Abstract
{

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
        return ($content);
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
        return ($content);
    }

}
