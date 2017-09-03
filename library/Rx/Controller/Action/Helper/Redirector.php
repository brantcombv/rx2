<?php

class Rx_Controller_Action_Helper_Redirector extends Zend_Controller_Action_Helper_Redirector
{

    public function __construct()
    {
        // Exit is disabled by default to allow complete MVC flow,
        // otherwise shutdown plugins will not be called
        $this->setExit(false);
    }

    /**
     * Determine if the baseUrl should be prepended, and prepend if necessary
     *
     * @param  string $url
     * @return string
     */
    protected function _prependBase($url)
    {
        if ($this->getPrependBase()) {
            $request = $this->getRequest();
            if ($request instanceof Zend_Controller_Request_Http) {
                // Don't prepend base url to urls that already contains it
                $base = $request->getBaseUrl();
                if (substr($base, -1) != '/') {
                    $base .= '/';
                }
                if ((!empty($base)) && ('/' != $base) && (substr($url, 0, strlen($base)) != $base)) {
                    $url = $base . ltrim($url, '/');
                } else {
                    $url = '/' . ltrim($url, '/');
                }
            }
        }

        return $url;
    }

}