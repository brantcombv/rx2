<?php

class Rx_View_Helper_Translate extends Zend_View_Helper_Abstract
{
    /**
     * Translate given message
     *
     * @param string $message       Message or message Id to translate
     * @param boolean $required     true to throw warning message if no translation can be found, false to skip all warnings.
     *                              Can be skipped, true will be mean in this case
     * @param string|null $locale   Locale to translate message to
     * @return string               Translated message
     */
    public function translate($message, $required = true, $locale = null)
    {
        $message = Rx_Translate::translate($message, $required, $locale);
        return ($message);
    }
}
