<?php

class Rx_Template
{
    protected static $_instance = null;

    protected function __construct()
    {

    }

    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Rx_Template
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Parse given template
     *
     * @param string $template Template for parsing
     * @return array
     */
    public static function parse($template)
    {
        $parsed = array();
        $isPlaceholder = false;
        $_cnt = 100;
        while (($_cnt--) && (strlen($template))) {
            $t = explode('%', $template, 2);
            $text = array_shift($t);
            $template = array_shift($t);
            if ($isPlaceholder) {
                if (strlen($text)) {
                    $part = array(
                        'type'      => 'placeholder',
                        'name'      => null,
                        'modifiers' => array(),
                    );
                    $t = explode('|', $text, 2);
                    $text = array_shift($t);
                    $modifiers = array_shift($t);
                    $part['name'] = $text;
                    // Parse placeholder modifiers
                    if (strlen($modifiers)) {
                        $modifiers = explode('|', $modifiers);
                        foreach ($modifiers as $mod) {
                            $modifier = array(
                                'name' => null,
                                'args' => array(),
                            );
                            $t = explode(':', $mod, 2);
                            $modifier['name'] = array_shift($t);
                            $args = array_shift($t);
                            if (strlen($args)) {
                                $_c = 100;
                                while (($_c--) && (strlen($args))) {
                                    if (!preg_match('/^([^\,]+|\"[^\"]*\"|\'[^\']*\')/', $args, $t)) {
                                        break;
                                    }
                                    $modifier['args'][] = self::getInstance()->cleanupValue($t[1]);
                                    $args = preg_replace('/^' . preg_quote($t[0], '/') . '/', '', $args);
                                    if (substr($args, 0, 1) == ',') {
                                        $args = substr($args, 1);
                                    }
                                }
                            }
                            if ($modifier['name'] !== null) {
                                $part['modifiers'][] = $modifier;
                            }
                        }
                    }
                } else {
                    // Empty placeholder must be treated as single '%' text symbol
                    $part = array(
                        'type' => 'text',
                        'text' => '%',
                    );
                    $parsed[] = $part;
                }
                $parsed[] = $part;
            } else {
                $part = array(
                    'type' => 'text',
                    'text' => $text,
                );
                $parsed[] = $part;
            }
            $isPlaceholder = !$isPlaceholder;
        }
        if (strlen($template)) {
            $part = array(
                'type' => 'text',
                'text' => $template,
            );
            $parsed[] = $part;
        }
        return ($parsed);
    }

    /**
     * Render given template by applying given parameters
     *
     * @param string $template Template for rendering
     * @param array $params    Parameters to apply for template
     * @return string
     */
    public static function render($template, $params = array())
    {
        $instance = self::getInstance();
        $result = '';
        if (!is_array($template)) {
            $template = self::parse($template);
        }
        foreach ($template as $tpl) {
            switch ($tpl['type']) {
                case 'text':
                    $result .= $tpl['text'];
                    break;
                case 'placeholder':
                    $name = $tpl['name'];
                    $value = (array_key_exists($name, $params)) ? $params[$name] : null;
                    foreach ($tpl['modifiers'] as $modifier) {
                        $class = $instance->getModifier($modifier['name']);
                        if ($class) {
                            $value = $class->apply($value, $name, $modifier['args']);
                        }
                    }
                    $result .= $value;
                    break;
                default:
                    trigger_error('Unknown template item type: ' . $tpl['type'], E_USER_WARNING);
                    break;
            }
        }
        return ($result);
    }

    /**
     * Cleanup given value before inserting it into template
     *
     * @param string $value
     * @return bool|null|string
     */
    protected function cleanupValue($value)
    {
        if ($value == 'null') {
            $value = null;
        } elseif ($value == 'true') {
            $value = true;
        } elseif ($value == 'false') {
            $value = false;
        }
        if (in_array(substr($value, 0, 1), array('"', "'"))) {
            $value = substr($value, 1, -1);
        }
        return ($value);
    }

    /**
     * Get template modifier by given name
     *
     * @param string $name Template modifier class name
     * @return Rx_Template_Abstract|null
     */
    protected function getModifier($name)
    {
        $name = 'Rx_Template_' . ucfirst($name);
        try {
            $class = new $name();
            return ($class);
        } catch (Exception $e) {
            trigger_error('Unknown template modifier: ' . $name, E_USER_WARNING);
            return (null);
        }
    }

}
