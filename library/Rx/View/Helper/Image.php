<?php

class Rx_View_Helper_Image extends Zend_View_Helper_HtmlElement
{

    public function __construct()
    {
        $this->_closingBracket = false;
    }

    /**
     * Render <img> element with given parameters
     *
     * @param string $url  Image url (as accepted by Rx_Url)
     * @param array $attrs Additional attributes for image
     * @return string
     */
    public function image($url, $attrs = array())
    {
        if ((isset($attrs['alt'])) && (!isset($attrs['title']))) {
            $attrs['title'] = $attrs['alt'];
        } elseif ((isset($attrs['title'])) && (!isset($attrs['alt']))) {
            $attrs['alt'] = $attrs['title'];
        } elseif ((!isset($attrs['title'])) && (!isset($attrs['alt']))) {
            $attrs['alt'] = '';
            $attrs['title'] = '';
        }
        if (!isset($attrs['src'])) {
            $attrs['src'] = Rx_Url::url($url);
        }
        if (isset($attrs['id'])) {
            $attrs['id'] = $this->_normalizeId($attrs['id']);
        }
        $info = Rx_ImagesInfoManager::getByUrl($attrs['src']);
        if (is_array($info)) {
            if (!isset($attrs['width'])) {
                $attrs['width'] = $info['width'];
            }
            if (!isset($attrs['height'])) {
                $attrs['height'] = $info['height'];
            }
        }
        $names = array('id', 'class', 'src', 'width', 'height', 'alt', 'title');
        $_attrs = array();
        foreach ($names as $n) {
            if (isset($attrs[$n])) {
                $_attrs[$n] = $attrs[$n];
                unset($attrs[$n]);
            }
        }
        $attrs = array_merge($_attrs, $attrs);
        $html = '<img' . $this->_htmlAttribs($attrs) . $this->getClosingBracket();
        return ($html);
    }
}
