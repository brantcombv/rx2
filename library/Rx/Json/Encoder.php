<?php

class Rx_Json_Encoder
{

    /**
     * Handler for additional pre-processing of given structures
     * before encoding them in JSON
     *
     * @var Rx_Json_Handler_Interface $_handler
     */
    protected $_handler = null;

    /**
     * Class constructor
     *
     * @param array $options OPTIONAL Options for encoding
     * @return Rx_Json_Encoder
     */
    protected function __construct($options = null)
    {
        $this->setOptions($options);
    }

    /**
     * Set options for encoding
     *
     * @param array $options Options for encoding
     * @return void
     */
    public function setOptions($options)
    {
        if (!is_array($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            $method = 'set' . ucfirst($name);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Get JSON pre-processing handler object
     *
     * @return Rx_Json_Handler_Interface
     */
    public function getHandler()
    {
        if (!$this->_handler) {
            $this->setHandler(new Rx_Json_Handler_Default());
        }
        return ($this->_handler);
    }

    /**
     * Get JSON pre-processing handler object
     *
     * @param Rx_Json_Handler_Interface $handler
     * @return void
     * @throws Rx_Json_Exception
     */
    public function setHandler($handler)
    {
        if (!in_array('Rx_Json_Handler_Interface', class_implements($handler))) {
            throw new Rx_Json_Exception('JSON handler should implement Rx_Json_Handler_Interface interface');
        }
        $this->_handler = $handler;
    }

    /**
     * Encode given value in JSON format
     *
     * @param mixed $value   Value to encode
     * @param array $options OPTIONAL Additional options for encoding
     * @return string
     */
    public static function encode($value, $options = null)
    {
        $encoder = new self($options);
        $value = $encoder->_preprocess($value);
        return ($encoder->_encode($value));
    }

    /**
     * Pre-process given value before encoding
     *
     * @param mixed $value Value to pre-process
     * @param string $path "Path" to current value within original structure passed to pre-processor
     * @return mixed
     */
    protected function _preprocess($value, $path = '')
    {
        $key = $this->getHandler()->jsonEncoderPreProcess($value, $path, $this);
        if ($key !== null) {
            return (array('__' . $key => $value));
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->_preprocess($v, $path . ((strlen($path)) ? '|' : '') . $k);
            }
        }
        return ($value);
    }

    /**
     * Encode given value in JSON format
     *
     * @param mixed $value Value to encode
     * @return string
     */
    protected function _encode($value)
    {
        if ($value === null) {
            return ('null');
        } elseif ($value === true) {
            return ('true');
        } elseif ($value === false) {
            return ('false');
        } elseif (is_int($value)) {
            return ($value);
        } elseif (is_float($value)) {
            return (str_replace(',', '.', strval($value)));
        } elseif (is_string($value)) {
            static $chars = array(
                array("\\", "/", "\n", "\t", "\r", "\f", '"'),
                array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\f', '\"'),
            );
            if (preg_match('//u', $value)) {
                $value = str_replace($chars[0], $chars[1], $value);
            } elseif (function_exists('json_encode')) {
                return (json_encode($value)); // Not used by default since it quotes all non-ASCII chars
            } else {
                // Not used by default because of ZF-6777
                if (!class_exists('Zend_Json_Encoder', true)) {
                    trigger_error('Failed to find a way to JSON-encode string', E_USER_WARNING);
                    return ('');
                }
                $value = Zend_Json_Encoder::encodeUnicodeString($value);
            }
            return ('"' . $value . '"');
        } elseif (is_array($value)) {
            $keys = array_keys($value);
            $range = range(0, sizeof($value) - 1);
            if (!sizeof($keys)) {
                return ('[]');
            } elseif ($keys === $range) {
                foreach ($value as $k => $v) {
                    $value[$k] = $this->_encode($v);
                }
                return ('[' . join(',', $value) . ']');
            } else {
                $result = array();
                foreach ($value as $k => $v) {
                    $result[] = $this->_encode('' . $k) . ':' . $this->_encode($v);
                }
                return ('{' . join(',', $result) . '}');
            }
        } elseif (is_object($value)) {
            if ($value instanceof Zend_Json_Expr) {
                return ($value);
            } elseif (method_exists($value, 'toArray')) {
                return ($this->_encode($value->toArray()));
            } elseif (method_exists($value, 'toString')) {
                return ($this->_encode($value->toString()));
            } else {
                if (!class_exists('Zend_Json_Encoder', true)) {
                    trigger_error('Failed to encode object to JSON', E_USER_WARNING);
                    return ('');
                }
                return (Zend_Json_Encoder::encode($value));
            }
        } else {
            trigger_error('Unsupported value type: ' . gettype($value));
        }
    }

}
