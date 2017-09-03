<?php

class Rx_Controller_Action_MissedMediaFile extends Zend_Controller_Action
{

    /**
     * Handle requests to missed media file
     */
    public function indexAction()
    {
        $content = null;
        $mime = null;
        // Type parameter is expected to be in a form of MVC URL mask (module.type.id)
        $type = $this->getRequest()->getParam('type');
        $this->_reportMissedFile($this->getRequest()->getParam('url'), $type);
        $masks = array($type);
        $p = explode('.', $type);
        $p = array_slice($p, 0, 3);
        $p[0] = '*';
        $masks[] = join('.', $p);
        $p[2] = '*';
        $masks[] = join('.', $p);
        $p[1] = '*';
        $masks[] = join('.', $p);
        foreach ($masks as $mask) {
            $content = $this->_getResponseContent($mask, $mime);
            if ($content !== null) {
                break;
            }
        }
        if ($content === null) {
            $content = $this->_getEmptyResponse($mime);
        }
        // Output received content
        $date = new Zend_Date();
        $date->subYear(1)->setTimezone('GMT');
        $response = $this->getResponse();
        $response->setHeader('Expires', $date->get(Zend_Date::RFC_2822), true);
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Type', $mime, true);
        $response->setHeader('Content-Length', strlen($content), true);
        $response->setBody($content);
    }

    /**
     * Post-dispatch routines
     *
     * @return void
     */
    public function postDispatch()
    {
        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        // No default view rendering should be performed
        if ($this->_helper->hasHelper('viewRenderer')) {
            $this->_helper->viewRenderer->setNoRender();
        }
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->getLayoutInstance()->disableLayout();
        }
    }

    /**
     * Perform reporting about request of missed media file
     *
     * @param string $url  URL of missed media file
     * @param string $type Type of missed media file
     * @return void
     */
    protected function _reportMissedFile($url, $type)
    {
        // This method is mean to be overridden into applications
    }

    /**
     * Get response for missed media file
     *
     * @param string $mask Media file mask
     * @param string $mime Reference to set MIME type of content
     * @return string|null      Content or null if there is no content for
     */
    protected function _getResponseContent($mask, &$mime)
    {
        $content = null;
        switch ($mask) {
            case '*.image.*':
                // This is empty GIF file
                $mime = 'image/gif';
                $content = base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
                break;
            case '*.script.*':
                $mime = 'text/javascript';
                $content = ' '; // Avoid totally empty responses to be sent
                break;
            case '*.style.*':
                $mime = 'text/css';
                $content = ' '; // Avoid totally empty responses to be sent
                break;
            case '*.static.favicon':
                // Site's favicon
                $mime = 'image/vnd.microsoft.icon';
                $content = base64_decode(
                    'AAABAAEAEBACAAEAAQCwAAAAFgAAACgAAAAQAAAAIAAAAAEAAQ' .
                    'AAAAAAAAAAAEcAAABHAAAAAgAAAAAAAAAAAAAA////AAAAAAAA' .
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
                    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA//8AAP//AAD//wAA' .
                    '//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//' .
                    '8AAP//AAD//wAA'
                );
                break;
            case '*.static.favicon-ios':
                // Site's favicon for iOS devices
                $mime = 'image/png';
                $content = base64_decode(
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAEE' .
                    'lEQVR42mL8//8/A0CAAQAJAQL/abyQ8AAAAABJRU5ErkJggg=='
                );
                break;
            case '*.static.robots':
                // Missed robots.txt
                $mime = 'text/plain';
                $content = 'User-agent: *' . "\n";
                break;
            case '*.static.sitemap':
                // Missed sitemap
                $mime = 'text/xml';
                $request = $this->getRequest();
                $content = '<' . '?xml version="1.0" encoding="utf-8"?' . '>' .
                    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
                    '<url><loc>' . $request->getScheme() . '://' . $request->getHttpHost() . '/</loc></url></urlset>';
                break;
            case '*.static.crossdomain':
                // Missed cross-domain access declaration
                $mime = 'text/xml';
                $request = $this->getRequest();
                $content = '<' . '?xml version="1.0" encoding="utf-8"?' . '>' .
                    '<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">' .
                    '<cross-domain-policy>' .
                    '<allow-access-from domain="*" />' .
                    '</cross-domain-policy>';
                break;
        }
        return ($content);
    }

    /**
     * Get empty response as response for missed media file
     *
     * @param string $mime Reference to set MIME type of content
     * @return string
     */
    protected function _getEmptyResponse(&$mime)
    {
        $mime = 'text/plain';
        return (' '); // Avoid totally empty responses to be sent
    }

}
