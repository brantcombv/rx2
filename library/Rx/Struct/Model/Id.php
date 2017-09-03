<?php

class Rx_Struct_Model_Id extends Rx_Struct_Model_Abstract
{

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'id'    => null, // Linked Id
            'db_id' => null, // Database Id
        );
    }

    /**
     * Initialize meanings of certain structure fields
     * Passing meanings as argument is useful for inheritance
     *
     * @param array $meanings OPTIONAL Meanings to add (in a form "meaning Id"=>"structure field name")
     * @return array
     */
    protected function initMeanings($meanings = array())
    {
        return (parent::initMeanings(array_merge($meanings, array(
            'db_id' => 'db_id',
        ))));
    }

}
