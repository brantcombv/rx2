<?php

class Rx_View_Filter_Translate
{
    /**
     * View object, this filter is applied to
     *
     * @var Zend_View $_view
     */
    protected $_view = null;

    /**
     * Set view object, this filter is applied to
     *
     * @param Zend_View $view
     */
    public function setView($view)
    {
        if ($view instanceof Zend_View) {
            $this->_view = $view;
        }
    }

    /**
     * Implement transparent translation of Zend_View scripts
     *
     * @param string $content Zend_View content to filter
     * @return string
     */
    public function filter($content)
    {
        $content = preg_replace_callback('/\{\*(.*?)\*\}/usi', array($this, 'translate'), $content);
        return ($content);
    }

    protected function translate($data)
    {
        $message = (isset($data[1])) ? $data[1] : null;
        $message = Rx_Translate::translate($message, true);
        return ($message);
    }

}
