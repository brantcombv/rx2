<?php

class Rx_Validate_InCollection extends Rx_Validate_Abstract
{
    const NOT_AVAILABLE = 'notAvailable';
    const NOT_ACTIVE = 'notActive';

    protected $_messageTemplates = array(
        self::NOT_AVAILABLE => 'Given Id is not available in collection',
        self::NOT_ACTIVE    => 'Given Id is not active',
    );
    /**
     * Name of collection model to check given value in
     *
     * @var string $_collection
     */
    protected $_collection = null;
    /**
     * true to pass validation only for active collection items, false - for any available item
     *
     * @var boolean $_onlyActive
     */
    protected $_onlyActive = true;

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
                'only_active' => $this->getOnlyActive(),
            )
        );
        if (!$info['exists']) {
            $this->_error(self::NOT_AVAILABLE);
            return (false);
        }
        if (($this->getOnlyActive()) && (!$info['active'])) {
            $this->_error(self::NOT_ACTIVE);
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

    /**
     * Set "only active" flag for validator
     *
     * @param boolean $flag
     * @return void
     */
    public function setOnlyActive($flag)
    {
        $this->_onlyActive = (boolean)$flag;
    }

    /**
     * Get "only active" flag for validator
     *
     * @return boolean
     */
    public function getOnlyActive()
    {
        return ($this->_onlyActive);
    }

}
