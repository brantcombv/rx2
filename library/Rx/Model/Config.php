<?php

class Rx_Model_Config extends Rx_Struct_Abstract
{

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'item_class'        => null,
            // Class name of collection item that is handled by model
            'entity_class'      => null,
            // Class name of entity model of collection item that is handled by model
            'db_table'          => null,
            // Name of database table that stores collection items information
            'id_column'         => 'id',
            // Name of database table column that stores item's Id (primary key)
            'auto_id'           => true,
            // true if database Id is auto-generated on inserting new row in database, false if database Id is defined manually
            'public_uid_column' => null,
            // Name of database table column that stores item's public UID (optional)
            'active_column'     => null,
            // Name of database table column that stores item's activity flag (optional)
            'deleted_column'    => null,
            // Name of database table column that stores item's deletion flag (optional)
            'params'            => array(),
            // Additional parameters that needs to be passed between models
        );
    }

}