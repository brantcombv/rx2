<?php

class Rx_Console_Output_Handler_File extends Rx_Console_Output_Handler_Abstract
{

    /**
     * Initialize logger object
     *
     * @return void
     * @throws Rx_Console_Exception
     */
    protected function _initLog()
    {
        parent::_initLog();
        // Initialize and configure writer to file
        $path = $this->getConfig('log');
        if (!strlen($path)) {
            throw new Rx_Console_Exception('No path to log file is defined for file output');
        }
        $path = Rx_Path::normalize($path, false);
        $writer = new Zend_Log_Writer_Stream($path);
        $writer->setFormatter($this->_getFormatter());
        $this->getLog()->addWriter($writer);
    }

    /**
     * Initialize list of configuration options
     */
    protected function _initConfig()
    {
        parent::_initConfig();
        $this->_mergeConfig(array(
            'log' => null, // Path to log file to write information to
        ));
    }

    /**
     * Check that given value of configuration option is valid
     *
     * @param string $name      Configuration option name
     * @param mixed $value      Option value (passed by reference)
     * @param string $operation Current operation Id
     * @return boolean
     */
    protected function _checkConfig($name, &$value, $operation)
    {
        switch ($name) {
            case 'log':
                if (!strlen($value)) {
                    $value = null;
                }
                break;
            default:
                return (parent::_checkConfig($name, $value, $operation));
                break;
        }
        return (true);
    }

}
