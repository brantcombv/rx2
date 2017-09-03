<?php

abstract class Rx_Model_Abstract extends Rx_Configurable_Object
{
    /**
     * Model information provider object
     *
     * @var Rx_Model_Provider_Abstract $_provider
     */
    private $_provider = null;
    /**
     * Current database adapter object
     *
     * @var Zend_Db_Adapter_Abstract $_db
     */
    private $_db = null;
    /**
     * Database tables prefix for current database adapter
     *
     * @var string $_prefix
     */
    private $_prefix = null;
    /**
     * Cache to use for storing model information
     *
     * @var Zend_Cache_Core $_cache
     */
    private $_cache = null;
    /**
     * Logger objects to use for model
     *
     * @var array $_logs
     */
    private $_logs = null;
    /**
     * ACL object to use for managing permissions to access to database tables
     *
     * @var Zend_Acl $_acl
     */
    private $_acl = null;

    /**
     * Indicates if model class can work properly with single instance.
     * Used by Rx_ModelManager
     *
     * @return boolean  true if class instance can be stored in registry and re-used
     *                  false if new instance of class must be created on every request
     */
    public static function isSingleton()
    {
        return (true);
    }

    /**
     * Get model information provider object
     *
     * @return Rx_Model_Provider_Abstract
     * @throws Rx_Model_Exception
     */
    public function getProvider()
    {
        if ($this->_provider) {
            return ($this->_provider);
        }
        // No provider is available yet, attempt to get default provider from registry
        if (Zend_Registry::isRegistered('Rx_Model_Provider')) {
            $provider = Zend_Registry::get('Rx_Model_Provider');
            if ($provider instanceof Rx_Model_Provider_Abstract) {
                $this->setProvider($provider);
                if ($this->_provider) {
                    return ($this->_provider);
                }
            }
        }
        throw new Rx_Model_Exception('No model information provider is defined');
    }

    /**
     * @param Rx_Model_Provider_Abstract $provider Model information provider object
     * @return void
     * @throws Rx_Model_Exception
     */
    public function setProvider($provider)
    {
        if (!$provider instanceof Rx_Model_Provider_Abstract) {
            throw new Rx_Model_Exception('Model information provider must be instance of Rx_Model_Provider_Abstract');
        }
        $this->_provider = $provider;
        // Clear cached objects from model information provider and select default database adapter
        $this->_db = null;
        $this->_prefix = null;
        $this->_cache = null;
        $this->_logs = array();
        $this->_acl = null;
        $this->selectAdapter();
    }

    /**
     * Get cache that is used by this model
     *
     * @return Zend_Cache_Core
     */
    public function getCache()
    {
        if (!$this->_cache) {
            $this->_cache = $this->getProvider()->getCache($this);
        }
        return ($this->_cache);
    }

    /**
     * Construct cache entry identifier by given type and additional parameters
     *
     * @param string $type      Type of cache identifier to construct
     * @param mixed $params     Additional parameters for constructing cache Id (either single value or array of parameters)
     * @param boolean $useClass OPTIONAL true to make cache identifier specific to single class (default), false to not do it
     * @return string
     */
    protected function getCacheId($type, $params = null, $useClass = true)
    {
        $cacheId = array();
        if ($useClass) {
            $cacheId[] = get_class($this);
        }
        $cacheId[] = $type;
        if ($params !== null) {
            $_params = array();
            if (!is_array($params)) {
                $params = array($params);
            }
            foreach ($params as $k => $v) {
                if (is_object($v)) {
                    if (method_exists($v, 'toString')) {
                        $v = $v->toString();
                    } elseif (method_exists($v, 'toArray')) {
                        $v = $v->toArray();
                    } else {
                        $v = serialize($v);
                    }
                }
                if (is_array($v)) {
                    // Determine if we get associative or numeric array
                    $assoc = (array_diff_key($v, array_keys(array_keys($v))));
                    asort($v); // To prevent creation of different cache Ids for arrays
                    // with same values but passed in different order
                    if ($assoc) {
                        $nv = array();
                        foreach ($v as $vk => $vv) {
                            $nv[] = $vk . '_' . $vv;
                        }
                    } else {
                        $nv = $v;
                    }
                    $v = join('_', $nv);
                }
                if ((strlen($v) >= 32) || (preg_match('/[^a-z0-9\_]/i', $v))) {
                    $v = Rx_Uid::getUid($v, true, true);
                }
                $_params[] = (is_numeric($k)) ? $v : $k . '_' . $v;
            }
            $_params = join('_', $_params);
            if (strlen($_params) >= 50) {
                $_params = Rx_Uid::getLongUid($_params, true);
            }
            $cacheId[] = $_params;
        }
        $cacheId = join('_', $cacheId);
        return ($cacheId);
    }

    /**
     * Get list of tags for cache
     *
     * @param array|string|null $tags List of tags to use
     * @return array
     */
    protected function getCacheTags($tags = null)
    {
        $_tags = array();
        if ($tags !== null) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }
        } else {
            $tags = array();
        }
        $tags = array_merge($_tags, $tags);
        return ($tags);
    }

    /**
     * Get logger object to use for model
     *
     * @param string $type OPTIONAL Logger type to get
     * @return Zend_Log
     */
    public function getLog($type = null)
    {
        static $_dummy = null;

        $_type = ($type !== null) ? $type : '_default';
        $log = null;
        if (array_key_exists($_type, $this->_logs)) {
            $log = $this->_logs[$_type];
        } else {
            $log = $this->getProvider()->getLog($type, $this);
            if ($log instanceof Zend_Log) {
                $this->_logs[$_type] = $log;
            } else {
                if (!$_dummy) {
                    $_dummy = new Zend_Log(new Zend_Log_Writer_Null());
                }
                $log = $_dummy;
            }
        }
        return ($log);
    }

    /**
     * Get ACL object that is used for managing permissions to access to database tables
     *
     * @return Zend_Acl|null
     */
    public function getAcl()
    {
        if (!$this->_acl) {
            $this->_acl = $this->getProvider()->getAcl($this);
        }
        return ($this->_acl);
    }

    /**
     * Select adapter type to be used by model
     *
     * @param string|null $type OPTIONAL Database type identifier to select
     * @return void
     */
    public function selectAdapter($type = null)
    {
        $this->_db = $this->getProvider()->getDbAdapter($type, $this);
        $this->_prefix = $this->getProvider()->getDbPrefix($type, $this);
        // All fetches will be done as associative arrays by default
        $this->_db->setFetchMode(Zend_Db::FETCH_ASSOC);
    }

    /**
     * Gets the Zend_Db_Adapter_Abstract for this model
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        if (!$this->_db) {
            $this->selectAdapter();
        }
        return ($this->_db);
    }

    /**
     * Creates and returns a new Zend_Db_Select object for select query
     *
     * @return Rx_Db_Select
     */
    public function select()
    {
        return new Rx_Db_Select($this->_db, $this->_prefix, $this->getProvider());
    }

    /**
     * Inserts a table row with specified data.
     * Code is taken from Zend_Db_Adapter_Abstract
     *
     * @param mixed $table                The table to insert data into.
     * @param array $bind                 Column-value pairs.
     * @param Rx_Scope_Limit|array $scope OPTIONAL Scope limitation for query
     * @return int|false                    The number of affected rows or false in a case of error
     */
    public function insert($table, array $bind, $scope = null)
    {
        if ($this->getUseScope()) {
            $scopes = (is_array($scope)) ? $scope : array($scope);
            $quoted = null;
            foreach ($scopes as $scope) {
                if (!$scope instanceof Rx_Scope_Limit) {
                    trigger_error(
                        'Scope limitation usage is required by model provider but no scope is provided',
                        E_USER_ERROR
                    );
                    return (false);
                }
                if ($scope->getId() !== false) {
                    $sColumn = $scope->getColumn();
                    $sValue = $scope->getValue();
                    $sNull = $scope->isNullAllowed();
                    // If scope value is true - there is no need to test it at all
                    if ($sValue === true) {
                        continue;
                    }
                    if (array_key_exists($sColumn, $bind)) {
                        $bValue = $bind[$sColumn];
                    } else {
                        $found = false;
                        // Column name is probably quoted
                        if (strpos($sColumn, $this->_db->getQuoteIdentifierSymbol()) !== false) {
                            if (!is_array($quoted)) {
                                foreach (array_keys($bind) as $key) {
                                    $quoted[$this->_db->quoteIdentifier($key)] = $key;
                                }
                            }
                            if (array_key_exists($sColumn, $quoted)) {
                                $bValue = $bind[$quoted[$sColumn]];
                                $found = true;
                            }
                        }
                        if (!$found) {
                            trigger_error(
                                'Scope limitation column "' . $sColumn . '" is not found into inserted data',
                                E_USER_ERROR
                            );
                            return (false);
                        }
                    }
                    if ($bValue instanceof Zend_Db_Expr) {
                        $bValue = $bValue->__toString();
                    }
                    if ((((is_array($sValue)) && (!in_array($bValue, $sValue))) ||
                            ((!is_array($sValue)) && ($sValue != $bValue))) &&
                        ((!$sNull) || (($sNull) && ($bValue !== null)))
                    ) {
                        trigger_error(
                            'Scope limitation in effect: given value for column "' . $sColumn . '" (' . $bValue . ') is not in a list of allowed values (' . ((is_array(
                                $sValue
                            )) ? join(',', $sValue) : $sValue) . ')',
                            E_USER_ERROR
                        );
                        return (false);
                    }
                }
            }
        }
        if (!$this->_isAllowed('insert', $table)) {
            return (false);
        }

        $cols = array();
        $vals = array();
        foreach ($bind as $col => $val) {
            $cols[] = $this->_db->quoteIdentifier($col, true);
            if ($val instanceof Zend_Db_Expr) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                $bind[$col] = $this->quoteBind($val);
                $vals[] = '?';
            }
        }

        $table = $this->_tableName($table);

        // build the statement
        $sql = "INSERT INTO "
            . $this->_db->quoteIdentifier($table, true)
            . ' (' . implode(', ', $cols) . ') '
            . 'VALUES (' . implode(', ', $vals) . ')';

        // execute the statement and return the number of affected rows
        $stmt = $this->_db->query($sql, array_values($bind));
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Updates table rows with specified data based on a WHERE clause.
     * Code is taken from Zend_Db_Adapter_Abstract
     *
     * @param  mixed $table               The table to update.
     * @param  array $bind                Column-value pairs.
     * @param  mixed $where               OPTIONAL UPDATE WHERE clause(s).
     * @param Rx_Scope_Limit|array $scope OPTIONAL Scope limitation for query
     * @throws Zend_Db_Adapter_Exception
     * @return int|boolean                  The number of affected rows or false in a case of error
     */
    public function update($table, array $bind, $where = '', $scope = null)
    {
        if (($this->getUseScope()) && (!$scope instanceof Rx_Scope_Limit) && (!is_array($scope))) {
            trigger_error(
                'Scope limitation usage is required by model provider but no scope is provided',
                E_USER_ERROR
            );
            return (false);
        }
        if (!$this->_isAllowed('update', $table)) {
            return (false);
        }
        /**
         * Build "col = ?" pairs for the statement,
         * except for Zend_Db_Expr which is treated literally.
         */
        $set = array();
        $i = 0;
        foreach ($bind as $col => $val) {
            if ($val instanceof Zend_Db_Expr) {
                $val = $val->__toString();
                unset($bind[$col]);
            } else {
                if ($this->_db->supportsParameters('positional')) {
                    $bind[$col] = $this->quoteBind($val);
                    $val = '?';
                } else {
                    if ($this->_db->supportsParameters('named')) {
                        unset($bind[$col]);
                        $bind[':' . $col . $i] = $this->quoteBind($val);
                        $val = ':' . $col . $i;
                        $i++;
                    } else {
                        /** @see Zend_Db_Adapter_Exception */
                        throw new Zend_Db_Adapter_Exception(get_class(
                                $this
                            ) . " doesn't support positional or named binding");
                    }
                }
            }
            $set[] = $this->_db->quoteIdentifier($col, true) . ' = ' . $val;
        }

        $table = $this->_tableName($table);
        $where = $this->_whereExpr($where);
        $where = $this->_scopeWhere($where, $scope);

        /**
         * Build the UPDATE statement
         */
        $sql = "UPDATE "
            . $this->_db->quoteIdentifier($table, true)
            . ' SET ' . implode(', ', $set)
            . (($where) ? " WHERE $where" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        if ($this->_db->supportsParameters('positional')) {
            $stmt = $this->_db->query($sql, array_values($bind));
        } else {
            $stmt = $this->_db->query($sql, $bind);
        }
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Deletes table rows based on a WHERE clause.
     * Code is taken from Zend_Db_Adapter_Abstract
     *
     * @param  mixed $table               The table to update.
     * @param  mixed $where               OPTIONAL DELETE WHERE clause(s).
     * @param Rx_Scope_Limit|array $scope OPTIONAL Scope limitation for query
     * @return int|false                    The number of affected rows or false in a case of error
     */
    public function delete($table, $where = '', $scope = null)
    {
        if (($this->getUseScope()) && (!$scope instanceof Rx_Scope_Limit) && (!is_array($scope))) {
            trigger_error(
                'Scope limitation usage is required by model provider but no scope is provided',
                E_USER_ERROR
            );
            return (false);
        }
        if (!$this->_isAllowed('delete', $table)) {
            return (false);
        }
        $table = $this->_tableName($table);
        $where = $this->_whereExpr($where);
        $where = $this->_scopeWhere($where, $scope);

        /**
         * Build the DELETE statement
         */
        $sql = "DELETE FROM "
            . $this->_db->quoteIdentifier($table, true)
            . (($where) ? " WHERE $where" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        $stmt = $this->_db->query($sql);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Get "use scope" status for model
     *
     * @return boolean
     */
    public function getUseScope()
    {
        $useScope = $this->getProvider()->getUseScope($this);
        if (Rx_Scope::getProvider()->isScopingDisabled()) {
            $useScope = false;
        }
        return ($useScope);
    }

    /**
     * Add scope limitation to database query
     *
     * @param string|array|Rx_Scope_Limit $name Either scope limitation object(s) or database field name to apply scope limitation to
     * @param string $id                        OPTIONAL Scope limitation identifier
     * @param mixed $value                      OPTIONAL Scope values that are planned to use for scope limitation
     * @param boolean $null                     OPTIONAL true if null value is allowed in database column, limitation will be applied to
     * @return Rx_Scope_Limit|array                 Either single scope limit or array of Rx_Scope_Limit
     */
    public function scope($name, $id = null, $value = null, $null = false)
    {
        if (($name instanceof Rx_Scope_Limit) || (is_array($name))) {
            return ($name);
        }
        return (new Rx_Scope_Limit($id, $value, $name, $null));
    }

    /**
     * Mark query as having no scope limitation
     *
     * @return Rx_Scope_Limit
     */
    public function noScope()
    {
        return (new Rx_Scope_Limit(false));
    }

    /**
     * Check if given action is allowed to be performed on given table
     *
     * @param string $type  Query type
     * @param string $table Table name, query is mean to be running on
     * @return boolean
     */
    protected function _isAllowed($type, $table)
    {
        $acl = $this->getAcl();
        if (!$acl instanceof Zend_Acl) {
            return (true);
        }
        $role = Rx_User::getRole();
        $allowed = $acl->isAllowed($role, $table, $type);
        if ($allowed) {
            return (true);
        }
        trigger_error(
            '"' . $type . '" query on table "' . $table . '" is not allowed for role "' . $role . '"',
            E_USER_WARNING
        );
        return (false);
    }

    /**
     * Safely quotes a value for an SQL statement.
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     * @return mixed                An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        return ($this->_db->quote($value, $type));
    }

    /**
     * Quote value that will be used for as binding for SQL statement
     *
     * @param mixed $value The value to quote.
     * @return mixed                An SQL-safe quoted value (or string of separated values).
     */
    protected function quoteBind($value)
    {
        if ($value === true) {
            return (1);
        } elseif ($value === false) {
            return (0);
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
            return ($value);
        } else {
            return ($value);
        }
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.
     *
     * @param string $text   The text with a placeholder.
     * @param mixed $value   The value to quote.
     * @param string $type   OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string               An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        return ($this->_db->quoteInto($text, $value, $type, $count));
    }

    /**
     * Quotes an identifier.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier.
     * @param boolean $auto                    If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string                               The quoted identifier.
     */
    public function quoteIdentifier($ident, $auto = false)
    {
        return ($this->_db->quoteIdentifier($ident, $auto));
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias                    An alias for the column.
     * @param boolean $auto                    If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string                               The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias, $auto = false)
    {
        return ($this->_db->quoteColumnAs($ident, $alias, $auto));
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias                    An alias for the table.
     * @param boolean $auto                    If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string                               The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
        return ($this->_db->quoteTableAs($ident, $alias, $auto));
    }

    /**
     * Convert an array, string, or Zend_Db_Expr object
     * into a string to put in a WHERE clause.
     * Code is taken from Zend_Db_Adapter_Abstract
     *
     * @param mixed $where
     * @return string
     */
    protected function _whereExpr($where)
    {
        if (empty($where)) {
            return $where;
        }
        if (!is_array($where)) {
            $where = array($where);
        }
        foreach ($where as $cond => &$term) {
            // is $cond an int? (i.e. Not a condition)
            if (is_int($cond)) {
                // $term is the full condition
                if ($term instanceof Zend_Db_Expr) {
                    $term = $term->__toString();
                }
            } else {
                // $cond is the condition with placeholder,
                // and $term is quoted into the condition
                $term = $this->_db->quoteInto($cond, $term);
            }
            $term = '(' . $term . ')';
        }

        $where = implode(' AND ', $where);
        return $where;
    }

    /**
     * Add scope limitation WHERE clause for given WHERE
     *
     * @param string $where               Current WHERE clause for the query
     * @param Rx_Scope_Limit|array $scope OPTIONAL Scope limitation for query
     * @return string
     * @throws Rx_Model_Exception
     */
    protected function _scopeWhere($where, $scope = null)
    {
        if ($scope === null) {
            return ($where);
        } elseif (is_array($scope)) {
            foreach ($scope as $sc) {
                $where = $this->_scopeWhere($where, $sc);
            }
            return ($where);
        }
        if (!$scope instanceof Rx_Scope_Limit) {
            throw new Rx_Model_Exception('Scope limitation information must be instance of Rx_Scope_Limit');
        }
        if ($scope->getId() !== false) {
            $cond = $scope->getColumn();
            $value = $scope->getValue();
            $null = $scope->isNullAllowed();
            $bind = false;
            if (is_array($value)) {
                // Avoid construction of invalid SQL query
                // in a case if empty array is passed as argument
                if (sizeof($value)) {
                    $cond .= ' in (?)';
                    $bind = true;
                } else {
                    $cond = 'true';
                }
            } elseif ($value === true) {
                $cond = 'true';
            } elseif ($value === false) {
                $cond = 'false';
            } elseif ($value !== null) {
                $cond .= '=?';
                $bind = true;
            } else {
                $cond .= ' is null';
            }
            if ($bind) {
                $cond = $this->quoteInto($cond, $value);
                if ($null) {
                    $cond = '(' . $cond . ' or ' . $scope->getColumn() . ' is null)';
                }
            }
            if ($where) {
                $cond = $where . ' and ' . $cond;
            }
            $where = $cond;
        }
        return ($where);
    }

    /**
     * Prepend given table name with prefix if necessary
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @return mixed
     */
    protected function _tableName($name)
    {
        $prefix = $this->_prefix;
        if ((!strlen($prefix)) || // There is nothing to do if we have no prefix
            ($name instanceof Zend_Db_Expr)
        ) // Don't deal with expressions, they should be prefixed by hand
        {
            return ($name);
        }
        $n = (is_array($name)) ? key($name) : $name;
        if ((strpos($n, '.') !== false) || // Don't deal with references to different databases or database schemas
            (strncasecmp($n, $prefix, strlen($prefix)) == 0)
        ) // Prefix is already applied
        {
            return ($name);
        }
        if (is_array($name)) {
            $name = array($prefix . $n => current($name));
        } else {
            $name = $prefix . $name;
        }
        return ($name);
    }

    /**
     * Get callback to function that is responsible for providing patches
     * for object's configuration options set
     *
     * @return callback|null
     */
    protected function _getConfigPatchProvider()
    {
        return (array($this->getProvider(), 'getConfigPatch'));
    }

}

;
