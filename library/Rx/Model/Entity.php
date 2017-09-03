<?php

abstract class Rx_Model_Entity extends Rx_Model_Abstract implements Countable, Iterator
{
    /* Constants for hook points of entity saving process */
    const HOOK_ENTITY_SAVE_STARTED = 'entity_save_started';
    const HOOK_PART_SAVE_STARTED = 'part_save_started';
    const HOOK_PART_SAVE_COMPLETED = 'part_save_completed';
    const HOOK_ENTITY_SAVE_COMPLETED = 'entity_save_completed';
    const HOOK_ENTITY_SAVE_FAILED = 'entity_save_failed';

    /* Constants for hook points of entity deleting process */
    const HOOK_ENTITY_DELETE_STARTED = 'entity_delete_started';
    const HOOK_PART_DELETE_STARTED = 'part_delete_started';
    const HOOK_PART_DELETE_COMPLETED = 'part_delete_completed';
    const HOOK_ENTITY_DELETE_COMPLETED = 'entity_delete_completed';
    const HOOK_ENTITY_DELETE_FAILED = 'entity_delete_failed';

    /**
     * Entity contents entity
     *
     * @var Rx_Struct_Model_Abstract $_entity
     */
    protected $_entity = null;
    /**
     * Name of corresponding Rx_Model_Collection based class (named Id to use for Rx_ModelManager)
     *
     * @var string $_collectionClassName
     */
    protected $_collectionClassName = null;
    /**
     * Cache of collection models for entity parts
     *
     * @var array $_collections
     */
    protected $_collections = array();
    /**
     * Cache of model configurations for entity parts
     *
     * @var array $_modelConfigs
     */
    protected $_modelConfigs = array();
    /**
     * Cache of model scopes for entity parts
     *
     * @var array $_modelScopes
     */
    protected $_modelScopes = array();

    /**
     * Class constructor
     *
     * @param Rx_Struct_Model_Abstract $entity OPTIONAL Entity to work with
     * @param array|Zend_Config $config        OPTIONAL Configuration options for class
     * @throws Rx_Model_Exception
     */
    public function __construct($entity = null, $config = null)
    {
        parent::__construct($config);
        $config = $this->getConfig();
        if ($entity === null) {
            $entity = $this->_createEntity($config);
        }
        $class = $this->getModelConfig($config)->item_class;
        if (!$entity instanceof $class) {
            throw new Rx_Model_Exception('Entity must be instance of ' . $class);
        }
        $this->_initEntity($entity, $config);
        $this->_entity = $entity;
    }

    /**
     * Indicates if model class can work properly with single instance.
     * Used by Rx_ModelManager
     *
     * @return boolean  true if class instance can be stored in registry and re-used
     *                  false if new instance of class must be created on every request
     */
    public static function isSingleton()
    {
        return (false);
    }

    /**
     * Create and return empty entity structure object
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @return Rx_Struct_Model_Abstract
     */
    protected function _createEntity($config)
    {
        $config = $this->getConfig($config);
        $class = $this->getModelConfig($config)->item_class;
        $entity = new $class();
        return ($entity);
    }

    /**
     * Initialize newly created or assigned entity
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to initialize
     * @param array $config                    Object configuration options
     * @return void
     */
    protected function _initEntity($entity, $config)
    {
        // This method is mean to be overridden in a case if newly created
        // or assigned entity object needs to be initialized before further use
    }

    /**
     * Get list of parts identifiers within entity structure
     *
     * @return array
     */
    protected function _getEntityPartsList()
    {
        // This method should be overridden in a case of complex entity structure
        // that have multiple parts that should be saved separately,
        // e.g. in a different database tables
        // IMPORTANT:
        // - Part Id that represents main entity structure should be first in list of parts
        // - In a case if complex entity have "patched" parts (e.g. if entity
        //   is inherited from Rx_Struct_Model_Patched) - it is better to give names
        //   for these parts started with "patch" prefix, @see _isPatchEntityPart()
        return (array('entity'));
    }

    /**
     * Normalize given entity part Id
     *
     * @param string $partId Entity part Id
     * @return string
     */
    protected function _normalizeEntityPartId($partId)
    {
        $parts = $this->_getEntityPartsList();
        if ($partId === null) {
            reset($parts);
            $partId = current($parts);
        }
        if (!in_array($partId, $parts)) {
            $partId = current($parts);
        }
        return ($partId);
    }

    /**
     * Get Id of default entity part
     *
     * @return string
     */
    protected function _getDefaultEntityPartId()
    {
        $parts = $this->_getEntityPartsList();
        $partId = array_shift($parts);
        return ($partId);
    }

    /**
     * Determine if given entity part Id represents "patched" entity
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return boolean
     */
    protected function _isPatchEntityPart($partId, $config)
    {
        $patch = (strtolower(substr($partId, 0, 5)) == 'patch');
        return ($patch);
    }

    /**
     * Get part of entity structure by given part Id
     *
     * @param string $partId                    Entity part Id
     * @param array $config                     Object configuration options
     * @throws Rx_Model_Exception
     * @return Rx_Struct_Model_Abstract|array   Either structure part entity or array of Rx_Struct_Model_Abstract
     *                                          in a case of multiple entity instances within single part
     */
    protected function _getEntityPart($partId, $config)
    {
        $fields = $this->_getEntityPartFields($partId, $config);
        if (is_string($fields)) {
            if (!isset($this->_entity->$fields)) {
                throw new Rx_Model_Exception('Unavailable field name "' . $fields . '" is given for entity part "' . $partId . '"');
            }
            return ($this->_entity->$fields);
        } else {
            return ($this->_entity);
        }
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
        // This method should be overridden to provide entity parts
        // in a case of complex multi-part entities
        return (true);
    }

    /**
     * Get configuration options for entity part with given Id
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return array                    Configuration options for entity
     */
    protected function _getEntityPartConfig($partId, $config)
    {
        $cfg = array(
            'force_read'  => true,
            // We should be able to access write-only properties in _getFieldDbValue()
            'force_write' => true,
            // We should be able to access read-only properties in _insertEntity() and _updateEntity()
            'use_patch'   => false,
            // We should not get patch values when working with original structure
        );
        if ($this->_isPatchEntityPart($partId, $config)) {
            // For patched entity parts we should take values from patch,
            // not from original structure
            $cfg['use_patch'] = true;
            // We should also disable fallback because otherwise it will not be possible
            // to take "non-existent" patch values since they will be substituted
            // by original structure values
            $cfg['no_fallback'] = true;
        }
        return ($cfg);
    }

    /**
     * Get meaning name of structure field that stores database Id in entity with given part Id
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return string
     */
    protected function _getEntityDbIdMeaningName($partId, $config)
    {
        $name = ($this->_isPatchEntityPart($partId, $config)) ? 'patch_db_id' : 'db_id';
        return ($name);
    }

    /**
     * Get database Id from given entity
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to get database Id from
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @return int|string|null
     */
    protected function _getEntityDbId($entity, $partId, $config)
    {
        $name = $this->_getEntityDbIdMeaningName($partId, $config);
        $cfg = $this->_getEntityPartConfig($partId, $config);
        $id = $entity->getByMeaning($name, null, $cfg);
        return ($id);
    }

    /**
     * Clone given entity part and prepare it for further processing
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to clone
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @return Rx_Struct_Model_Abstract
     */
    protected function _cloneEntityPart($entity, $partId, $config)
    {
        // Entity should be cloned before processing
        // because we need to patch its configuration options set
        /* @var $_entity Rx_Struct_Model_Abstract */
        $_entity = clone($entity);
        // Disable errors reporting because of possible warning about invalid configuration option.
        // It is caused because we should set "use_patch" option but there is no easy way to determine
        // if this option is available for some entity part or not
        @$_entity->setConfig($this->_getEntityPartConfig($partId, $config));
        return ($_entity);
    }

    /**
     * Get corresponding collection model object for given part Id
     *
     * @param string $partId OPTIONAL Entity part Id to get collection for (default part Id will be used if missed)
     * @return Rx_Model_Collection|null
     * @throws Rx_Model_Exception
     */
    public function getCollection($partId = null)
    {
        $partId = $this->_normalizeEntityPartId($partId);
        if (!array_key_exists($partId, $this->_collections)) {
            $collection = $this->_getCollection($partId);
            if (($collection !== null) && (!$collection instanceof Rx_Model_Collection)) {
                throw new Rx_Model_Exception('Failed to get collection model for entity part ' . $partId);
            }
            $this->_collections[$partId] = $collection;
        }
        return ($this->_collections[$partId]);
    }

    /**
     * Get model collection object for given entity part Id
     *
     * @param string $partId Entity part Id
     * @return Rx_Model_Collection|null
     */
    protected function _getCollection($partId)
    {
        // This method should be overridden if different models needs to be provided
        // depending on "part_id" configuration option value
        if ($partId != $this->_getDefaultEntityPartId()) // We can't get collection for non-default parts of entity
        {
            return (null);
        }
        if (!$this->_collectionClassName) {
            return (null);
        }
        $collection = Rx_ModelManager::get($this->_collectionClassName);
        return ($collection);
    }

    /**
     * Get model configuration
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                 OPTIONAL Entity part Id to get configuration for (default part Id will be used if missed)
     * @return Rx_Model_Config
     * @throws Rx_Model_Exception
     */
    public function getModelConfig($config = null, $partId = null)
    {
        $partId = $this->_normalizeEntityPartId($partId);
        if (!array_key_exists($partId, $this->_modelConfigs)) {
            $config = $this->getConfig($config);
            $mCfg = $this->_getModelConfig($partId, $config);
            if (!$mCfg instanceof Rx_Model_Config) {
                throw new Rx_Model_Exception('Model configuration for entity part "' . $partId . '" should be instance of Rx_Model_Config');
            }
            $this->_modelConfigs[$partId] = $mCfg;
        }
        return ($this->_modelConfigs[$partId]);
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
        // This method should be overridden if different models needs to be provided
        // depending on "part_id" configuration option value
        return ($this->getCollection($partId)->getModelConfig());
    }

    /**
     * Get model scope information
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                 OPTIONAL Entity part Id to get scopes for (default part Id will be used if missed)
     * @return array                            Array of Rx_Scope_Limit objects
     */
    public function getModelScopes($config = null, $partId = null)
    {
        $partId = $this->_normalizeEntityPartId($partId);
        if (!array_key_exists($partId, $this->_modelScopes)) {
            $config = $this->getConfig($config);
            $scope = $this->_getModelScopes($partId, $config);
            if ($scope instanceof Rx_Scope_Limit) {
                $scope = array($scope);
            }
            if ((!is_array($scope)) || (!sizeof($scope))) {
                $scope = array($this->noScope());
            }
            $this->_modelScopes[$partId] = $scope;
        }
        return ($this->_modelScopes[$partId]);
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
        // This method should be overridden if different models needs to be provided
        // depending on "part_id" configuration option value
        return ($this->getCollection($partId)->getScopes());
    }

    /**
     * Check if entity structure is valid to be stored into database
     * This method differs from Rx_Model_Collection::isValid() because its purpose
     * is to validate not just Id, but whole structure to determine if it will be possible
     * to store it in database
     *
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                    OPTIONAL Entity part Id to validate (whole entity will be validated by default)
     * @return boolean|array                    true if entity structure is valid, FALE if not,
     *                                          array in a form of (field name => message) to pass error messages
     *                                          about problems in certain structure fields to application
     * @throws Rx_Model_Exception
     */
    public function isValid($config = null, $partId = null)
    {
        $errors = array();
        $nErrors = 0;
        $config = $this->getConfig($config);
        $_partId = $partId;
        $parts = $this->_getEntityPartsList();
        foreach ($parts as $partId) {
            if (($_partId !== null) && ($partId != $_partId)) {
                continue;
            }
            if (!array_key_exists($partId, $errors)) {
                $errors[$partId] = array();
            }
            // If entity part is not initialized - there is nothing to do
            if (!$this->_entity->isInitialized($this->_getEntityPartFields($partId, $config))) {
                continue;
            }
            $entities = $this->_getEntityPart($partId, $config);
            $list = false;
            if ($entities instanceof Rx_Struct_Model_Abstract) {
                $entities = array($entities);
            } elseif (is_array($entities)) {
                $errors[$partId] = array();
                $list = true;
            }
            foreach ($entities as $eId => $entity) {
                if (!$entity instanceof Rx_Struct_Model_Abstract) {
                    throw new Rx_Model_Exception('Entity part should be instance of Rx_Struct_Model_Abstract');
                }
                $result = $this->_validateEntityPart($entity, $partId, $config);
                $nErrors += sizeof($result);
                if ($list) {
                    $errors[$partId][$eId] = $result;
                } else {
                    $errors[$partId] = $result;
                }
            }
        }
        if (sizeof($parts) == 1) // Entity consists of single part, prepare plain list of errors
        {
            $errors = array_shift($errors);
        } elseif (($_partId) && (array_key_exists(
                $_partId,
                $errors
            ))
        ) // Errors report should be given just for specified entity part
        {
            $errors = $errors[$_partId];
        }
        if (($config['get_errors']) && ($config['plain_errors_list'])) // Errors list needs to be forced to be plain
        {
            $t = array();
            $this->_getPlainList($errors, $t);
            $errors = $t;
        } elseif (!$config['get_errors']) {
            $errors = ($nErrors == 0);
        } // Get just error status flag
        return ($errors);
    }

    /**
     * Create plain list of items from given data
     *
     * @param array $data   Data to convert
     * @param array $result Reference to result
     * @return void
     */
    protected function _getPlainList($data, &$result)
    {
        if (!is_array($result)) {
            $result = array();
        }
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $this->_getPlainList($v, $result);
                } else {
                    $result[] = $v;
                }
            }
        } else {
            $result[] = $data;
        }
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
    protected function _validateEntityPart($entity, $partId, $config)
    {
        // This function is actually a wrapper for _isValidEntity() which is mean
        // to be overridden in child classes but which requires some additional
        // common functionality
        $entity = $this->_cloneEntityPart($entity, $partId, $config);
        // If given entity part have its own entity class and this class
        // is not the same as current class - we should validate given entity part
        // through external validator
        if (($entity->haveEntity()) &&
            (get_class($entity->getEntity()) != get_class($this))
        ) {
            $result = $entity->getEntity()->isValid(array('get_errors' => true));
        } else {
            $result = $this->_isValidEntity($entity, $partId, $config);
        }
        if (!is_array($result)) {
            $result = ($result) ? array() : array(false);
        }
        return ($result);
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
        // Method is mean to be overridden in a case of special validation
        // should be applied to structure before it will be stored in database
        // It is also allowed to modify structure contents in this method
        return (array());
    }

    /**
     * Save entity contents in database
     *
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                    OPTIONAL Entity part Id to save (whole entity will be saved by default)
     * @return boolean|array                    true if entity was successfully saved, false in a case of error,
     *                                          array if "get_report" configuration option is enabled
     * @throws Rx_Model_Exception
     */
    public function save($config = null, $partId = null)
    {
        $report = array(
            'success' => true,
            'parts'   => array(),
        );
        $config = $this->getConfig($config);
        $_partId = $partId;
        $parts = $this->_getEntityPartsList();
        try {
            // Entity should be saved within transaction because it may consist of
            // multiple joined parts which should be saved as a single structure
            // to prevent data corruption in database
            $this->getAdapter()->beginTransaction();
            $processing = true;
            $hpr = $this->_hookPointsHandler(self::HOOK_ENTITY_SAVE_STARTED, $this->_entity, null, $config, $report);
            if ($hpr === false) {
                $processing = false;
            } // Disable parts processing as requested by hook points handler
            foreach ($parts as $partId) {
                if (!$processing) {
                    break;
                }
                if (($_partId !== null) && ($partId != $_partId)) {
                    continue;
                }
                if (!array_key_exists($partId, $report['parts'])) {
                    $report['parts'][$partId] = array(
                        'success' => true,
                    );
                }
                // If entity part is not initialized - there is nothing to do
                if (!$this->_entity->isInitialized($this->_getEntityPartFields($partId, $config))) {
                    continue;
                }
                $entity = $this->_getEntityPart($partId, $config);
                $hpr = $this->_hookPointsHandler(self::HOOK_PART_SAVE_STARTED, $entity, $partId, $config, $report);
                if ($hpr === false) {
                    break;
                }
                if ($entity instanceof Rx_Struct_Model_Abstract) {
                    $r = $this->_saveEntity($entity, $partId, $config, $report['parts'][$partId]);
                } elseif (is_array($entity)) {
                    $r = $this->_syncEntitiesList($entity, $partId, $config, $report['parts'][$partId]);
                } else {
                    throw new Rx_Model_Exception('Entity part should be either instance of Rx_Struct_Model_Abstract or array');
                }
                $report['success'] = (($report['success']) && ($report['parts'][$partId]['success']) && ($r));
                $hpr = $this->_hookPointsHandler(self::HOOK_PART_SAVE_COMPLETED, $entity, $partId, $config, $report);
                // If we had failed to save some part of entity structure
                // or further processing is disabled by hook points handler -
                // then there is no reason to attempt to save remaining parts of it
                // since transaction will be rolled back anyway
                if (($hpr === false) || (!$report['success'])) {
                    break;
                }
            }
            if ($report['success']) {
                $this->_hookPointsHandler(self::HOOK_ENTITY_SAVE_COMPLETED, $this->_entity, null, $config, $report);
                $this->getAdapter()->commit();
                if ($config['reload']) {
                    $this->reload();
                }
                $this->_entity->clearModified(true); // No modifications are left after successful saving
            } else {
                $this->_hookPointsHandler(self::HOOK_ENTITY_SAVE_FAILED, $this->_entity, null, $config, $report);
                $this->getAdapter()->rollBack();
            }
        } catch (Exception $e) {
            Rx_ErrorsHandler::getInstance()->exceptionsHandler($e);
            $report['success'] = false;
            $report['exception'] = $e;
            $this->_hookPointsHandler(self::HOOK_ENTITY_SAVE_FAILED, $this->_entity, null, $config, $report);
            $this->getAdapter()->rollBack();
        }
        return (($config['get_report']) ? $report : $report['success']);
    }

    /**
     * Insert entity contents in database
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                 OPTIONAL Entity part Id to insert (whole entity will be inserted by default)
     * @return boolean                          true if entity was successfully inserted, false in a case of error
     * @throws Rx_Model_Exception
     */
    public function insertEntity($config = null, $partId = null)
    {
        $config = $this->modifyConfig($config, 'insert', true);
        return ($this->save($config, $partId));
    }

    /**
     * Update entity contents in database
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                 OPTIONAL Entity part Id to updated (whole entity will be updated by default)
     * @return boolean                          true if entity was successfully updated, false in a case of error
     * @throws Rx_Model_Exception
     */
    public function updateEntity($config = null, $partId = null)
    {
        $config = $this->modifyConfig($config, 'insert', false);
        return ($this->save($config, $partId));
    }

    /**
     * Delete entity contents from database
     *
     * @param array|Zend_Config|null $config    OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                    OPTIONAL Entity part Id to delete (whole entity will be deleted by default)
     * @return boolean|array                    true if entity was successfully deleted, false in a case of error,
     *                                          array if "get_report" configuration option is enabled
     * @throws Rx_Model_Exception
     */
    public function deleteEntity($config = null, $partId = null)
    {
        $report = array(
            'success' => true,
            'parts'   => array(),
        );
        $config = $this->getConfig($config);
        $_partId = $partId;
        $parts = $this->_getEntityPartsList();
        try {
            // Entity should be deleted within transaction because it may consist of
            // multiple joined parts which should be deleted as a single structure
            // to prevent data corruption in database
            $this->getAdapter()->beginTransaction();
            $processing = true;
            $hpr = $this->_hookPointsHandler(self::HOOK_ENTITY_DELETE_STARTED, $this->_entity, null, $config, $report);
            if ($hpr === false) {
                $processing = false;
            } // Disable parts processing as requested by hook points handler
            // Entity parts list have main part Id at the beginning, but when we delete
            // information from database - we should first remove information from child tables
            // and only then - from main table (to avoid possible constraint violations)
            // Hence we should process entity parts list in reversed order
            $parts = array_reverse($parts);
            foreach ($parts as $partId) {
                if (!$processing) {
                    break;
                }
                if (($_partId !== null) && ($partId != $_partId)) {
                    continue;
                }
                if (!array_key_exists($partId, $report['parts'])) {
                    $report['parts'][$partId] = array(
                        'success' => true,
                    );
                }
                // If entity part is not initialized - there is nothing to do
                if (!$this->_entity->isInitialized($this->_getEntityPartFields($partId, $config))) {
                    continue;
                }
                $entity = $this->_getEntityPart($partId, $config);
                $hpr = $this->_hookPointsHandler(self::HOOK_PART_DELETE_STARTED, $entity, $partId, $config, $report);
                if ($hpr === false) {
                    break;
                }
                if (!is_array($entity)) {
                    $entity = array($entity);
                }
                $ids = array();
                foreach ($entity as $_ent) {
                    if (!$_ent instanceof Rx_Struct_Model_Abstract) {
                        throw new Rx_Model_Exception('Entity part entry should be instance of Rx_Struct_Model_Abstract');
                    }
                    $eId = $this->_getEntityDbId($_ent, $partId, $config);
                    if ($eId !== null) {
                        $ids[] = $eId;
                    }
                }
                $r = $this->_deleteEntity($ids, $partId, $config, $report['parts'][$partId]);
                $report['success'] = (($report['success']) && ($report['parts'][$partId]['success']) && ($r));
                $hpr = $this->_hookPointsHandler(self::HOOK_PART_DELETE_COMPLETED, $entity, $partId, $config, $report);
                // If we had failed to delete some part of entity structure
                // or further processing is disabled by hook points handler -
                // then there is no reason to attempt to delete remaining parts of it
                // since transaction will be rolled back anyway
                if (($hpr === false) || (!$report['success'])) {
                    break;
                }
            }
            if ($report['success']) {
                $this->_hookPointsHandler(self::HOOK_ENTITY_DELETE_COMPLETED, $this->_entity, null, $config, $report);
                $this->getAdapter()->commit();
            } else {
                $this->_hookPointsHandler(self::HOOK_ENTITY_DELETE_FAILED, $this->_entity, null, $config, $report);
                $this->getAdapter()->rollBack();
            }
        } catch (Exception $e) {
            Rx_ErrorsHandler::getInstance()->exceptionsHandler($e);
            $report['success'] = false;
            $report['exception'] = $e;
            $this->_hookPointsHandler(self::HOOK_ENTITY_DELETE_FAILED, $this->_entity, null, $config, $report);
            $this->getAdapter()->rollBack();
        }
        return (($config['get_report']) ? $report : $report['success']);
    }

    /**
     * Save contents of given entity in database
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to save
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @param array $report                    Reference to entity saving report
     * @return boolean                          true if entity was successfully saved, false in a case of error
     */
    protected function _saveEntity($entity, $partId, $config, &$report)
    {
        $mustInsert = $config['insert'];
        if ($config['insert'] === null) {
            $config = $this->modifyConfig($config, 'insert', $this->_needInsert($entity, $partId, $config));
        }
        // When we need to update information in database and there is no modifications
        // are available in structure - there is nothing to do
        if ((!$entity->isModified(
                $this->_getEntityPartFields($partId, $config),
                $this->_getEntityPartConfig($partId, $config)
            )) &&
            (!$config['insert'])
        ) {
            return (true);
        }
        // If entity structure is not valid for storing in database - reject saving
        $errors = $this->_validateEntityPart($entity, $partId, $config);
        if (sizeof($errors)) {
            $report['success'] = false;
            $report['error'] = 'Entity structure part "' . $partId . '" is not valid to be stored in database';
            $report['validation'] = $errors;
            trigger_error($report['error'], E_USER_WARNING);
            return (false);
        }
        if ($config['insert']) {
            // For INSERT queries we should also check if new insert will not fail
            // because of duplicated value of non-primary unique keys that are defined
            // on target database table. If it is - we should UPDATE target row instead
            // of inserting information into it
            $uKey = $this->_getEntityUniqueKeyId($entity, $partId, $config);
            if ($uKey) {
                if ($mustInsert) {
                    $report['success'] = false;
                    $report['error'] = 'Insertion of entity part "' . $partId . '" is not allowed because of duplicated value of unique key';
                    trigger_error($report['error'], E_USER_WARNING);
                    return (false);
                }
                $name = $this->_getEntityDbIdMeaningName($partId, $config);
                if ($name) {
                    $entity->setByMeaning($name, $uKey, $this->_getEntityPartConfig($partId, $config));
                }
                $config = $this->modifyConfig($config, 'insert', false);
            } elseif (!$this->_insertEntity($entity, $partId, $config, $report)) {
                trigger_error('Failed to insert entity contents into database', E_USER_ERROR);
                return (false);
            }
        }
        if (!$config['insert']) // Not "elseif" because of unique key check that may change this configuration option
        {
            if (!$this->_updateEntity($entity, $partId, $config, $report)) {
                trigger_error('Failed to update entity contents into database', E_USER_ERROR);
                return (false);
            }
        }
        return (true);
    }

    /**
     * Sync contents of given list of entities with database
     *
     * @param array $entities List of Rx_Struct_Model_Abstract objects to sync with database
     * @param string $partId  Entity part Id, given entities belongs to
     * @param array $config   Object configuration options
     * @param array $report   Reference to entity saving report
     * @return boolean              true if entities was synced successfully, false in a case of error
     * @throws Rx_Model_Exception
     */
    protected function _syncEntitiesList($entities, $partId, $config, &$report)
    {
        $report['operation'] = 'sync';
        $report['items'] = array();
        // We need to have collection model and entity model class defined
        // for current entity part Id to be able to sync entities list
        // through external collection model
        if (($this->getCollection($partId)) &&
            ($this->getModelConfig($config, $partId)->entity_class)
        ) {
            return ($this->_syncEntitiesListCollection($entities, $partId, $config, $report));
        } else {
            return ($this->_syncEntitiesListInternal($entities, $partId, $config, $report));
        }
    }

    /**
     * Sync contents of given list of entities with database using external collection model
     *
     * @param array $entities List of Rx_Struct_Model_Abstract objects to sync with database
     * @param string $partId  Entity part Id, given entities belongs to
     * @param array $config   Object configuration options
     * @param array $report   Reference to entity saving report
     * @return boolean              true if entities was synced successfully, false in a case of error
     * @throws Rx_Model_Exception
     */
    protected function _syncEntitiesListCollection($entities, $partId, $config, &$report)
    {
        $collection = $this->getCollection($partId);
        $ids = $collection->getEntitySyncIds($this, $this->_getEntityPartSyncConfig($partId, $config));
        /* @var $entity Rx_Struct_Model_Abstract */
        foreach ($entities as $key => $entity) {
            $r = $entity->save($this->modifyConfig($config, 'get_report', true));
            $report['items'][$key] = $r;
            $report['success'] = (($report['success']) && ($report['items'][$key]['success']));
            if (!$report['success']) {
                return (false);
            }
            $id = $this->_getEntityDbId($entity, $partId, $config);
            if ($id) {
                $t = array_search($id, $ids);
                if ($t !== false) {
                    unset($ids[$t]);
                }
            }
        }
        // If some Ids are still left in Ids list - we need to remove corresponding items from collection
        if (sizeof($ids)) {
            $r = $collection->deleteItems($ids, array('permanent_delete' => $config['permanent_delete']));
            $report['deleted'] = array(
                'success' => (boolean)$r,
                'ids'     => $ids,
            );
            $report['success'] = (($report['success']) && ($report['deleted']['success']));
        }
        return ($report['success']);
    }

    /**
     * Get configuration options to use when syncing list of entities using external collection
     *
     * @param string $partId Entity part Id
     * @param array $config  Object configuration options
     * @return array                    Configuration options for external collection
     */
    protected function _getEntityPartSyncConfig($partId, $config)
    {
        return (array('only_active' => false));
    }

    /**
     * Sync contents of given list of entities with database using internal saving mechanisms
     *
     * @param array $entities List of Rx_Struct_Model_Abstract objects to sync with database
     * @param string $partId  Entity part Id, given entities belongs to
     * @param array $config   Object configuration options
     * @param array $report   Reference to entity saving report
     * @return boolean              true if entities was synced successfully, false in a case of error
     * @throws Rx_Model_Exception
     */
    protected function _syncEntitiesListInternal($entities, $partId, $config, &$report)
    {
        $insert = array();
        $update = array();
        $delete = array();
        $ids = array();
        $query = $this->_getAvailableEntitiesIdsFetchingQuery($partId, $config);
        if (!$query instanceof Rx_Db_Select) {
            throw new Rx_Model_Exception('SQL query for fetching available entities Ids should be instance of Rx_Db_Select');
        }
        $ids = $this->getAdapter()->fetchCol($query);
        $eConfig = $this->_getEntityPartConfig($partId, $config);
        /* @var $entity Rx_Struct_Model_Abstract */
        foreach ($entities as $key => $entity) {
            $report['items'][$key] = array(
                'success' => true,
            );
            // If entity structure is not valid for storing in database - reject saving
            $errors = $this->_validateEntityPart($entity, $partId, $config);
            if (sizeof($errors)) {
                $report['items'][$key]['success'] = false;
                $report['items'][$key]['error'] = 'Entity structure part "' . $partId . '" is not valid to be stored in database';
                $report['items'][$key]['validation'] = $errors;
                trigger_error($report['items'][$key]['error'], E_USER_WARNING);
                $report['success'] = false;
                return (false);
            }
            $id = $this->_getEntityDbId($entity, $partId, $config);
            if (!$id) {
                // For INSERT queries we should also check if new insert will not fail
                // because of duplicated value of non-primary unique keys that are defined
                // on target database table. If it is - we should UPDATE target row instead
                // of inserting information into it
                $uKey = $this->_getEntityUniqueKeyId($entity, $partId, $config);
                if ($uKey) {
                    $name = $this->_getEntityDbIdMeaningName($partId, $config);
                    if ($name) {
                        $entity->setByMeaning($name, $uKey, $this->_getEntityPartConfig($partId, $config));
                    }
                    $id = $uKey;
                    if (!in_array($id, $ids)) {
                        $ids[] = $id;
                    } // To allow new Id to be stored into update queue
                } else {
                    $insert[$key] = $entity;
                }
            }
            if ($id) // Not "elseif" because of unique key check that may change Id
            {
                $t = array_search($id, $ids);
                if ($t !== false) {
                    // We should only perform update on entity if it contains some modifications
                    if ($entity->isModified(true, $eConfig)) {
                        $update[$key] = $entity;
                    }
                    unset($ids[$t]);
                }
            }
        }
        foreach ($insert as $key => $entity) {
            $r = $this->_insertEntity($entity, $partId, $config, $report['items'][$key]);
            $report['success'] = (($report['success']) && ($report['items'][$key]['success']) && ($r));
            if (!$report['success']) {
                return (false);
            }
        }
        foreach ($update as $entity) {
            $r = $this->_updateEntity($entity, $partId, $config, $report['items'][$key]);
            $report['success'] = (($report['success']) && ($report['items'][$key]['success']) && ($r));
            if (!$report['success']) {
                return (false);
            }
        }
        if (sizeof($ids)) {
            $report['deleted'] = array(
                'success' => true,
            );
            $r = $this->_deleteEntity($ids, $partId, $config, $report['deleted']);
            $report['success'] = (($report['success']) && ($report['deleted']['success']) && ($r));
            if (!$report['success']) {
                return (false);
            }
        }
        return ($report['success']);
    }

    /**
     * Handler for various hook points that occur while saving or deleting entity
     *
     * @param string $hook                     Hook point Id
     * @param Rx_Struct_Model_Abstract $entity Current entity part
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @param array $report                    Reference to entity saving report
     * @return boolean                              false to break saving process
     */
    protected function _hookPointsHandler($hook, $entity, $partId, $config, &$report)
    {
        // This method is mean to be overridden in a case if some additional actions
        // needs to be performed at certain stages of entity saving process
        // See object's HOOK_xxx constants for list of possible hook points
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
        // This method is mean to prepare SQL query to retrieve from database
        // list of Ids of rows that are represented by entity's part with given part Id
        // Child classes should normally take base of SQL query from their parent
        // and add necessary WHERE clauses
        $mCfg = $this->getModelConfig($config, $partId);
        $query = $this->select()
            ->from($mCfg->db_table, array($mCfg->id_column))
            ->scope($this->getModelScopes($config, $partId));
        return ($query);
    }

    /**
     * Determine if given entity should be stored in database
     * via INSERT statement instead of UPDATE
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to store in database
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @return boolean
     */
    protected function _needInsert($entity, $partId, $config)
    {
        $id = $this->_getEntityDbId($entity, $partId, $config);
        if (!$id) // Not null because most setters convert Id to integer
        {
            return (true);
        }
        // For database tables with no automatic Id generation we can't determine
        // if record is need to be inserted or updated just by the fact that database Id
        // is available, we need to check if it is exists in database itself
        $mCfg = $this->getModelConfig($config, $partId);
        if ($mCfg->auto_id) {
            return (false);
        }
        $query = $this->select()
            ->from($mCfg->db_table, array($mCfg->id_column))
            ->where($mCfg->id_column . '=?', $id)
            ->noScope(); // Do not use scoping because it may cause situation when Id
        // is available in database but hidden by scoping
        $exists = $this->getAdapter()->fetchOne($query);
        if ($exists === false) {
            return (true);
        }
        return (false);
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
        // This method should be overridden in a case if some special logic needs to be
        // applied to determine if some parts of entity needs to be saved in database or not
        // Practical example of such situation is saving of "empty" entity patches
        return (true);
    }

    /**
     * Get database Id of row that contains unique key value
     * that matches contents of given entity
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to get unique key Id for
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @return int|string|null                      Unique key Id from database or null of no key is available
     */
    protected function _getEntityUniqueKeyId($entity, $partId, $config)
    {
        $columns = $this->_getEntityUniqueKeyColumns($partId, $config);
        if ($columns === null) {
            return (null);
        }
        if (!is_array($columns)) {
            $columns = array($columns);
        }
        $where = array();
        $map = $this->_getEntityFieldsMap($entity, $partId, $config);
        foreach ($columns as $column) {
            if (!array_key_exists($column, $map)) {
                trigger_error(
                    'Unable to find unique key column "' . $column . '" into columns map for entity part "' . $partId . '"',
                    E_USER_WARNING
                );
                continue;
            }
            $skip = false;
            $value = $this->_getFieldDbValue($entity, $partId, $map[$column], $column, $config, $skip);
            if (!$skip) {
                $where[$column] = $value;
            }
        }
        if (!sizeof($where)) {
            return (null);
        }
        $mCfg = $this->getModelConfig($config, $partId);
        $query = $this->select()
            ->from($mCfg->db_table, array($mCfg->id_column))
            ->noScope(); // Do not use scoping because it may cause situation when Id
        // is available in database but hidden by scoping
        foreach ($where as $column => $value) {
            $query->where($this->quoteIdentifier($column) . '=?', $value);
        }
        $keyId = $this->getAdapter()->fetchOne($query);
        if ($keyId === false) {
            return (null);
        }
        return ($keyId);
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
        // This method is mean to be overridden to provide list of table columns
        // that forms unique key in database table that is represented by given entity part Id
        // Practical example is any single- or multi-column unique keys (except primary key)
        // that are defined on database table.
        // In a case of several unique keys - columns of all unique keys should be returned

        // By default model should check uniqueness of public UID if it is available
        $mCfg = $this->getModelConfig($config, $partId);
        return ($mCfg->public_uid_column);
    }

    /**
     * Store given entity contents into database with INSERT statement
     *
     * @param Rx_Struct_Model_Abstract $entity      Entity to insert in database
     * @param string $partId                        Entity part Id
     * @param array $config                         Object configuration options
     * @param array $report                         Reference to entity saving report
     * @return mixed                                Database Id of newly created row if entity was successfully saved,
     *                                              false in a case of error
     */
    protected function _insertEntity($entity, $partId, $config, &$report)
    {
        $report['operation'] = 'insert';
        $config = $this->modifyConfig($config, 'insert', true);
        if (!$this->_needSaving($entity, $partId, $config)) {
            return (true);
        }
        // We should initialize "uid" and "added_at" fields if they're available in structure
        if ($entity->getMeaningField('uid')) {
            $entity->setByMeaning(
                'uid',
                $this->generatePublicUid($config, $partId),
                $this->_getEntityPartConfig($partId, $config)
            );
        }
        if ($entity->getMeaningField('added_at')) {
            $entity->setByMeaning('added_at', new Zend_Date(), $this->_getEntityPartConfig($partId, $config));
        }
        $data = $this->_entityToDb($entity, $partId, $config);
        $mCfg = $this->getModelConfig($config, $partId);
        $result = $this->insert(
            $mCfg->db_table,
            $data,
            $this->getModelScopes($config, $partId)
        );
        if ($result === false) {
            $report['success'] = false;
            return (false);
        }
        if ($mCfg->auto_id) {
            // Set database Id back to entity
            $name = $this->_getEntityDbIdMeaningName($partId, $config);
            if ($name) {
                $id = $this->getAdapter()->lastInsertId();
                $entity->setByMeaning($name, $id, $this->_getEntityPartConfig($partId, $config));
                $report['id'] = $id;
            } else {
                $report['success'] = false;
                $report['error'] = 'Unable to determine entity structure field name to store database id to';
                trigger_error($report['error'], E_USER_ERROR);
            }
        }
        return (true);
    }

    /**
     * Store given entity contents into database with UPDATE statement
     *
     * @param Rx_Struct_Model_Abstract $entity      Entity to store in database
     * @param string $partId                        Entity part Id
     * @param array $config                         Object configuration options
     * @param array $report                         Reference to entity saving report
     * @return boolean                              true if entity was successfully saved,
     *                                              false in a case of error
     */
    protected function _updateEntity($entity, $partId, $config, &$report)
    {
        $report['operation'] = 'update';
        $config = $this->modifyConfig($config, 'insert', false);
        if (!$this->_needSaving($entity, $partId, $config)) {
            return (true);
        }
        // We should update "modified_at" field if it is available in structure
        if ($entity->getMeaningField('modified_at')) {
            $entity->setByMeaning(
                'modified_at',
                new Zend_Date(),
                $this->_getEntityPartConfig($partId, $config)
            );
        }
        $data = $this->_entityToDb($entity, $partId, $config);
        if (!sizeof($data)) {
            return (true);
        }
        $mCfg = $this->getModelConfig($config, $partId);
        // If we're updating information in database - it should became undeleted,
        // otherwise it may lead to information lose in a case of database tables
        // without autoincrement Ids
        if ($mCfg->deleted_column) {
            $data[$mCfg->deleted_column] = false;
        }
        $result = $this->update(
            $mCfg->db_table,
            $data,
            $this->quoteInto($mCfg->id_column . '=?', $this->_getEntityDbId($entity, $partId, $config)),
            $this->getModelScopes($config, $partId)
        );
        if ($result !== false) {
            $result = true;
        }
        $report['success'] = (boolean)$result;
        return ($result);
    }

    /**
     * Delete database rows that belongs to given entity part Id from database
     *
     * @param array|int|string $ids     Ids of database rows to delete from database
     * @param string $partId            Entity part Id
     * @param array $config             Object configuration options
     * @param array $report             Reference to entity saving report
     * @return boolean                  true if Ids was successfully deleted,
     *                                  false in a case of error
     */
    protected function _deleteEntity($ids, $partId, $config, &$report)
    {
        $report['operation'] = 'delete';
        if (!is_array($ids)) {
            $ids = ($ids !== null) ? array($ids) : array();
        }
        $report['ids'] = $ids;
        if (!sizeof($ids)) {
            $report['success'] = false;
            return (false);
        }
        $mCfg = $this->getModelConfig($config, $partId);
        $scopes = $this->getModelScopes($config, $partId);
        if (($mCfg->deleted_column) && (!$config['permanent_delete'])) {
            $result = $this->update(
                $mCfg->db_table,
                array($mCfg->deleted_column => true),
                $this->quoteInto($mCfg->id_column . ' in (?)', $ids),
                $scopes
            );
        } else {
            $result = $this->delete(
                $mCfg->db_table,
                $this->quoteInto($mCfg->id_column . ' in (?)', $ids),
                $scopes
            );
        }
        if ($result === false) {
            $report['success'] = false;
        }
        return ($report['success']);
    }

    /**
     * Convert given entity contents into database row
     *
     * @param Rx_Struct_Model_Abstract $entity Entity to convert
     * @param string $partId                   Entity part Id
     * @param array $config                    Object configuration options
     * @return array
     */
    protected function _entityToDb($entity, $partId, $config)
    {
        $contents = array();
        $entity = $this->_cloneEntityPart($entity, $partId, $config);
        $mapping = $this->_getEntityFieldsMap($entity, $partId, $config);
        if (!$config['insert']) {
            // If we're about to update information in database -
            // we should skip "uid" and "added_at" fields if they're available in structure
            $skip = array('uid', 'added_at');
            foreach ($skip as $name) {
                $name = $entity->getMeaningField($name);
                if ($name === null) {
                    continue;
                }
                $column = array_search($name, $mapping);
                if ($column !== false) {
                    unset($mapping[$column]);
                }
            }
            // For updating information in database we should operate
            // only with modified structure fields
            $modified = $entity->getModified();
            foreach ($mapping as $column => $name) {
                if ($name === null) // Mapping is determined programmatically, we should not remove such columns from mapping table
                {
                    continue;
                }
                if (!in_array($name, $modified)) {
                    unset($mapping[$column]);
                }
            }
        }
        // Auto-generated database Id should be excluded from mapping
        if ($this->getModelConfig($config, $partId)->auto_id) {
            $name = $entity->getMeaningField($this->_getEntityDbIdMeaningName($partId, $config));
            $column = array_search($name, $mapping);
            if ($column !== false) {
                unset($mapping[$column]);
            }
        }
        // Store entity contents into database structure
        foreach ($mapping as $column => $name) {
            $skip = false;
            $value = $this->_getFieldDbValue($entity, $partId, $name, $column, $config, $skip);
            if (!$skip) {
                $contents[$column] = $value;
            }
        }
        return ($contents);
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
        // This method should be overridden in most cases,
        // provided implementation is just for illustrative purposes
        // Normally mapping table should NOT include structure field
        // that contains database Id unless this Id is not auto-generated
        // In a case if there is no direct mapping available for some database column -
        // mapping value should contain null and value for this column
        // is expected to be provided by _getFieldDbValue()
        $map = array();
        $keys = array_keys($entity);
        foreach ($keys as $key) {
            $map[$key] = $key;
        }
        return ($map);
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
        // This method is mean to be overridden to provide additional handling
        // of structure fields values before they will be stored in database
        // Set $skip to true to skip storing field in database
        if (!isset($entity->$name)) {
            trigger_error('Unknown structure field name in mapping: ' . $name, E_USER_WARNING);
            $skip = true;
            return (null);
        }
        $value = $entity->$name;
        if (!is_object($value)) {
            return ($value);
        }
        if ($value instanceof Zend_Date) {
            $value = $value->get('yyyy-MM-dd HH:mm:ss');
        } elseif (in_array('Serializable', class_implements($value))) {
            $value = serialize($value);
        } elseif (is_callable(array($value, 'toString'))) {
            $value = $value->toString();
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
        // This method can be overridden in a case if custom set of configuration options
        // needs to be passed to model for correct entity reloading
        $id = $this->_entity->getByMeaning('db_id');
        // If there is no database Id - we can't reload entity from database
        if (!$id) {
            return (true);
        }
        $entity = $this->getCollection()->getItem($id, $modelConfig);
        $class = $this->getModelConfig()->item_class;
        if (!$entity instanceof $class) {
            return (false);
        }
        // Do not overwrite entity because it will break link between structure
        // and entity model in Rx_Struct_Model_Abstract, import reloaded values
        // into original entity instead
        $this->_entity->import($entity, array('constructor' => true));
        return (true);
    }

    /**
     * Generate public UID for entity
     *
     * @param array|Zend_Config|null $config OPTIONAL Configuration options to override default object's configuration
     * @param string $partId                 OPTIONAL Entity part Id to generate public UID for (default part Id will be used if missed)
     * @return string|null
     */
    public function generatePublicUid($config = null, $partId = null)
    {
        $uid = null;
        $mCfg = $this->getModelConfig($config, $partId);
        if (!$mCfg->public_uid_column) {
            trigger_error(
                'Unable to generate public UID for model ' . get_class(
                    $this
                ) . ' because not public UID column is defined',
                E_USER_WARNING
            );
            return (null);
        }
        try {
            $query = $this->select()
                ->from($mCfg->db_table, $mCfg->id_column)
                ->where($this->quoteIdentifier($mCfg->public_uid_column) . '=?')
                ->noScope();
            $_cnt = 100;
            while ($_cnt--) {
                $uid = Rx_Uid::getRandomUid();
                $exists = $this->getAdapter()->fetchOne($query, $uid);
                if (!$exists) {
                    return ($uid);
                }
            }
        } catch (Exception $e) {
            Rx_ErrorsHandler::getInstance()->exceptionsHandler($e);
            return (null);
        }
        trigger_error('Failed to generate unique public UID for model ' . get_class($this), E_USER_ERROR);
        return (null);
    }

    /**
     * Get structure that is wrapped by entity model object
     *
     * @return Rx_Struct_Model_Abstract
     */
    public function getStruct()
    {
        return ($this->_entity);
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'insert'            => null, // true to force entity to be inserted in database,
            // false to force entity to be updated,
            // null to detect required operation automatically
            'reload'            => false, // true to reload saved entity structure form database
            // to obtain possibly missed information
            // false to disable reloading entity in any case
            'get_report'        => false, // true to get report about entity saving, false to get boolean status
            'permanent_delete'  => false, // true to perform permanent delete operation regardless
            // of existence of "deleted" flag in entity table
            'get_errors'        => false, // Option is mean to be used with isValid() method
            // true to return array with validation errors messages,
            // false to just return validity status
            'plain_errors_list' => false, // Option is mean to be used with isValid() method
            // true to return plain list of errors
            // false to return detailed report about errors into every part of entity
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
            case 'insert':
                if ($value !== null) {
                    $value = (boolean)$value;
                }
                break;
            case 'reload':
            case 'get_report':
            case 'permanent_delete':
            case 'get_errors':
            case 'plain_errors_list':
                $value = (boolean)$value;
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

    /*
     * Rest of class provides methods to access corresponding methods of entity structure.
     */

    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return ($this->_entity->get($name));
    }

    /**
     * Magic function for setting entity fields values
     *
     * @param string $name Entity element name to set value of
     * @param mixed $value New value for this element
     * @return void
     */
    public function __set($name, $value)
    {
        $this->_entity->set($name, $value);
    }

    /**
     * Support isset() overloading on PHP 5.1
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return ($this->_entity->__isset($name));
    }

    /**
     * Support unset() overloading on PHP 5.1
     * Unsetting field name in a term of removing it from entity is not allowed,
     * so unset() just wipes field's value.
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        return ($this->_entity->__unset($name));
    }

    /**
     * Proxy for calls to entity structure methods
     *
     * @param string $name     Method name
     * @param array $arguments Method call arguments
     * @return mixed
     * @throws Rx_Model_Exception
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this->_entity, $name)) {
            throw new Rx_Model_Exception('Attempt to call to unavailable entity method: ' . $name);
        }
        return (call_user_func_array(array($this->_entity, $name), $arguments));
    }

    /**
     * Defined by Countable interface
     *
     * @return int
     */
    public function count()
    {
        return ($this->_entity->count());
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        return ($this->_entity->current());
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return ($this->_entity->key());
    }

    /**
     * Defined by Iterator interface
     *
     * @return void
     */
    public function next()
    {
        $this->_entity->next();
    }

    /**
     * Defined by Iterator interface
     *
     * @return void
     */
    public function rewind()
    {
        $this->_entity->rewind();
    }

    /**
     * Defined by Iterator interface
     *
     * @return boolean
     */
    public function valid()
    {
        return ($this->_entity->valid());
    }

}
