<?php

class Rx_Model_Translate_Text extends Rx_Model_Entity
{
    /**
     * Name of corresponding Rx_Model_Collection based class (named Id to use for Rx_ModelManager)
     *
     * @var string $_collectionClassName
     */
    protected $_collectionClassName = 'translate_texts';

    /**
     * Get list of parts identifiers within entity structure
     *
     * @return array
     */
    protected function _getEntityPartsList()
    {
        return (array('text', 'translation', 'patch'));
    }


    /**
     * Get list of structure fields that forms given part Id of entity structure
     *
     * @param string $partId        Entity part Id
     * @param array $config         Object configuration options
     * @return string|array|true    Structure field name or array of structure field names
     *                              true if whole structure belongs to given entity part
     */
    protected function _getEntityPartFields($partId, $config)
    {
        switch ($partId) {
            case 'text':
                return (true);
                break;
            case 'translation':
            case 'patch':
                return ('translations');
                break;
        }
    }

    /**
     * Get model configuration for given entity part Id
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return Rx_Model_Config
     */
    protected function _getModelConfig($partId, $config)
    {
        $mCfg = null;
        switch ($partId) {
            case 'text':
                $mCfg = $this->getCollection()->getModelConfig();
                break;
            case 'translation':
                $mCfg = new Rx_Model_Config(array(
                    'item_class'     => 'Rx_Struct_Model_Translate_Text_Translation',
                    'db_table'       => 'text_translations',
                    'deleted_column' => 'is_deleted',
                ));
                break;
            case 'patch':
                // Take model configuration from collection
                // because we need to get additional parameters from it
                $mCfg = $this->getCollection()->getModelConfig();
                $mCfg->set(array(
                    'item_class' => 'Rx_Struct_Model_Translate_Text_Translation',
                    'db_table'   => 'text_patches',
                ));
                break;
        }
        return ($mCfg);
    }

    /**
     * Get model scope information for given entity part Id
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return array                    Array of Rx_Scope_Limit objects
     */
    protected function _getModelScopes($partId, $config)
    {
        $scopes = $this->getCollection()->getScopes(null, null, ($partId == 'patch'));
        return ($scopes);
    }

    /**
     * Get text section structure for current entity
     *
     * @return Rx_Struct_Model_Translate_Section
     */
    protected function _getSectionStruct()
    {
        // Retrieving section structure is moved to separate method because it is required
        // for validation of multiple entity parts but its local caching into static
        // variable is not always possible (e.g. when validating "translation" entity part without
        // prior validation of "text" part).
        static $sections = array();

        $id = $this->_entity->section;
        if (!array_key_exists($id, $sections)) {
            $sections[$id] = Rx_ModelManager::get('translate_sections')->getItem($id);
        }
        return ($sections[$id]);
    }

    /**
     * Check if given entity is valid to be stored in database
     *
     * @param Rx_Struct_Model_Abstract $entity      Entity to validate
     * @param string $partId                        Entity part Id
     * @param array $config                         Object configuration options
     * @return array                                Array of validation errors messages
     *                                              in a form of (field name => message)
     */
    protected function _isValidEntity($entity, $partId, $config)
    {
        $result = array();
        switch ($partId) {
            case 'text':
                if (!strlen($entity->name)) {
                    $result['name'] = 'No named Id is defined for text';
                }
                if (!$entity->section) {
                    $result['section'] = 'Text section Id is not defined';
                }
                $section = $this->_getSectionStruct();
                if ($section) {
                    if ((!$section->subids) && ($entity->subid)) {
                        $result['subid'] = 'Sub Id is used for text, but not allowed for text section';
                    }
                    if ((!$section->raw) && ($entity->_raw)) {
                        $result['_raw'] = 'Raw text Id is used, but not allowed for text section';
                    }
                } else {
                    $result['section'] = 'Unknown text section Id';
                }
                break;
            case 'translation':
                /* @var $entity Rx_Struct_Model_Translate_Text */
                if (!$entity->language) {
                    $result['language'] = 'Text language is not defined';
                }
                if (($entity->plural) && (sizeof($entity->plural_texts) > 2)) {
                    $result['plural_texts'] = 'Maximum 2 plural forms are supported for plural texts';
                }
                break;
            case 'patch':
                $patch = $entity->getPatch();
                $section = $this->_getSectionStruct();
                if ((sizeof($patch)) && ($section) && (!$section->patches)) {
                    $result['_patch_id'] = 'No patches are allowed for this text section';
                }
                break;
        }
        return ($result);
    }

    /**
     * Get SQL query to fetch list of database Ids of entities
     * that are already stored in database
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return Rx_Db_Select
     */
    protected function _getAvailableEntitiesIdsFetchingQuery($partId, $config)
    {
        $query = parent::_getAvailableEntitiesIdsFetchingQuery($partId, $config);
        switch ($partId) {
            case 'translation':
            case 'patch':
                $query->where('text_id=?', $this->_getEntityPart('text', $config)->getByMeaning('db_id'));
                break;
        }
        return ($query);
    }

    /**
     * Determine if given entity needs to be saved into database
     *
     * @param Rx_Struct_Model_Abstract $entity      Entity to store in database
     * @param string $partId                        Entity part Id
     * @param array $config                         Object configuration options
     * @return boolean                              true if entity should be saved,
     *                                              false if it should be skipped
     */
    protected function _needSaving($entity, $partId, $config)
    {
        switch ($partId) {
            case 'translation':
                // Translations without texts not need to be saved
                // Get text through get() with appropriate configuration options
                // to avoid false positives in a case when only patch is available
                $text = $entity->get('text', null, $this->_getEntityPartConfig($partId, $config));
                if ($text === null) {
                    return (false);
                }
                break;
            case 'patch':
                // Unavailable patches not need to be saved
                if (!sizeof($entity->getPatch())) {
                    return (false);
                }
                break;
        }
        return (true);
    }

    /**
     * Get list of database columns that are forms unique key in database table
     * that is represented by entity part with given Id
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return string|array|null
     */
    protected function _getEntityUniqueKeyColumns($partId, $config)
    {
        switch ($partId) {
            case 'text':
                return (array('section_id', 'sub_id', 'name'));
                break;
            case 'translation':
            case 'patch':
                return (array('text_id', 'language'));
                break;
            default:
                return (parent::_getEntityUniqueKeyColumns($partId, $config));
                break;
        }
    }

    /**
     * Get mapping between database columns and structure fields
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to get fields map for
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @return array                                Map in a form "db column name => structure field name"
     */
    protected function _getEntityFieldsMap($entity, $partId, $config)
    {
        switch ($partId) {
            case 'text':
                return (array(
                    'id'         => 'db_id',
                    'section_id' => 'section',
                    'sub_id'     => 'subid',
                    'is_raw'     => '_raw',
                    'name'       => 'name',
                ));
                break;
            case 'translation':
                return (array(
                    'id'         => 'id',
                    'text_id'    => null,
                    'language'   => 'language',
                    'is_content' => '_blob',
                    't_value'    => null,
                    't_content'  => null,
                    'is_plural'  => 'plural',
                    'plural_1'   => null,
                    'plural_2'   => null,
                ));
                break;
            case 'patch':
                static $pColumn = false;
                if ($pColumn === false) {
                    $pColumn = null;
                    // Add column name for linking patch owner column,
                    // it should be taken from model config of corresponding collection model
                    $mCfg = $this->getModelConfig();
                    $params = $mCfg->params;
                    if ((is_array($params)) &&
                        (array_key_exists('use_patch', $params)) &&
                        ($params['use_patch']) &&
                        (array_key_exists('patch_column', $params)) &&
                        ($params['patch_column'])
                    ) {
                        $pColumn = $params['patch_column'];
                    }
                }
                $map = array(
                    'id'         => '_patch_id',
                    'text_id'    => null,
                    'language'   => 'language',
                    'is_content' => '_blob',
                    't_value'    => null,
                    't_content'  => null,
                );
                if ($pColumn) {
                    $map[$pColumn] = '_patch_owner';
                }
                return ($map);
                break;
        }
    }

    /**
     * Get value of given structure field that will be stored in database
     *
     * @param Rx_Struct_Model_Abstract $entity Entity that is being stored in database
     * @param string $partId                   Entity part Id
     * @param string $name                     Structure field name to process
     * @param string $column                   Database column name, field will be stored to
     * @param array $config                    Object configuration options
     * @param boolean $skip                    OPTIONAL true to skip storing field in database
     * @return mixed
     */
    protected function _getFieldDbValue($entity, $partId, $name, $column, $config, &$skip)
    {
        switch ($column) {
            case 't_value':
                $value = ($entity->_blob) ? null : $entity->text;
                break;
            case 't_content':
                $value = ($entity->_blob) ? $entity->text : null;
                break;
            case 'plural_1':
                $plural = array_values($entity->plural_texts);
                $value = array_shift($plural);
                break;
            case 'plural_2':
                $plural = array_values($entity->plural_texts);
                array_shift($plural);
                $value = array_shift($plural);
                break;
            case 'text_id':
                $value = $this->_getEntityPart('text', $config)->getByMeaning('db_id');
                break;
            default:
                $value = parent::_getFieldDbValue($entity, $partId, $name, $column, $config, $skip);
                break;
        }
        return ($value);
    }

    /**
     * Reload model entity from corresponding collection model
     *
     * @param array|Zend_Config|null $modelConfig OPTIONAL Configuration options to pass to collection model
     * @return boolean                              true if entity was reloaded, false in a case of error
     */
    public function reload($modelConfig = null)
    {
        return (parent::reload(array(
            'only_active' => false,
            'get_details' => true,
            'use_patch'   => true,
        )));
    }

}
