<?php

class Rx_Console_Output_Formatter_Base implements Zend_Log_Formatter_Interface
{
    /**
     * Default log message format string
     *
     * @var string $format
     */
    protected $format = "[%date%][%icon%] %message%\n";
    /**
     * Default set of "icons" to indicate log message levels
     *
     * @var array $icons
     */
    protected $icons = array(
        'emerg'     => '!',
        'alert'     => '!',
        'crit'      => '!',
        'err'       => '!',
        'warn'      => '*',
        'notice'    => ' ',
        'info'      => ' ',
        'debug'     => ' ',
        'success'   => '+',
        'fail'      => '-',
        'exception' => '#',
    );

    /**
     * Formats given message
     *
     * @param array $event          Event data
     * @param string $message       OPTIONAL Log message to format (overrides $event['message'])
     * @param string $format        OPTIONAL Log message format
     * @param boolean $multiline    OPTIONAL true to allow multi-line log entries,
     *                              false to keep each log entry on single line (default)
     * @return string               Formatted line to write to the log
     */
    protected function _format($event, $message = null, $format = null, $multiline = false)
    {
        if ($message === null) {
            $message = $event['message'];
            if (!is_string($message)) {
                $message = '***** INVALID LOG MESSAGE FORMAT ***** (' . serialize($message) . ')';
            }
        }
        if ($format === null) {
            $format = $this->format;
        }
        $params = array();
        if (preg_match_all('/%([a-z0-9\_\-]+)%/', $format, $t, PREG_PATTERN_ORDER)) {
            foreach ($t[1] as $v) {
                $params[$v] = null;
            }
        }
        foreach ($event as $name => $value) {
            if (in_array($name, array('timestamp', 'priority', 'priorityName'))) {
                continue;
            }
            $params[$name] = $value;
        }
        $params['message'] = $message;
        $_params = array();
        foreach ($params as $name => $value) {
            $_params['%' . $name . '%'] = $this->_param($name, $value, $event, $multiline);
        }
        $output = strtr($format, $_params);
        return ($output);
    }

    /**
     * Handle log message parameter
     *
     * @param string $name          Parameter name
     * @param mixed $value          Parameter value
     * @param array $event          Event data
     * @param boolean $multiline    OPTIONAL true to allow multi-line log entries,
     *                              false to keep each log entry on single line (default)
     * @return string
     */
    protected function _param($name, $value, $event, $multiline = false)
    {
        switch ($name) {
            case 'date':
                $value = strftime('%Y-%m-%d %H:%M:%S');
                break;
            case 'icon':
                $t = (isset($event['priorityName'])) ? strtolower($event['priorityName']) : null;
                $value = (isset($this->icons[$t])) ? $this->icons[$t] : '?';
                break;
            case 'message':
                break;
            default:
                $value = (array_key_exists($name, $event)) ? $event[$name] : '';
                break;
        }
        if (!$multiline) {
            $value = strtr($value, array("\r" => '', "\t" => ' ', "\n" => ' '));
        }
        return ($value);
    }

    /**
     * Dump given value to be able to store it into log
     *
     * @param mixed $value Value to dump
     * @param int $level   OPTIONAL Deep level of value dumping
     * @param string $path OPTIONAL Path to current value within dump process
     * @return string
     */
    protected function dump($value, $level = 0, $path = '')
    {
        if (is_object($value)) {
            if ($value instanceof Zend_Date) {
                $value = $value->get('yyyy-MM-dd HH:mm:ss');
            } elseif (is_callable(array($value, 'toArray'))) {
                $value = $value->toArray();
            } elseif (in_array('Iterator', class_implements($value))) {
                $array = array();
                foreach ($value as $k => $v) {
                    $array[$k] = $v;
                }
            } else {
                $value = '<object>';
            }
        }
        if (is_array($value)) {
            $value = $this->dumpFilterArray($value, $path);
        }
        if (is_array($value)) {
            $dump = 'array(' . ((sizeof($value)) ? "\n" : '');
            foreach ($value as $k => $v) {
                $dump .= '    [' . $k . '] => ' . $this->dump(
                        $v,
                        $level + 1,
                        $path . ((strlen($path)) ? '|' : '') . $k
                    ) . "\n";
            }
            $dump .= ')';
            $value = $dump;
        } elseif ($value === true) {
            $value = '<true>';
        } elseif ($value === false) {
            $value = '<false>';
        } elseif ($value === null) {
            $value = '<null>';
        } else {
            $value = $this->dumpFilter($value, $path);
        }
        $value = strtr($value, array("\t" => '    ', "\r" => '', "\n" => "\n" . (($level > 0) ? '    ' : '')));
        return ($value);
    }

    /**
     * Filter given value from dumped structure
     *
     * @param mixed $value Value to filter
     * @param string $path Path to value into dumped structure
     * @return mixed
     */
    protected function dumpFilter($value, $path)
    {
        return ($value);
    }

    /**
     * Filter given array from dumped structure
     *
     * @param array $value Array to filter
     * @param string $path Path to value into dumped structure
     * @return mixed
     */
    protected function dumpFilterArray($value, $path)
    {
        return ($value);
    }

    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param array $event Event data
     * @return string           Formatted line to write to the log
     */
    public function format($event)
    {
        return ($this->_format($event));
    }

}
