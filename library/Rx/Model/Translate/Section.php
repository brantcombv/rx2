<?php

class Rx_Model_Translate_Section extends Rx_Model_Entity
{
    /**
     * Name of corresponding Rx_Model_Collection based class (named Id to use for Rx_ModelManager)
     *
     * @var string $_collectionClassName
     */
    protected $_collectionClassName = 'translate_sections';

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
        $errors = array();
        if (!strlen($entity->id)) {
            $errors['id'] = 'Text section Id must be defined';
        }
        return ($errors);
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
        return (array(
            'id'               => 'id',
            'can_have_subids'  => 'subids',
            'can_have_patches' => 'patches',
            'raw_keys_allowed' => 'raw',
            'description'      => 'description',
        ));
    }

}
