<?php

class Rx_Validate_NotInCollection extends Rx_Validate_Abstract
{
    const ALREADY_EXISTS = 'alreadyExists';

    protected $_messageTemplates = array(
        self::ALREADY_EXISTS => 'Given Id is already available in collection',
    );
    /**
     * Name of collection model to check given value in
     *
     * @var string $_collection
     */
    protected $_collection = null;

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = null)
    {
        /* @var $collection Rx_Model_Collection */
        $collection = Rx_ModelManager::get($this->getCollection());
        if (!$collection instanceof Rx_Model_Collection) {
            throw new Zend_Validate_Exception('Defined collection name "' . $this->getCollection() . '" does not belongs to actual collection');
        }
        $info = $collection->isValid(
            $value,
            true,
            array(
                'only_active' => false,
            )
        );
        if ($info['exists']) {
            $this->_error(self::ALREADY_EXISTS);
            return (false);
        }
        return (true);
    }

    /**
     * Set name of collection model to check given value in
     *
     * @param string $collection
     * @return void
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;
    }

    /**
     * Set name of collection model to check given value in
     *
     * @return string
     */
    public function getCollection()
    {
        return ($this->_collection);
    }

}
