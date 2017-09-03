<?php

class Rx_Db_Adapter_Pdo_Mysql extends Zend_Db_Adapter_Pdo_Mysql
{
    /**
     * Transactions nesting level
     *
     * @var int $_transactionLevel
     */
    protected $_transactionLevel = 0;

    /**
     * Begin a transaction.
     */
    protected function _beginTransaction()
    {
        if ($this->_transactionLevel++ == 0) {
            parent::_beginTransaction();
        }
    }

    /**
     * Commit a transaction.
     */
    protected function _commit()
    {
        if (--$this->_transactionLevel != 0) {
            return;
        }
        parent::_commit();
    }

    /**
     * Roll-back a transaction.
     */
    protected function _rollBack()
    {
        if (--$this->_transactionLevel != 0) {
            return;
        }
        parent::_rollBack();
    }

    /**
     * Quote a raw string.
     *
     * @param string $value Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if ($value === true) {
            return 1;
        } elseif ($value === false) {
            return 0;
        } elseif ($value === null) {
            return 'null';
        } elseif (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        } elseif (is_object($value)) {
            if ($value instanceof Zend_Date) {
                $value = $value->get('yyyy-MM-dd HH:mm:ss');
            } elseif (in_array('Serializable', class_implements($value))) {
                $value = serialize($value);
            } elseif (is_callable(array($value, 'toString'))) {
                $value = $value->toString();
            } else {
                trigger_error(
                    'Unable to convert given instance of class "' . get_class($value) . '" into string',
                    E_USER_WARNING
                );
                $value = 'Object';
            }
            return ($this->quote($value));
        } elseif (preg_match('/^-?0+[1-9]+/', $value)) {
            // It is something like 000001 and more likey NOT a number, so store it as a string
            return "'" . addslashes($value) . "'";
        } elseif (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return $value;
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

}
