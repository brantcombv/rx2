<?php

class Rx_Controller_Action_SharedFile extends Zend_Controller_Action
{
    /**
     * Output shared file contents
     */
    public function indexAction()
    {
        // No default view rendering should be performed
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->getLayoutInstance()->disableLayout();
        }
        $this->_helper->viewRenderer->setNoRender();

        // Get information about shared file by information from request
        $request = $this->getRequest();
        $collectionId = $request->getParam('collection');
        $collection = Rx_SharedFiles::get($collectionId, true);
        if (!$collection instanceof Rx_SharedFiles_Collection) {
            trigger_error('Unknown shared files collection Id: ' . $collectionId, E_USER_ERROR);
            return;
        }
        $fileId = $request->getParam('id');
        $info = $collection->getFileInfo($fileId, true);
        if (!$info instanceof Rx_SharedFiles_File) {
            trigger_error(
                'Shared file "' . $fileId . '" is missed in collection "' . $collectionId . '"',
                E_USER_ERROR
            );
            return;
        }
        $date = new Zend_Date();
        $date->addYear(1);
        $date->setTimezone('GMT');
        $response = $this->getResponse();
        $response->setHeader('Content-Type', $info->mime, true);
        $response->setHeader('Content-Length', $info->size, true);
        $response->setHeader('ETag', $collection->getId() . '-' . $info->id . '-' . $info->hash, true);
        $response->setHeader('Expires', $date->get(Zend_Date::RFC_2822));
        $response->setHeader('Cache-Control', 'max-age=31536000, public');
        $response->setBody($info->content);
    }

}

