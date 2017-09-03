<?php

/**
 * @property int $id
 * @property boolean $active
 * @property string $role
 */
class Rx_User_Profile extends Rx_Struct_Abstract
{

    /**
     * Initialize structure fields list
     *
     * @return array|void   Initial structure state
     */
    protected function init()
    {
        $this->_struct = array(
            'id'     => null, // User Id
            'active' => false, // true for active user, false otherwise
            'role'   => null, // User role
        );
    }

    /**
     * Actual implementation of setting structure field value.
     *
     * @param string $name  Structure element name to set value of
     * @param mixed $value  New value for this element
     * @param array $config Configuration options
     * @return void
     */
    protected function _set($name, $value, $config)
    {
        switch ($name) {
            case 'id':
                if ($value !== null) {
                    $value = (int)$value;
                }
                break;
            case 'active':
                $value = (boolean)$value;
                break;
            case 'role':
                $value = trim($value);
                if (!strlen($value)) {
                    $value = null;
                }
                break;
            default:
                break;
        }
        parent::_set($name, $value, $config);
    }

}
