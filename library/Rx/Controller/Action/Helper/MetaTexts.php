<?php

class Rx_Controller_Action_Helper_MetaTexts extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * true if meta texts rendering is enabled, false if not
     *
     * @var boolean $_enabled
     */
    protected $_enabled = true;
    /**
     * View helper for meta texts rendering
     *
     * @var Rx_View_Helper_MetaTexts $_helper
     */
    protected $_helper = null;

    public function __construct()
    {
        // Automatically register ourselves upon instantiating
        // because it is possible to disable helpers by calling setEnabled(false)
        Zend_Controller_Action_HelperBroker::addHelper($this);
    }

    /**
     * Enable/disable meta texts rendering
     *
     * @param boolean $status
     * @return void
     */
    public function setEnabled($status)
    {
        $this->_enabled = (boolean)$status;
    }

    /**
     * Enable/disable type of meta texts template with given Id
     *
     * @param string $metaId  Meta text Id
     * @param boolean $enable OPTIONAL true to enable meta text, false to disable it
     */
    public function enableMetaText($metaId, $enable = true)
    {
        $this->getHelper()->enableMetaText($metaId, $enable);
    }

    /**
     * Set Id of meta texts configuration to use for meta texts rendering
     *
     * @param string $configId Meta texts configuration Id
     * @return void
     */
    public function setConfigId($configId)
    {
        $this->getHelper()->setConfigId($configId);
    }

    /**
     * Set parameter for meta texts rendering
     *
     * @param string $name  Parameter name
     * @param string $value Parameter value
     * @return void
     */
    public function setTemplateParam($name, $value)
    {
        $this->getHelper()->setTemplateParam($name, $value);
    }

    /**
     * Set multiple parameters for meta texts rendering
     *
     * @param array $params Parameters array
     * @return void
     */
    public function setTemplateParams($params)
    {
        $this->getHelper()->setTemplateParams($params);
    }

    /**
     * Hook into action controller postDispatch() workflow
     *
     * @return void
     */
    public function postDispatch()
    {
        // No rendering should be done for redirects and if it is disabled explicitly
        if ((!$this->_enabled) ||
            ($this->getResponse()->isRedirect())
        ) {
            return;
        }
        /** @var $vr Zend_Controller_Action_Helper_ViewRenderer */
        $vr = Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer');
        if (($vr) && (($vr->getNeverRender()) || ($vr->getNoRender()))) {
            return;
        }
        // Render meta texts
        $this->getHelper()->metaTexts();
    }

    /**
     * Get view helper for meta texts rendering
     *
     * @return Rx_View_Helper_MetaTexts
     */
    protected function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = $this->getActionController()->view->getHelper('metaTexts');
        }
        return ($this->_helper);
    }

}
