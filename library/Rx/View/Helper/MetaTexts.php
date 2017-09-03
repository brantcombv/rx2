<?php

class Rx_View_Helper_MetaTexts extends Zend_View_Helper_Abstract
{
    /**
     * List of known Ids of meta texts
     *
     * @var array $_ids
     */
    protected $_ids = array();
    /**
     * Meta texts configuration Id to use for meta texts rendering
     *
     * @var string $_configId
     */
    protected $_configId = null;
    /**
     * List of parameters for meta texts rendering
     *
     * @var array $_params
     */
    protected $_params = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        // Load default information from application configuration
        $ids = Rx_Config::get('rx.metatexts.ids');
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $id = trim(strtolower($id));
            if (!strlen($id)) {
                continue;
            }
            $this->_ids[$id] = true;
        }
        $this->setConfigId('default');
    }

    /**
     * Enable/disable type of meta texts template with given Id
     *
     * @param string $metaId  Meta text Id
     * @param boolean $enable OPTIONAL true to enable meta text, false to disable it
     */
    public function enableMetaText($metaId, $enable = true)
    {
        if (array_key_exists($metaId, $this->_ids)) {
            $this->_ids[$metaId] = (boolean)$enable;
        }
    }

    /**
     * Set Id of meta texts configuration to use for meta texts rendering
     *
     * @param string $configId Meta texts configuration Id
     * @return void
     */
    public function setConfigId($configId)
    {
        $this->_configId = $configId;
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
        $this->_params[$name] = $value;
    }

    /**
     * Set multiple parameters for meta texts rendering
     *
     * @param array $params Parameters array
     * @return void
     */
    public function setTemplateParams($params)
    {
        if (!is_array($params)) {
            return;
        }
        foreach ($params as $k => $v) {
            $this->setTemplateParam($k, $v);
        }
    }

    /**
     * Render meta texts for page
     *
     * @return void
     */
    public function metaTexts()
    {
        // Build list of parameters for meta texts templates rendering
        $dParams = Rx_Config::getArray('rx.metatexts.default.param');
        $cParams = ($this->_configId != 'default') ? Rx_Config::getArray(
            'rx.metatexts.' . $this->_configId . '.param'
        ) : array();
        $parameters = array_merge($dParams, $cParams, $this->_params);

        // Load and render meta texts templates
        $dTemplates = Rx_Config::getArray('rx.metatexts.default.template');
        $cTemplates = ($this->_configId != 'default') ? Rx_Config::getArray(
            'rx.metatexts.' . $this->_configId . '.template'
        ) : array();
        foreach ($this->_ids as $id => $status) {
            if (!$status) {
                continue;
            }
            $template = null;
            if (array_key_exists($id, $cTemplates)) {
                $template = $cTemplates[$id];
            } elseif (array_key_exists($id, $dTemplates)) {
                $template = $dTemplates[$id];
            }
            if (!strlen($template)) {
                continue;
            }
            // Render template and store it in view
            $template = Rx_Template::render($template, $parameters);
            if (!strlen($template)) {
                continue;
            }
            if ($id == 'title') {
                $this->view->headTitle($template);
            } else {
                $meta = str_replace(' ', '-', ucwords(strtr($id, array('-' => ' ', '_' => ' '))));
                $this->view->headMeta()->setName($meta, $template);
            }
        }
    }
}
