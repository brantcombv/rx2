<?php

abstract class Rx_Crypt_Adapter_Abstract extends Rx_Configurable_Object implements Serializable
{

    /**
     * true if encryption adapter need to get encryption key
     *
     * @var boolean $_needKey
     */
    protected $_needKey = false;
    /**
     * true if encryption adapter need to get encryption initialization vector
     *
     * @var boolean $_needIv
     */
    protected $_needIv = false;
    /**
     * true if encryption adapter need to get encryption strength
     *
     * @var boolean $_needStrength
     */
    protected $_needStrength = false;

    /**
     * Encrypt given content
     *
     * @param string $content                Content for encryption
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return string                           Encrypted content
     * @throws Rx_Crypt_Exception
     */
    public function encrypt($content, $config = null)
    {
        $config = $this->getConfig($config);
        $provider = $this->_getProvider($config);
        if ($provider) {
            $content = $provider->preEncrypt($content);
        }
        $params = $this->_getParams($config);
        $result = $this->_encrypt($content, $params);
        if ($provider) {
            $content = $provider->postEncrypt($content);
        }
        return ($result);
    }

    /**
     * Actual implementation of content encryption
     *
     * @param string $content Content for encryption
     * @param array $params   Parameters for encryption
     * @return string               Encrypted content
     * @throws Rx_Crypt_Exception
     */
    abstract protected function _encrypt($content, $params);

    /**
     * Decrypt given content
     *
     * @param string $content                Content to decrypt
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return string                           Decrypted content
     * @throws Rx_Crypt_Exception
     */
    public function decrypt($content, $config = null)
    {
        $config = $this->getConfig($config);
        $provider = $this->_getProvider($config);
        if ($provider) {
            $content = $provider->preDecrypt($content);
        }
        $params = $this->_getParams($config);
        $result = $this->_decrypt($content, $params);
        if ($provider) {
            $content = $provider->postDecrypt($content);
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
    abstract protected function _decrypt($content, $params);

    /**
     * Generate initialization vector for encryption
     *
     * @param string $strength OPTIONAL Encryption strength
     * @return string               Initialization vector
     */
    public function generateIv($strength = null)
    {
        $size = $this->getIvSize($strength);
        if ($size === null) {
            return (null);
        }
        mt_srand((double)microtime() * 1000000);
        $iv = '';
        for ($i = 0; $i < $size; $i++) {
            $iv .= chr(mt_rand(1, 255));
        }
        return ($iv);
    }

    /**
     * Get default encryption strength for current adapter
     *
     * @return int|null
     */
    public static function getDefaultStrength()
    {
        return (null);
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
        return (null);
    }

    /**
     * Get crypt adapter Id
     *
     * @return string
     */
    public function getAdapterId()
    {
        $class = get_class($this);
        $id = null;
        if (preg_match('/_Crypt_Adapter_(.+)$/i', $class, $t)) {
            $id = $t[1];
        } else {
            $id = explode('_', $class);
            $id = array_pop($id);
        }
        $id = strtolower($id);
        return ($id);
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'provider':
                if (($value === null) && (!$this->getConfig('use_provider'))) {
                    return (true);
                } // We don't need to use provider so it can be null
                if (!$value instanceof Rx_Crypt_Provider_Abstract) {
                    trigger_error(
                        'Crypt information provider must be instance of Rx_Crypt_Provider_Abstract',
                        E_USER_WARNING
                    );
                    return (false);
                }
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'provider'     => null, // Crypt information provider class to use for retrieving encryption information.
            // Class must be instance of Rx_Crypt_Provider_Abstract
            'use_provider' => true, // true if information from crypt provider have preference over explictly defined encryption parameters
            'key'          => null, // Encryption key
            'iv'           => null, // Initialization vector to use for encryption
            'strength'     => null, // Encryption strength
        ));
    }

    /**
     * Get cryptographic provider
     *
     * @param array $config Configuration options
     * @return Rx_Crypt_Provider_Abstract|null
     */
    protected function _getProvider($config)
    {
        $provider = (($config['use_provider']) &&
            ($config['provider'] instanceof Rx_Crypt_Provider_Abstract)) ? $config['provider'] : null;
        return ($provider);
    }

    /**
     * Get parameters for encryption adapter
     *
     * @param array $config Configuration options
     * @return array
     */
    protected function _getParams($config)
    {
        // Copy parameters from $config to make sure
        // that additional configuration options are also
        // passed cryptographic methods implementations
        $params = $config;
        $provider = $this->_getProvider($config);
        if ($provider) {
            $params['key'] = $provider->getKey();
            $params['iv'] = $provider->getIv();
            $params['strength'] = $provider->getStrength();
        }
        unset($params['provider']);
        unset($params['use_provider']);
        unset($params[Rx_Configurable_Abstract::CONFIG_CLASS_ID_KEY]);
        return ($params);
    }

    /**
     * Check if encryption parameter with given name is required by adapter
     *
     * @param string $name Adapter parameter name to check
     * @return boolean
     */
    public function isParamRequired($name)
    {
        switch ($name) {
            case 'key':
                return ($this->_needKey);
                break;
            case 'iv':
                return ($this->_needIv);
                break;
            case 'strength':
                return ($this->_needStrength);
                break;
            default:
                trigger_error('Unknown encryption parameter name: ' . $name, E_USER_WARNING);
                return (false);
                break;
        }
    }

    /**
     * Implementation of Serializable interface
     * To avoid leaking of sensitive information (like encryption keys) all encryption adapters
     * are forced to be not serializable
     *
     * @return string
     */
    public function serialize()
    {
        return (serialize(array()));
    }

    /**
     * Implementation of Serializable interface
     * To avoid leaking of sensitive information (like encryption keys) all encryption adapters
     * are forced to be not serializable
     *
     * @param array $data Serialized object data
     * @return void
     */
    public function unserialize($data)
    {
        return;
    }

}
