<?php

class Rx_Controller_Plugin_ImagesInfoManager extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopShutdown()
    {
        Rx_ImagesInfoManager::shutdown();
    }
}
