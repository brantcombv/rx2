<?php

class Rx_View_Helper_StaticPage extends Zend_View_Helper_Abstract
{

    /**
     * Render given static page text
     *
     * @param string $page Static page text to render
     * @return string
     */
    public function staticPage($page)
    {
        $sp = new Rx_StaticPage();
        if (preg_match('/^[a-z0-9\_\-]+$/i', $page)) {
            $text = $sp->getPage($page, Rx_Language::getLanguageId(), true, false);
            if ($text) {
                $page = $text;
            }
        }
        $page = $sp->render($page, $this->view);
        return ($page);
    }

}
