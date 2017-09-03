<?php

/**
 * Own version of partial view helper
 * Aims to workaround unresolved ZF-3549 issue
 */
class Rx_View_Helper_Partial extends Zend_View_Helper_Partial
{

    /**
     * Clone the current View
     *
     * @return Zend_View_Interface
     */
    public function cloneView()
    {
        // Improve problem with original Zend_View: attached view helpers
        // keeps link to original, not cloned view
        /* @var $view Zend_View */
        $view = parent::cloneView();
        $helpers = $view->getHelpers();
        /* @var $helper Zend_View_Helper_Abstract */
        foreach ($helpers as $name => $helper) {
            $helper = clone $helper;
            $helper->setView($view);
            $view->registerHelper($helper, $name);
        }
        return ($view);
    }

}
