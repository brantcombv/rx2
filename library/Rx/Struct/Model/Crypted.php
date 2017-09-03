<?php

abstract class Rx_Struct_Model_Crypted extends Rx_Struct_Model_Abstract
{

    /**
     * Implementation of Serializable interface
     *
     * @return string
     */
    public function serialize()
    {
        $data = parent::serialize();
        $crypt = $this->getCrypt();
        $data = $crypt->encrypt($data);
        $data = base64_encode($data);
        return ($data);
    }

    /**
     * Implementation of Serializable interface
     *
     * @param array $data Serialized object data
     * @return void
     */
    public function unserialize($data)
    {
        $data = @base64_decode($data);
        if ($data === false) {
            trigger_error('Failed to decode serialized data', E_USER_ERROR);
            return;
        }
        $crypt = $this->getCrypt();
        $data = $crypt->decrypt($data);
        $data = @unserialize($data);
        if (!is_array($data)) {
            trigger_error('Failed to decode serialized data', E_USER_ERROR);
            return;
        }
        parent::unserialize($data);
    }

    /**
     * Get crypt adapter class to use for structure encryption
     *
     * @return Rx_Crypt_Adapter_Abstract
     */
    abstract protected function getCrypt();

}
