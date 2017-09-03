<?php

class Rx_Lookup_States_Ca extends Rx_Lookup_Abstract
{

    /**
     * Initialize lookup table contents
     *
     * @return array|void
     */
    protected function init()
    {
        return (array(
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NF' => 'Newfoundland',
            'NT' => 'Northwest Territory',
            'NS' => 'Nova Scotia',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon',
        ));
    }

}