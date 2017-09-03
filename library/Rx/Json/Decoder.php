<?php

class Rx_Json_Decoder
{
    /**
     * Parse tokens used to decode the JSON object. These are not
     * for public consumption, they are just used internally to the
     * class.
     */
    const EOF = 0;
    const DATUM = 1;
    const LBRACE = 2;
    const LBRACKET = 3;
    const RBRACE = 4;
    const RBRACKET = 5;
    const COMMA = 6;
    const COLON = 7;

    /**
     * Use to maintain a "pointer" to the source being decoded
     *
     * @var string $_source
     */
    protected $_source;
    /**
     * Caches the source length
     *
     * @var int $_sourceLength
     */
    protected $_sourceLength;
    /**
     * The offset within the source being decoded
     *
     * @var int $_offset
     */
    protected $_offset;
    /**
     * The current token being considered in the parser cycle
     *
     * @var int $_token
     */
    protected $_token;
    /**
     * Handler for additional post-processing of decoded JSON structures
     *
     * @var Rx_Json_Handler_Interface $_handler
     */
    protected $_handler = null;

    /**
     * Class constructor
     *
     * @param string $source String source to decode
     * @param array $options OPTIONAL Additional options for decoding
     * @return void
     */
    protected function __construct($source, $options = null)
    {
        $this->setOptions($options);
        // Decoder optimization: only call unicode string decoder if it was used for encoding
        if (preg_match('/\\\u[0-9A-F]{4}/i', $source)) {
            $source = Zend_Json_Decoder::decodeUnicodeString($source);
        }
        $this->_source = $source;
        $this->_sourceLength = strlen($this->_source);
        $this->_token = self::EOF;
        $this->_offset = 0;
        // Set pointer at first token
        $this->_getNextToken();
    }

    /**
     * Set options for decoding
     *
     * @param array $options Options for decoding
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
     * Get JSON post-processing handler object
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
     * Get JSON post-processing handler object
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
     * Decode a JSON source string
     *
     * @param string $source String to be decoded
     * @param array $options OPTIONAL Additional options for decoding
     * @return mixed
     * @throws Rx_Json_Exception
     */
    public static function decode($source, $options = null)
    {
        if (!is_string($source)) {
            throw new Rx_Json_Exception('Can only decode JSON encoded strings');
        }
        $decoder = new self($source);
        $result = $decoder->_decodeValue();
        $result = $decoder->_postprocess($result);
        return ($result);
    }


    /**
     * Recursive driving routine for supported toplevel tops
     *
     * @return mixed
     */
    protected function _decodeValue()
    {
        switch ($this->_token) {
            case self::DATUM:
                $result = $this->_tokenValue;
                $this->_getNextToken();
                return ($result);
                break;
            case self::LBRACE:
                return ($this->_decodeObject());
                break;
            case self::LBRACKET:
                return ($this->_decodeArray());
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Decodes an object of the form:
     *  { "attribute: value, "attribute2" : value,...}
     *
     *
     * @throws Rx_Json_Exception
     * @return array
     */
    protected function _decodeObject()
    {
        $result = array();
        $tok = $this->_getNextToken();

        while ($tok && $tok != self::RBRACE) {
            if ($tok != self::DATUM || !is_string($this->_tokenValue)) {
                throw new Rx_Json_Exception('Missing key in object encoding: ' . $this->_getProblemContext());
            }

            $key = $this->_tokenValue;
            $tok = $this->_getNextToken();

            if ($tok != self::COLON) {
                throw new Rx_Json_Exception('Missing ":" in object encoding: ' . $this->_getProblemContext());
            }

            $tok = $this->_getNextToken();
            $result[$key] = $this->_decodeValue();
            $tok = $this->_token;

            if ($tok == self::RBRACE) {
                break;
            }

            if ($tok != self::COMMA) {
                throw new Rx_Json_Exception('Missing "," in object encoding: ' . $this->_getProblemContext());
            }

            $tok = $this->_getNextToken();
        }

        $this->_getNextToken();
        return $result;
    }

    /**
     * Decodes a JSON array format:
     *    [element, element2,...,elementN]
     *
     * @throws Rx_Json_Exception
     * @return array
     */
    protected function _decodeArray()
    {
        $result = array();
        $starttok = $tok = $this->_getNextToken(); // Move past the '['
        $index = 0;

        while ($tok && $tok != self::RBRACKET) {
            $result[$index++] = $this->_decodeValue();

            $tok = $this->_token;

            if ($tok == self::RBRACKET || !$tok) {
                break;
            }

            if ($tok != self::COMMA) {
                throw new Rx_Json_Exception('Missing "," in array encoding: ' . $this->_getProblemContext());
            }

            $tok = $this->_getNextToken();
        }

        $this->_getNextToken();
        return ($result);
    }


    /**
     * Removes whitespace characters from the source input
     *
     * @return void
     */
    protected function _eatWhitespace()
    {
        if (preg_match(
                '/([\t\b\f\n\r ])*/s',
                $this->_source,
                $matches,
                PREG_OFFSET_CAPTURE,
                $this->_offset
            )
            && $matches[0][1] == $this->_offset
        ) {
            $this->_offset += strlen($matches[0][0]);
        }
    }


    /**
     * Retrieves the next token from the source stream
     *
     * @throws Rx_Json_Exception
     * @return int  Token constant value specified in class definition
     */
    protected function _getNextToken()
    {
        $this->_token = self::EOF;
        $this->_tokenValue = null;
        $this->_eatWhitespace();

        if ($this->_offset >= $this->_sourceLength) {
            return (self::EOF);
        }

        $str = $this->_source;
        $str_length = $this->_sourceLength;
        $i = $this->_offset;
        $start = $i;

        switch ($str{$i}) {
            case '{':
                $this->_token = self::LBRACE;
                break;
            case '}':
                $this->_token = self::RBRACE;
                break;
            case '[':
                $this->_token = self::LBRACKET;
                break;
            case ']':
                $this->_token = self::RBRACKET;
                break;
            case ',':
                $this->_token = self::COMMA;
                break;
            case ':':
                $this->_token = self::COLON;
                break;
            case  '"':
                $result = '';
                do {
                    $i++;
                    if ($i >= $str_length) {
                        break;
                    }

                    $chr = $str{$i};

                    if ($chr == '\\') {
                        $i++;
                        if ($i >= $str_length) {
                            break;
                        }
                        $chr = $str{$i};
                        switch ($chr) {
                            case '"' :
                                $result .= '"';
                                break;
                            case '\\':
                                $result .= '\\';
                                break;
                            case '/' :
                                $result .= '/';
                                break;
                            case 'b' :
                                $result .= "\x08";
                                break;
                            case 'f' :
                                $result .= "\x0c";
                                break;
                            case 'n' :
                                $result .= "\x0a";
                                break;
                            case 'r' :
                                $result .= "\x0d";
                                break;
                            case 't' :
                                $result .= "\x09";
                                break;
                            case '\'' :
                                $result .= '\'';
                                break;
                            default:
                                throw new Rx_Json_Exception("Illegal escape sequence '" . $chr . "': " . $this->_getProblemContext());
                        }
                    } elseif ($chr == '"') {
                        break;
                    } else {
                        $result .= $chr;
                    }
                } while ($i < $str_length);

                $this->_token = self::DATUM;
                $this->_tokenValue = $result;
                break;
            case 't':
                if (($i + 3) < $str_length && substr($str, $start, 4) == "true") {
                    $this->_token = self::DATUM;
                }
                $this->_tokenValue = true;
                $i += 3;
                break;
            case 'f':
                if (($i + 4) < $str_length && substr($str, $start, 5) == "false") {
                    $this->_token = self::DATUM;
                }
                $this->_tokenValue = false;
                $i += 4;
                break;
            case 'n':
                if (($i + 3) < $str_length && substr($str, $start, 4) == "null") {
                    $this->_token = self::DATUM;
                }
                $this->_tokenValue = null;
                $i += 3;
                break;
        }

        if ($this->_token != self::EOF) {
            $this->_offset = $i + 1; // Consume the last token character
            return ($this->_token);
        }

        $chr = $str{$i};
        if ($chr == '-' || $chr == '.' || ($chr >= '0' && $chr <= '9')) {
            if (preg_match(
                    '/-?([0-9])*(\.[0-9]*)?((e|E)((-|\+)?)[0-9]+)?/s',
                    $str,
                    $matches,
                    PREG_OFFSET_CAPTURE,
                    $start
                ) && $matches[0][1] == $start
            ) {

                $datum = $matches[0][0];

                if (is_numeric($datum)) {
                    if (preg_match('/^0\d+$/', $datum)) {
                        throw new Rx_Json_Exception('Octal notation not supported by JSON (value: ' . $datum . '): ' . $this->_getProblemContext());
                    } else {
                        $val = intval($datum);
                        $fVal = floatval($datum);
                        $this->_tokenValue = ($val == $fVal ? $val : $fVal);
                    }
                } else {
                    throw new Rx_Json_Exception('Illegal number format: ' . $datum . ': ' . $this->_getProblemContext());
                }

                $this->_token = self::DATUM;
                $this->_offset = $start + strlen($datum);
            }
        } else {
            throw new Rx_Json_Exception('Illegal Token: ' . $this->_getProblemContext());
        }

        return ($this->_token);
    }

    /**
     * Get description of context where decoding problem occurs
     *
     * @return string
     */
    protected function _getProblemContext()
    {
        $sz = 20;
        $map = array("\n" => '\\n', "\r" => '\\r', "\t" => '\\t');
        $start = max($this->_offset - $sz, 0);
        $p1 = strtr(substr($this->_source, $start, $this->_offset - $start), $map);
        $p2 = strtr(substr($this->_source, $this->_offset, $sz), $map);
        $context = '';
        if (strlen($p1)) {
            $context .= '[' . $p1 . ']';
        }
        if ((strlen($context)) && (strlen($p2))) {
            $context .= '#';
        }
        if (strlen($p2)) {
            $context .= '[' . $p2 . ']';
        }
        $context = 'offset: ' . $this->_offset . ', context: ' . $context;
        return ($context);
    }

    /**
     * Post-process given value after decoding
     *
     * @param mixed $value Value to post-process
     * @param string $path "Path" to current value within original structure passed to post-processor
     * @return mixed
     */
    protected function _postprocess($value, $path = '')
    {
        if (!is_array($value)) {
            return ($value);
        }
        if ((sizeof($value) == 1) && (substr(key($value), 0, 2) == '__')) {
            return ($this->getHandler()->jsonDecoderPostProcess(
                substr(key($value), 2),
                current($value),
                $path,
                $this
            ));
        }
        foreach ($value as $k => $v) {
            $value[$k] = $this->_postprocess($v, $path . ((strlen($path)) ? '|' : '') . $k);
        }
        return ($value);
    }

}
