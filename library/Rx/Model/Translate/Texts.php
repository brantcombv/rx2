<?php

class Rx_Model_Translate_Texts extends Rx_Model_Collection
{
    /* Regular expression to parse text Id */
    const REGEXP_TEXT_ID = '/^([a-z0-9][a-z0-9\_]*)(?:\-([a-z0-9][a-z0-9\_]*))?(?:\-([a-z0-9][a-z0-9\_]*))$/';
    /**
     * Name of collection item class (it should be based on Rx_Struct_Model_Abstract)
     *
     * @var string $_itemClassName
     */
    protected $_itemClassName = 'Rx_Struct_Model_Translate_Text';
    /**
     * true to enable sharding for getting Ids of collection items from database
     *
     * @var boolean $_shardingEnabled
     */
    protected $_shardingEnabled = true;
    /**
     * Name of database table that is represented by collection object
     *
     * @var string $_dbTableName
     */
    protected $_dbTableName = 'texts';
    /**
     * true if collection item deleting is done by setting value to database table column,
     * false if item deletion is done by actually deleting row from database
     *
     * @var boolean $_haveDeletedColumn
     */
    protected $_haveDeletedColumn = true;
    /**
     * true to enable use of texts patches
     *
     * @var boolean $_textUsePatch
     */
    protected $_textUsePatch = false;
    /**
     * Name of database column that contains Id of "owner" of text patch
     *
     * @var string $_textPatchOwnerColumnName
     */
    protected $_textPatchOwnerColumnName = 'owner_id';
    /**
     * Scope limitations for texts patches table
     *
     * @var array $_textPatchScopes
     */
    protected $_textPatchScopes = array();

    /**
     * Get list of languages, translations are available for
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getLanguages($config = null)
    {
        return ($this->_getItemsInfo('get_languages', null, $config));
    }

    /**
     * Get detailed information about texts within given texts section
     *
     * @param string|array $sectionId               Id of text section or array(sectionId,subId) to get
     * @param string|array|true|null $languages     OPTIONAL Language Ids to get text section for. Possible values:
     *                                              - Single language Id as string
     *                                              - Multiple language Ids as array
     *                                              - true as "all languages"
     *                                              - null as "current language" (default)
     * @param array|Zend_Config|null $config        OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getSection($sectionId, $languages = null, $config = null)
    {
        $params = $this->_prepareTextFetchingParams($sectionId, $languages);
        $texts = $this->_getItems('get_section', $params, $config);
        return ($texts);
    }

    /**
     * Get list of Ids of texts in given texts section
     *
     * @param string|array $sectionId        Id of text section or array(sectionId,subId) to get Ids from
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getSectionIds($sectionId, $config = null)
    {
        $params = $this->_prepareTextFetchingParams($sectionId, null);
        $texts = $this->_getItemsInfo('get_section_ids', $params, $config);
        return ($texts);
    }

    /**
     * Get translations for text in given texts section
     *
     * @param string|array $sectionId        Id of text section or array(sectionId,subId) to get
     * @param string|null $language          OPTIONAL Language Id to get section texts for
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getTranslations($sectionId, $language = null, $config = null)
    {
        // Instead of implementing duplicated logic of fetching and formatting translated text -
        // it is easier and better to re-use current logic, perform required processing
        // and cache results
        $translations = array();
        try {
            $config = $this->getConfig($config);
            $class = get_class($this);
            if (!is_string($language)) {
                $language = null;
            }
            $params = $this->_prepareTextFetchingParams($sectionId, $language);
            $language = array_shift($params['languages']);
            unset($params['languages']);
            $params['language'] = $language;
            $params['rx_query_type'] = 'info';
            $cParams = $this->_getCacheParams('get_translations', $params, $config);
            $cacheId = $this->getCacheId($class, $cParams, false);
            if (!$this->getCache()->test($cacheId)) {
                $texts = $this->getSection($sectionId, $language, $config);
                $cfg = array('use_patch' => $config['use_patch']);
                /* @var $text Rx_Struct_Model_Translate_Text */
                foreach ($texts as $text) {
                    $t = $text->getPlural($language, null, $cfg);
                    if (sizeof($t) < 2) {
                        $t = array_shift($t);
                    }
                    $translations[$text->id] = $t;
                }
                $this->getCache()->save($translations, $cacheId, $this->getCacheTags($class));
            } else {
                $translations = $this->getCache()->load($cacheId);
            }
        } catch (Exception $e) {
            Rx_ErrorsHandler::getInstance()->exceptionsHandler($e);
            return (array());
        }
        return ($translations);
    }

    /**
     * Get patches for texts within given texts section
     *
     * @param string|array $sectionId               Id of text section or array(sectionId,subId) to get patches from
     * @param string|array|true|null $languages     OPTIONAL Language Ids to get text section patches for. Possible values:
     *                                              - Single language Id as string
     *                                              - Multiple language Ids as array
     *                                              - true as "all languages"
     *                                              - null as "current language" (default)
     * @param array|Zend_Config|null $config        OPTIONAL Configuration options to override default object's configuration
     * @return array
     */
    public function getPatches($sectionId, $languages = null, $config = null)
    {
        $params = $this->_prepareTextFetchingParams($sectionId, $languages);
        $config = $this->modifyConfig($config, 'use_patch', true);
        $texts = $this->_getItems('get_patches', $params, $config);
        return ($texts);
    }

    /**
     * Get translated text structure by given text message Id
     *
     * @param string|int $msgId              Message Id or database Id to get translated text structure for
     * @param string|array $sectionId        OPTIONAL Id of text section or array(sectionId,subId), given text belongs to
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return Rx_Struct_Model_Translate_Text   In a case if no text is available - empty structure will be returned
     */
    public function getTextStruct($msgId, $sectionId = null, $config = null)
    {
        // If database Id of text is given - run normal getItem() and return result
        if (is_numeric($msgId)) {
            $text = $this->getItem($msgId, $config);
            if (!$text instanceof Rx_Struct_Model_Translate_Text) {
                $params = $this->_prepareTextFetchingParams($sectionId, true);
                $text = new Rx_Struct_Model_Translate_Text(array(
                    'section' => $params['id'],
                    'subid'   => $params['subid'],
                ));
            }
            return ($text);
        }
        $params = $this->_prepareTextFetchingParams($sectionId, true);
        if ($params['id'] === null) {
            // No text section information is given, try to determine it by ourselves
            $info = $this->parseTextId($msgId);
            if ($info['id'] !== null) {
                // It is message Id
                $params['id'] = $info['section'];
                $params['subid'] = $info['subid'];
                $msgId = $info['name'];
            } else {
                // We get plain text message, it is required to have section Id
                // to be explicitly defined for it, so throw error and return empty structure
                trigger_error('Text section Id should be explicitly defined for plain text messages', E_USER_WARNING);
                $text = new Rx_Struct_Model_Translate_Text(array(
                    'section' => $params['id'],
                    'subid'   => $params['subid'],
                    'name'    => $msgId,
                ));
                return ($text);
            }
        }
        $params['name'] = $msgId;
        $texts = $this->_getItems('get_text', $params, $config);
        if (sizeof($texts) > 1) {
            trigger_error(
                'Ambiguous Id of translated text, multiple results are found (' . serialize($params) . ')',
                E_USER_WARNING
            );
        }
        $text = array_shift($texts);
        if (!$text instanceof Rx_Struct_Model_Translate_Text) {
            $text = new Rx_Struct_Model_Translate_Text(array(
                'section' => $params['id'],
                'subid'   => $params['subid'],
                'name'    => $params['name'],
            ));
        }
        return ($text);
    }

    /**
     * Prepare set of request parameters for fetching texts information from database
     *
     * @param string|array $sectionId              Id of text section or array(sectionId,subId) to get
     * @param string|array|boolean|null $languages Language Ids to get text section for
     * @return array
     */
    protected function _prepareTextFetchingParams($sectionId, $languages)
    {
        $subId = null;
        // If section Id contains subId as part of string Id - extract it
        if ((is_string($sectionId)) && (strpos($sectionId, '-') !== false)) {
            $sectionId = explode('-', $sectionId);
        }
        if (is_array($sectionId)) {
            $t = $sectionId;
            $sectionId = array_shift($t);
            $subId = array_shift($t);
        }
        // Prepare list of languages for SQL query
        if ($languages === null) {
            $languages = array(Rx_Language::getLanguage(true));
        } elseif (($languages !== true) && (!is_array($languages))) {
            $languages = array($languages);
        }
        if (is_array($languages)) {
            // Expand language Ids since we use complete Ids internally
            foreach ($languages as $k => $v) {
                $languages[$k] = Rx_Language::expand($v);
            }
        }
        $params = array(
            'id'        => $sectionId,
            'subid'     => $subId,
            'languages' => $languages,
        );
        return ($params);
    }

    /**
     * Check if given text is text Id or raw text
     *
     * @param string $text Text to check
     * @return boolean
     */
    public static function isTextId($text)
    {
        return (preg_match(self::REGEXP_TEXT_ID, $text));
    }

    /**
     * Parse given text Id into parts
     *
     * @param string $text Text Id to parse
     * @return array
     */
    public static function parseTextId($text)
    {
        $info = array(
            'id'      => null,
            'section' => null,
            'subid'   => null,
            'name'    => null,
        );
        if (preg_match(self::REGEXP_TEXT_ID, $text, $m)) {
            $info['id'] = $m[0];
            $info['section'] = $m[1];
            $info['subid'] = $m[2];
            $info['name'] = $m[3];
        }
        return ($info);
    }

    /**
     * Get model configuration
     * Mean to be used by corresponding Rx_Model_Entity class
     *
     * @return array
     */
    public function getModelConfig()
    {
        $config = parent::getModelConfig();
        $config->params = array(
            'use_patch'    => $this->_textUsePatch,
            'patch_column' => $this->_textPatchOwnerColumnName,
        );
        return ($config);
    }

    /**
     * Create SQL query for given type of information fetching from database
     *
     * @param string $type  Type of information request
     * @param mixed $params Additional request parameters
     * @param array $config Configuration options
     * @return Rx_Db_Select|array       Either single SQL query of array of Rx_Db_Select objects that will be joined by UNION
     */
    protected function _getItemsFetchingQuery($type, $params, $config)
    {
        $columns = $this->_getItemsFetchingQueryColumns($type, $params, $config);
        $query = null;
        if ($type != 'get_patches') {
            $query = $this->select()
                ->from(array('t' => 'texts'), $columns['texts'])
                ->joinInner(array('tt' => 'text_translations'), 'tt.text_id=t.id', $columns['translations'])
                ->noScope();
            $this->_addWhere($query, $config, 't');
            $this->_addWhere($query, $config, 'tt');
        }
        if ($config['use_patch']) {
            $qPatch = $this->select()
                ->from(array('t' => 'texts'), $columns['texts'])
                ->joinInner(array('tt' => 'text_patches'), 'tt.text_id=t.id', $columns['patches'])
                // Add scoping through direct "where" clause
                // because we need to pass additional argument to getScopes()
                ->where($this->_scopeWhere(null, $this->getScopes($config, 'tt', true)))
                ->noScope();
            $this->_addWhere($qPatch, $config, 't');
            $this->_addWhere($qPatch, $config, 'tt');
            $query = ($query instanceof Zend_Db_Select) ? array($query) : array();
            $query[] = $qPatch;
        }
        return ($query);
    }

    /**
     * Get list of columns to fetch from database with information fetching SQL query
     * for given type of information request
     *
     * @param string $type  Type of information request
     * @param mixed $params Additional request parameters
     * @param array $config Configuration options
     * @return array                    Array with list of columns
     */
    protected function _getItemsFetchingQueryColumns($type, $params, $config)
    {
        $columns = array();
        switch ($type) {
            default:
                $columns = array(
                    'texts'        => array('id', 'section_id', 'sub_id', 'is_raw', 'name'),
                    'translations' => array(
                        'is_patch' => new Zend_Db_Expr('false'),
                        't_id'     => 'id',
                        'owner_id' => new Zend_Db_Expr('null'),
                        'language',
                        'is_content',
                        't_value',
                        't_content',
                        'is_plural',
                        'plural_1',
                        'plural_2'
                    ),
                );
                if ($config['use_patch']) {
                    $columns['patches'] = array(
                        'is_patch'  => new Zend_Db_Expr('true'),
                        't_id'      => 'id',
                        'owner_id'  => $this->_textPatchOwnerColumnName,
                        'language',
                        'is_content',
                        't_value',
                        't_content',
                        'is_plural' => new Zend_Db_Expr('null'),
                        'plural_1'  => new Zend_Db_Expr('null'),
                        'plural_2'  => new Zend_Db_Expr('null'),
                    );
                }
                break;
        }
        return ($columns);
    }

    /**
     * Apply modifications to given SQL query that will be used for fetching collection items
     *
     * @param string $type        Type of information request
     * @param int|string $key     Key from query parts array associated with current query
     * @param Rx_Db_Select $query SQL query to apply modifications for
     * @param string|null $prefix Prefix for database table in FROM clause of given SQL query
     * @param mixed $params       Additional request parameters
     * @param array $config       Configuration options
     * @return void
     */
    protected function _modifyItemsFetchingQuery($type, $key, $query, $prefix, $params, $config)
    {
        switch ($type) {
            case 'get_section':
            case 'get_patches':
                $query->where($this->quoteIdentifier(array('t', 'section_id')) . '=?', $params['id']);
                if ($params['subid'] !== null) {
                    $query->where($this->quoteIdentifier(array('t', 'sub_id')) . '=?', $params['subid']);
                }
                $query->whereIn(array('tt', 'language'), $params['languages']);
                break;
            case 'get_text':
                $query->where($this->quoteIdentifier(array('t', 'section_id')) . '=?', $params['id']);
                if ($params['subid'] !== null) {
                    $query->where($this->quoteIdentifier(array('t', 'sub_id')) . '=?', $params['subid']);
                }
                if ($params['name'] !== null) {
                    $query->where($this->quoteIdentifier(array('t', 'name')) . '=?', $params['name']);
                }
                break;
            default:
                parent::_modifyItemsFetchingQuery($type, $key, $query, $prefix, $params, $config);
                break;
        }
    }

    /**
     * Run given SQL query and create collection items from fetched information
     *
     * @param string $type        Type of information request
     * @param Rx_Db_Select $query SQL query to run
     * @param mixed $params       Additional request parameters
     * @param array $config       Configuration options
     * @return array                    Array of rows fetched from database
     */
    protected function _runItemsFetchingQuery($type, $query, $params, $config)
    {
        $fMode = $this->_resolveFetchingMode($this->_getItemsFetchingMode($type, $params, $config));
        $rows = $this->getAdapter()->$fMode($query, $this->_getItemsFetchingQueryBindings($type, $params, $config));
        // Group fetched information by text
        $texts = array();
        foreach ($rows as $row) {
            if (!array_key_exists($row['id'], $texts)) {
                $texts[$row['id']] = array(
                    'id'         => $row['id'],
                    'section_id' => $row['section_id'],
                    'sub_id'     => $row['sub_id'],
                    'is_raw'     => $row['is_raw'],
                    'name'       => $row['name'],
                    'texts'      => array(),
                    'patches'    => array(),
                );
            }
            $tType = ($row['is_patch']) ? 'patches' : 'texts';
            $texts[$row['id']][$tType][$row['language']] = $row;
        }
        return ($texts);
    }

    /**
     * Get fetching mode for SQL query for collections items information fetching
     *
     * @param string $type  Type of information request
     * @param mixed $params Additional request parameters
     * @param array $config Configuration options
     * @return array                    Array of bindings
     */
    protected function _getItemsFetchingMode($type, $params, $config)
    {
        return ('all');
    }

    /**
     * Create item structure object by provided information from database (or other data source)
     *
     * @param array $data   Data to create item object from
     * @param array $config Configuration options
     * @return Rx_Struct_Model_Abstract|null|false  Created item on success, null if item can't be created for some reason, false in a case of error
     */
    protected function _createItem($data, $config)
    {
        $item = new Rx_Struct_Model_Translate_Text(array(
            'section' => $data['section_id'],
            'subid'   => $data['sub_id'],
            'name'    => $data['name'],
            'db_id'   => $data['id'],
            '_raw'    => $data['is_raw'],
        ));
        $item->setConfig('constructor', true);
        $languages = array_unique(array_merge(array_keys($data['texts']), array_keys($data['patches'])));
        foreach ($languages as $language) {
            $translation = new Rx_Struct_Model_Translate_Text_Translation();
            $translation->setConfig('constructor', true);
            if (array_key_exists($language, $data['texts'])) {
                $text = $data['texts'][$language];
                $translation->set(array(
                    'id'       => $text['t_id'],
                    'language' => $language,
                    'text'     => ($text['is_content']) ? $text['t_content'] : $text['t_value'],
                    '_blob'    => $text['is_content'],
                ), null, array('use_patch' => false));
                if ($text['is_plural']) {
                    $plural = array();
                    if ($text['plural_1'] !== null) {
                        $plural[] = $text['plural_1'];
                    }
                    if ($text['plural_2'] !== null) {
                        $plural[] = $text['plural_2'];
                    }
                    if (sizeof($plural)) {
                        $translation->set(array(
                            'plural'       => $text['is_plural'],
                            'plural_texts' => $plural,
                        ));
                    }
                }
            }
            if (($config['use_patch']) && (array_key_exists($language, $data['patches']))) {
                $text = $data['patches'][$language];
                $translation->set(array(
                    '_patch_id'    => $text['t_id'],
                    '_patch_owner' => $text['owner_id'],
                    'language'     => $language,
                    'text'         => ($text['is_content']) ? $text['t_content'] : $text['t_value'],
                    '_blob'        => $text['is_content'],
                ), null, array('use_patch' => true));
            }
            $translation->setConfig('constructor', false);
            $item->arraySet('translations', $language, $translation);
        }
        $item->setConfig('constructor', false);
        return ($item);
    }

    /**
     * Create SQL query for given type of information fetching from database
     *
     * @param string $type  Type of information request
     * @param mixed $params Additional request parameters
     * @param array $config Configuration options
     * @return Rx_Db_Select|array       Either single SQL query of array of Rx_Db_Select objects that will be joined by UNION
     */
    protected function _getItemsInfoFetchingQuery($type, $params, $config)
    {
        $columns = $this->_getItemsInfoFetchingQueryColumns($type, $params, $config);
        switch ($type) {
            case 'get_languages':
                $query = $this->select()
                    ->from(array('t' => 'text_translations'), $columns)
                    ->distinct(true)
                    ->noScope();
                $this->_addWhere($query, $config, 't');
                if ($config['use_patch']) {
                    $qPatch = $this->select()
                        ->from(array('tp' => 'text_patches'), $columns)
                        ->distinct(true)
                        // Add scoping through direct "where" clause
                        // because we need to pass additional argument to getScopes()
                        ->where($this->_scopeWhere(null, $this->getScopes($config, 'tp', true)))
                        ->noScope();
                    $this->_addWhere($qPatch, $config, 'tp');
                    $query = array($query, $qPatch);
                }
                break;
            default:
                $query = parent::_getItemsInfoFetchingQuery($type, $params, $config);
                break;
        }
        return ($query);
    }

    /**
     * Get list of columns to fetch from database with information fetching SQL query
     * for given type of information request
     *
     * @param string $type  Type of information request
     * @param mixed $params Additional request parameters
     * @param array $config Configuration options
     * @return array                    Array with list of columns
     */
    protected function _getItemsInfoFetchingQueryColumns($type, $params, $config)
    {
        $columns = array();
        switch ($type) {
            case 'get_languages':
                $columns = array('language');
                break;
            case 'get_section_ids':
                $columns = array('id', 'section_id', 'sub_id', 'is_raw', 'name');
                break;
            default:
                $columns = parent::_getItemsInfoFetchingQueryColumns($type, $params, $config);
                break;
        }
        return ($columns);
    }

    /**
     * Apply modifications to given SQL query that will be used for fetching collection items information
     *
     * @param string $type        Type of information request
     * @param int|string $key     Key from query parts array associated with current query
     * @param Rx_Db_Select $query SQL query to apply modifications for
     * @param string|null $prefix Prefix for database table in FROM clause of given SQL query
     * @param mixed $params       Additional request parameters
     * @param array $config       Configuration options
     * @return void
     */
    protected function _modifyItemsInfoFetchingQuery($type, $key, $query, $prefix, $params, $config)
    {
        switch ($type) {
            case 'get_section_ids':
                $query->where('section_id=?', $params['id']);
                if ($params['subid'] !== null) {
                    $query->where('sub_id=?', $params['subid']);
                }
                break;
            default:
                parent::_modifyItemsInfoFetchingQuery($type, $key, $query, $prefix, $params, $config);
                break;
        }
    }

    /**
     * Get fetching mode for SQL query for collections items information fetching
     *
     * @param string $type  Type of information request
     * @param mixed $params Additional request parameters
     * @param array $config Configuration options
     * @return array                    Array of bindings
     */
    protected function _getItemsInfoFetchingMode($type, $params, $config)
    {
        switch ($type) {
            case 'get_languages':
                return ('col');
                break;
            default:
                return (parent::_getItemsInfoFetchingMode($type, $params, $config));
                break;
        }
    }

    /**
     * Create collection item information set by given values
     *
     * @param string $type  Type of information request
     * @param int $id       Collection item Id to build information for
     * @param array $values List of values fetched from database to build information from
     * @param array $config Configuration options
     * @param array $path   OPTIONAL "Path" within results set to store item information to
     * @param boolean $skip OPTIONAL true to skip storing result in results set
     * @return mixed            Information that is required for given type of information request
     */
    protected function _createItemInfo($type, $id, $values, $config, &$path, &$skip)
    {
        static $text = null;

        switch ($type) {
            case 'get_section_ids':
                // Use text Id logic from structure object
                if (!$text) {
                    $text = new Rx_Struct_Model_Translate_Text();
                }
                $text->reset();
                $text->set(array(
                    'section' => $values['section_id'],
                    'subid'   => $values['sub_id'],
                    'name'    => $values['name'],
                    '_raw'    => $values['is_raw'],
                ), null, null, true);
                return ($text->id);
                break;
            default:
                return (parent::_createItemInfo($type, $id, $values, $config, $path, $skip));
                break;
        }
    }

    /**
     * Get scope information for model queries
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param string $prefix                 OPTIONAL Table prefix for database column names
     * @param boolean $patch                 OPTIONAL true to use scopes for texts patches table
     * @return array                            Array of Rx_Scope_Limit objects
     * @throws Rx_Model_Exception
     */
    public function getScopes($config = null, $prefix = null, $patch = false)
    {
        $_scopes = $this->_dbScopesMap;
        if (($patch) && ($this->_textUsePatch) && (sizeof($this->_textPatchScopes))) {
            $this->_dbScopesMap = $this->_textPatchScopes;
        }
        $scopes = parent::getScopes($config, $prefix);
        $this->_dbScopesMap = $_scopes;
        return ($scopes);
    }

    /**
     * Filter given list of cache parameters
     *
     * @param string $type  Type of information that will be cached
     * @param array $params List of collected cache parameters
     * @param string $qType Query type Id (from "rx_query_type" parameter)
     * @param array $config Configuration options
     * @return array
     */
    protected function _getCacheParamsFilter($type, $params, $qType, $config)
    {
        if ($config['use_patch']) {
            // Add custom scopes for text patches since they're handled separately
            $scopes = $this->getScopes($config, null, true);
            if (sizeof($scopes)) {
                if (!array_key_exists('scope', $params)) {
                    $params['scope'] = array();
                }
                /* @var $scope Rx_Scope_Limit */
                foreach ($scopes as $scope) {
                    $params['scope'][$scope->getId()] = $scope->getValue();
                }
            }
        }
        // There is no activity flag for texts
        unset($params['cfg']['only_active']);
        return (parent::_getCacheParamsFilter($type, $params, $qType, $config));
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'use_patch' => false, // true to use patched values for texts if available, false to skip patched values
        ));
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'use_patch':
                $value = (boolean)$value;
                if (!$this->_textUsePatch) {
                    $value = false;
                }
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

}
