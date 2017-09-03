<?php

/**
 * Lookup table for list of states in Australia
 */
class Rx_Lookup_States_Au extends Rx_Lookup_Abstract
{

    /**
     * Initialize lookup table contents
     *
     * @return array|void
     */
    protected function init()
    {
        return (array(
            'ACT' => 'Capital Territory',
            'NSW' => 'New South Wales',
            'NT'  => 'Northern Territory',
            'QLD' => 'Queensland',
            'SA'  => 'South Australia',
            'TAS' => 'Tasmania',
            'VIC' => 'Victoria',
            'WA'  => 'Western Australia',
        ));
    }

}