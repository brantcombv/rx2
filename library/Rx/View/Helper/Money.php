<?php

class Rx_View_Helper_Money extends Zend_View_Helper_Abstract
{
    protected $options = array(
        'currency' => null,
        // Currency to use for value formatting
        'exchange' => true,
        // true to exchange given value from DEFAULT to currency defined into $currency before formatting, false to skip exchanging
    );

    /**
     * Display formatted money value
     *
     * @param float $money   Money value to format
     * @param array $options Rendering options (overrides default options)
     * @return string
     */
    public function money($money, $options = array())
    {
        $options = array_merge($this->options, $options);
        $currency = $options['currency'];
        unset($options['currency']);
        $exchange = (boolean)$options['exchange'];
        unset($options['exchange']);
        $html = Rx_Currency::format($money, $currency, $exchange, $options);
        return ($html);
    }
}
