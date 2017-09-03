<?php

/**
 * Version of Zend_Db_Select with support of setting scope limitations with use of Rx_Scope
 */
class Rx_Db_Select extends Zend_Db_Select
{
    /**
     * Same as Zend_Db_Select::$_partsInit, but self::UNION is moved lower
     * to fix invalid construction of SQL queries with UNION statement
     *
     * @var array $_partsInit
     * @see Zend_Db_Select#$_partsInit
     */
    protected static $_partsInit = array(
        self::DISTINCT     => false,
        self::COLUMNS      => array(),
        self::FROM         => array(),
        self::WHERE        => array(),
        self::GROUP        => array(),
        self::HAVING       => array(),
        self::UNION        => array(),
        self::ORDER        => array(),
        self::LIMIT_COUNT  => null,
        self::LIMIT_OFFSET => null,
        self::FOR_UPDATE   => false
    );
    /**
     * Model information provider to use for retrieving model-specific information
     *
     * @var Rx_Model_Provider_Abstract $_provider
     */
    protected $_provider = null;
    /**
     * Prefix to add to database table names
     *
     * @var string $_prefix
     */
    protected $_prefix = null;
    /**
     * Flag to control if scope limitation is added to query
     *
     * @var boolean $_scopeAdded
     */
    protected $_scopeAdded = false;

    /**
     * Class constructor
     *
     * @param Zend_Db_Adapter_Abstract $adapter    Database adapter to use for query
     * @param string|null $prefix                  Prefix for database table name
     * @param Rx_Model_Provider_Abstract $provider Model information provider
     */
    public function __construct(Zend_Db_Adapter_Abstract $adapter, $prefix, $provider)
    {
        parent::__construct($adapter);
        $this->_prefix = $prefix;
        $this->_provider = $provider;
        $this->_scopeAdded = false;
    }

    /**
     * Converts this object to an SQL SELECT string.
     *
     * @return string|null This object as a SELECT string. (or null if a string cannot be produced.)
     */
    public function assemble()
    {
        $valid = true;
        /** @var $acl Zend_Acl */
        $acl = $this->_provider->getAcl();
        if ($acl) {
            $role = Rx_User::getRole();
            // Validate if access to all involved tables is granted
            foreach ($this->_parts[Zend_Db_Select::FROM] as $k => $v) {
                if (!$acl->isAllowed($role, $v['tableName'], 'select')) {
                    trigger_error(
                        'Select from table "' . $v['tableName'] . '" is not allowed for role "' . $role . '"',
                        E_USER_WARNING
                    );
                    $valid = false;
                }
            }
        }
        // Convert all table names so they will have prefixes
        foreach ($this->_parts[Zend_Db_Select::FROM] as $k => $v) {
            $this->_parts[Zend_Db_Select::FROM][$k]['tableName'] = $this->_tableName($v['tableName']);
        }
        $sql = $this->_assemble();
        if (($this->_provider->getUseScope()) && (!$this->_scopeAdded)) {
            trigger_error('No scope limitation is added to query (' . $sql . ')', E_USER_WARNING);
        }

        return $sql;
    }

    /**
     * Add scope limitation to the query
     *
     * @param string|array|Rx_Scope_Limit $name Either scope limitation object(s) or database field name to apply scope limitation to
     * @param string $id                        OPTIONAL Scope limitation identifier
     * @param mixed $value                      OPTIONAL Scope values that are planned to use for scope limitation
     * @param boolean $null                     OPTIONAL true if null value is allowed in database column, limitation will be applied to
     * @return Zend_Db_Select                       This Zend_Db_Select object.
     * @throws Rx_Model_Exception
     */
    public function scope($name, $id = null, $value = null, $null = false)
    {
        if (!$this->_provider->getUseScope()) {
            $this->_scopeAdded = true;
            return ($this);
        }
        if ($name instanceof Rx_Scope_Limit) {
            /* @var $name Rx_Scope_Limit */
            $id = $name->getId();
            if ($id === false) {
                // Handle empty scope limitation
                $this->_scopeAdded = true;
                return ($this);
            }
            $scope = $name->getValue();
            $cond = $name->getColumn();
            $null = $name->isNullAllowed();
        } elseif (is_array($name)) {
            foreach ($name as $scope) {
                if (!$scope instanceof Rx_Scope_Limit) {
                    throw new Rx_Model_Exception('Scope limitation object should be instance of Rx_Scope_Limit');
                }
                $this->scope($scope);
            }
            return ($this);
        } else {
            $scope = Rx_Scope::getScope($id, $value);
            $cond = $name;
        }
        /** @var $_cond string */
        $_cond = $cond;
        if (is_array($scope)) {
            // Avoid construction of invalid SQL query
            // in a case if empty array is passed as argument
            if (sizeof($scope)) {
                $cond .= ' in (?)';
            } else {
                $cond = 'true';
                $scope = true;
            }
        } elseif ($scope === true) {
            $cond = 'true';
        } elseif ($scope === false) {
            $cond = 'false';
        } elseif ($scope !== null) {
            $cond .= '=?';
        } else {
            $cond .= ' is null';
        }
        if ($null) {
            $cond = '(' . $cond . ') or (' . $_cond . ' is null)';
        }
        $this->where($cond, $scope);

        $this->_scopeAdded = true;
        return ($this);
    }

    /**
     * Mark query as having no scope limitation
     *
     * @return Zend_Db_Select   This Zend_Db_Select object.
     */
    public function noScope()
    {
        $this->_scopeAdded = true;

        return $this;
    }

    /**
     * Adds WHERE column IN (values) condition to query
     *
     * @param string|array|Zend_Db_Expr $ident      Column identifier
     *                                              (@see Zend_Db_Adapter_Abstract#quoteIdentifier() for syntax)
     * @param string|array|boolean $inValues        List of values for IN condition
     * @param boolean $withAnd                      OPTIONAL true to add condition with AND, false to add with OR
     * @return Rx_Db_Select
     */
    public function whereIn($ident, $inValues, $withAnd = true)
    {
        if (is_string($inValues)) {
            $inValues = array($inValues);
        }
        if ((is_array($inValues)) && (!sizeof(
                $inValues
            ))
        ) // Empty list of values for IN condition should result into empty recordset
        {
            $inValues = new Zend_Db_Expr('false');
        }
        $ident = $this->_adapter->quoteIdentifier($ident);
        if (is_bool($inValues)) {
            $cond = $inValues;
        } // Boolean values should be passed to WHERE clause without change
        elseif ($inValues === null) {
            $cond = $ident . '=?';
        } // null value should result into "$ident is null"
        else {
            $cond = $ident . ' in (?)';
        }
        if ($withAnd) {
            return ($this->where($cond, $inValues));
        } else {
            return ($this->orWhere($cond, $inValues));
        }
    }

    /**
     * Prepend given table name with prefix if necessary
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @return mixed
     */
    protected function _tableName($name)
    {
        if ((!strlen($this->_prefix)) || // There is nothing to do if we have no prefix
            ($name instanceof Zend_Db_Expr)
        ) // Don't deal with expressions, they should be prefixed by hand
        {
            return ($name);
        }
        $n = (is_array($name)) ? key($name) : $name;
        if ((strpos($n, '.') !== false) || // Don't deal with references to different databases or database schemas
            (strncasecmp($n, $this->_prefix, strlen($this->_prefix)) == 0)
        ) // Prefix is already applied
        {
            return ($name);
        }
        if (is_array($name)) {
            $name = array($this->_prefix . $n => current($name));
        } else {
            $name = $this->_prefix . $name;
        }
        return ($name);
    }

    /**
     * Converts this object to an SQL SELECT string.
     * Moved from Zend_Db_Select::assemble() because it needs to use improved $_partsInit member variable
     *
     * @return string|null This object as a SELECT string. (or null if a string cannot be produced.)
     */
    protected function _assemble()
    {
        $sql = self::SQL_SELECT;
        foreach (array_keys(self::$_partsInit) as $part) {
            $method = '_render' . ucfirst($part);
            if (method_exists($this, $method)) {
                $sql = $this->$method($sql);
            }
        }
        return $sql;
    }

    /**
     * Internal function for creating the where clause
     *
     * @param string $condition
     * @param string $value optional
     * @param string $type  optional
     * @param boolean $bool true = AND, false = OR
     * @return string  clause
     */
    protected function _where($condition, $value = null, $type = null, $bool = true)
    {
        if ($value === null) {
            $condition = preg_replace('/\s*(\!\=|\<\>)\s*\?/', ' is NOT null', $condition);
            $condition = preg_replace('/\s*\=\s*\?/', ' is null', $condition);
        }
        return (parent::_where($condition, $value, $type, $bool));
    }

    /**
     * Render UNION query
     *
     * @param string $sql SQL query
     * @return string
     */
    protected function _renderUnion($sql)
    {
        if ($this->_parts[self::UNION]) {
            $parts = array('(' . $sql . ')');
            foreach ($this->_parts[self::UNION] as $union) {
                list($query, $type) = $union;
                if ($query instanceof Zend_Db_Select) {
                    $query = $query->assemble();
                }
                $parts[] = $type;
                $parts[] = '(' . $query . ')';
            }
            $sql = join(' ', $parts);
        }

        return $sql;
    }

}
