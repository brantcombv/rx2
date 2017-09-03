<?php

class Rx_Log_Writer_Translate extends Zend_Log_Writer_Abstract
{

    /**
     * Write a message to the log.
     *
     * @param  array $event log data event
     * @return void
     */
    protected function _write($event)
    {
        $p = strrpos($event['message'], '|');
        if ($p === false) {
            $p = null;
        }
        $message = substr($event['message'], 0, $p);
        $locale = ($p !== null) ? substr($event['message'], $p + 1) : null;
        $required = Rx_Translate::isMessageId($message);
        $current = true;
        Rx_Translate::reportMissedTranslation($message, $locale, $required, $current);
    }

    /**
     * Construct a Zend_Log driver
     *
     * @param  array|Zend_Config $config
     * @return Rx_Log_Writer_Translate
     */
    static public function factory($config)
    {
        return (new self());
    }

}
