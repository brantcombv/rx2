<?php

class Rx_Scope_Limit
{

    /**
     * Scope limitation identifier
     *
     * @var string $_id
     */
    protected $_id = null;
    /**
     * Scope limitation value
     *
     * @var mixed $_value
     */
    protected $_value = null;
    /**
     * Database column name, limitation will be applied to
     *
     * @var string $_column
     */
    protected $_column = null;
    /**
     * true if null value is allowed in database column, limitation will be applied to
     *
     * @var boolean $_nullAllowed
     */
    protected $_nullAllowed = false;

    /**
     * Class constructor
     *
     * @param string|false $id Scope limitation identifier or false for "no scope" limitation
     * @param mixed $value     OPTIONAL Scope limitation value
     * @param string $column   OPTIONAL Database column name, limitation will be applied to
     * @param boolean $null    OPTIONAL true if null value is allowed in database column, limitation will be applied to
     * @return void
     */
    public function __construct($id, $value = null, $column = null, $null = false)
    {
        if ($id !== false) {
            $value = Rx_Scope::getScope($id, $value);
        }
        $this->_id = $id;
        $this->_value = $value;
        $this->_column = $column;
        $this->_nullAllowed = (boolean)$null;
    }

    /**
     * Get scope limitation identifier
     *
     * @return string
     */
    public function getId()
    {
        return ($this->_id);
    }

    /**
     * Get scope limitation value
     *
     * @return mixed
     */
    public function getValue()
    {
        return ($this->_value);
    }

    /**
     * Get database column name, limitation will be applied to
     *
     * @return string
     */
    public function getColumn()
    {
        return ($this->_column);
    }

    /**
     * Check if null value is allowed in database column, limitation will be applied to
     *
     * @return boolean
     */
    public function isNullAllowed()
    {
        return ($this->_nullAllowed);
    }

}
