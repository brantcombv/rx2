<?php

abstract class Rx_Crypt_Provider_Abstract extends Rx_Configurable_Object
{
    /**
     * Crypt adapter Id to use by this cryptographic provider
     *
     * @var string $_adapterId
     */
    protected $_adapterId = null;
    /**
     * Encryption class
     *
     * @var Rx_Crypt_Adapter_Abstract $_adapter
     */
    private $_adapter = null;
    /**
     * Encryption key
     *
     * @var string $_key
     */
    private $_key = null;
    /**
     * Initialization vector
     *
     * @var string $_iv
     */
    private $_iv = null;
    /**
     * Encryption strength
     *
     * @var int|null $_strength
     */
    private $_strength = null;

    /**
     * Class constructor
     *
     * @param array|Zend_Config $config OPTIONAL Configuration options for class
     * @return void
     * @throws Rx_Crypt_Exception
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        if ($this->_adapterId) {
            // We have crypt adapter Id defined - initialize cryptographic adapter of defined type
            $adapter = Rx_Crypt::getAdapter($this->_adapterId);
            if (!$adapter) {
                throw new Rx_Crypt_Exception('Unknown crypt adapter type Id: ' . $this->_adapterId);
            }
            $this->setAdapter($adapter);
        } else // Initialize cryptographic parameters directly
        {
            $this->_initCrypt();
        }
    }

    /**
     * Initialize cryptographic parameters
     *
     * @return void
     */
    protected function _initCrypt()
    {
        // This method should normally be overridden
        // to provide required cryptographic parameters
        // IMPORTANT: If non-default encryption strength is used by provider -
        // it should be defined BEFORE setting initialization vector!
    }

    /**
     * Pre-process content before encryption
     *
     * @param string $content Content passed to encryption
     * @return string
     */
    public function preEncrypt($content)
    {
        return ($content);
    }

    /**
     * Post-process of encrypted content
     *
     * @param string $content Encrypted content
     * @return string
     */
    public function postEncrypt($content)
    {
        return ($content);
    }

    /**
     * Pre-process content before decryption
     *
     * @param string $content Content passed for decryption
     * @return string
     */
    public function preDecrypt($content)
    {
        return ($content);
    }

    /**
     * Post-process of decrypted content
     *
     * @param string $content Decrypted content
     * @return string
     */
    public function postDecrypt($content)
    {
        return ($content);
    }

    /**
     * Check if cryptographic adapter is already available
     *
     * @return boolean
     */
    public final function haveAdapter()
    {
        return ($this->_adapter instanceof Rx_Crypt_Adapter_Abstract);
    }

    /**
     * Get encryption adapter
     *
     * @throws Rx_Crypt_Exception
     * @return Rx_Crypt_Adapter_Abstract
     */
    public final function getAdapter()
    {
        if (!$this->_adapter) {
            throw new Rx_Crypt_Exception('Cryptographic adapter is not initialized');
        }
        return ($this->_adapter);
    }

    /**
     * Set encryption adapter
     *
     * @param Rx_Crypt_Adapter_Abstract $adapter Encryption adapter
     * @return void
     * @throws Rx_Crypt_Exception
     */
    protected final function setAdapter($adapter)
    {
        if (!$adapter instanceof Rx_Crypt_Adapter_Abstract) {
            throw new Rx_Crypt_Exception('Encryption adapter must be instance of Rx_Crypt_Adapter_Abstract');
        }
        // Assign ourselves as provider for new adapter, enable provider usage
        $adapter->setConfig(array(
            'provider'     => $this,
            'use_provider' => true,
        ));
        $this->_adapter = $adapter;
        // Re-initialize cryptographic parameters because adapter is changed
        $this->_key = null;
        $this->_iv = null;
        $this->_strength = null;
    }

    /**
     * Get encryption key
     *
     * @return string $key          Encryption key
     * @throws Rx_Crypt_Exception
     */
    public final function getKey()
    {
        if ($this->_key === false) // It is known that parameter is not required
        {
            return (null);
        } elseif ($this->_key !== null) // We know parameter value
        {
            return ($this->_key);
        } elseif (!$this->getAdapter()->isParamRequired('key')) {
            // Parameter is now known to not be required
            $this->_key = false;
            return (null);
        }
        // Parameter is required but not defined
        $this->_initCrypt();
        if ($this->_key === null) {
            throw new Rx_Crypt_Exception('Cryptographic key is not initialized');
        }
        return ($this->_key);
    }

    /**
     * Set encryption key
     *
     * @param string $key Encryption key
     * @return void
     * @throws Rx_Crypt_Exception
     */
    protected final function setKey($key)
    {
        if ($this->getAdapter()->isParamRequired('key')) {
            if (!strlen($key)) {
                throw new Rx_Crypt_Exception('Cryptographic key is required, it can\'t be empty');
            }
            $this->_key = $key;
        } else {
            $this->_key = false;
        }
    }

    /**
     * Get encryption initialization vector
     *
     * @throws Rx_Crypt_Exception
     * @return string
     */
    public final function getIv()
    {
        if ($this->_iv === false) // It is known that parameter is not required
        {
            return (null);
        } elseif ($this->_iv !== null) // We know parameter value
        {
            return ($this->_iv);
        } elseif (!$this->getAdapter()->isParamRequired('iv')) {
            // Parameter is now known to not be required
            $this->_iv = false;
            return (null);
        }
        // Parameter is required but not defined
        $this->_initCrypt();
        if ($this->_iv === null) {
            throw new Rx_Crypt_Exception('Cryptographic initialization vector is not initialized');
        }
        return ($this->_iv);
    }

    /**
     * Set encryption initialization vector
     *
     * @param string $iv Encryption initialization vector
     * @return void
     * @throws Rx_Crypt_Exception
     */
    protected final function setIv($iv)
    {
        if ($this->getAdapter()->isParamRequired('iv')) {
            if (!strlen($iv)) // Empty IV but non-empty is required
            {
                throw new Rx_Crypt_Exception('Cryptographic initialization vector is required by cryptographic adapter but empty IV is given');
            }
            $this->_iv = $this->normalizeIv($iv);
        } else {
            $this->_iv = false;
        }
    }

    /**
     * Get encryption strength
     *
     * @return int|null
     */
    public final function getStrength()
    {
        if ($this->_strength === false) // It is known that parameter is not required
        {
            return (null);
        } elseif ($this->_strength !== null) // We know parameter value
        {
            return ($this->_strength);
        } elseif (!$this->getAdapter()->isParamRequired('strength')) {
            // Parameter is now known to not be required
            $this->_strength = false;
            return (null);
        }
        // Parameter is required but not defined, use default encryption strength
        $this->_strength = $this->getAdapter()->getDefaultStrength();
        return ($this->_strength);
    }

    /**
     * Set encryption strength
     *
     * @param int $strength Encryption strength
     * @return void
     * @throws Rx_Crypt_Exception
     */
    protected final function setStrength($strength)
    {
        if ($this->getAdapter()->isParamRequired('strength')) {
            // For valid encryption strength we expect to get valid IV size
            $sz = $this->getAdapter()->getIvSize($strength);
            if ($sz === null) {
                throw new Rx_Crypt_Exception('Invalid enctyption strength: ' . $strength);
            }
            $this->_strength = $strength;
        } else {
            $this->_strength = false;
        }
    }

    /**
     * Get crypt provider Id
     *
     * @return string
     */
    public function getProviderId()
    {
        $class = get_class($this);
        $id = null;
        if (preg_match('/_Crypt_Provider_(.+)$/i', $class, $t)) {
            $id = $t[1];
        } else {
            $id = explode('_', $class);
            $id = array_pop($id);
        }
        $id = strtolower($id);
        return ($id);
    }

    /**
     * Normalize given initialization vector so it will match
     * cryptographic adapter size requirements on IV
     *
     * @param string $iv Initialization vector to normalize
     * @return string
     */
    protected function normalizeIv($iv)
    {
        $sz = $this->getAdapter()->getIvSize($this->getStrength());
        if ($sz === null) {
            return (null);
        }
        if (strlen($iv) != $sz) {
            $iv = substr(sprintf('%' . $sz . 's', $iv), 0, $sz);
        }
        return ($iv);
    }

}
