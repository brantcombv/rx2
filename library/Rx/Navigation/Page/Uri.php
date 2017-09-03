<?php

class Rx_Navigation_Page_Uri extends Rx_Navigation_Page
{
    /**
     * Page URI
     *
     * @var string|null
     */
    protected $_uri = null;

    /**
     * Sets page URI
     *
     * @param  string $uri page URI, must a string or null
     * @return Zend_Navigation_Page_Uri   fluent interface, returns self
     * @throws Zend_Navigation_Exception  if $uri is invalid
     */
    public function setUri($uri)
    {
        if (null !== $uri && !is_string($uri)) {
            throw new Zend_Navigation_Exception('Invalid argument: $uri must be a string or null');
        }

        $this->_uri = $uri;
        return $this;
    }

    /**
     * Returns URI
     *
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * Returns href for this page
     *
     * @return string
     */
    public function getHref()
    {
        $uri = $this->getUri();

        $fragment = $this->getFragment();
        if (null !== $fragment) {
            if ('#' == substr($uri, -1)) {
                return $uri . $fragment;
            } else {
                return $uri . '#' . $fragment;
            }
        }

        return $uri;
    }

    // Public methods:

    /**
     * Returns an array representation of the page
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'uri' => $this->getUri()
            )
        );
    }
}
