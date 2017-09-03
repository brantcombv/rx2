<?php

class Rx_Console_Output_Handler_Stdout extends Rx_Console_Output_Handler_Abstract
{
    /**
     * Output stream resource
     *
     * @var resource $_stream
     */
    protected $_stream = null;
    /**
     * true if LF needs to be sent to stdout before writing new log message
     *
     * @var boolean $_needLf
     */
    protected $_needLf = false;

    /**
     * Handler of calls to Zend_Log methods implicitly defined by log priorities
     *
     * @param string $method Priority name
     * @param string $params Message to log
     * @return void
     * @throws Rx_Console_Exception
     */
    public function __call($method, $params)
    {
        if (!$this->_enabled) {
            return;
        }
        if ($this->_needLf) {
            $this->writeln();
        }
        parent::__call($method, $params);
    }

    /**
     * Initialize logger object
     *
     * @return void
     * @throws Rx_Console_Exception
     */
    protected function _initLog()
    {
        parent::_initLog();
        // Initialize and configure writer to stdout
        $this->_stream = fopen('php://stdout', 'w');
        if (!is_resource($this->_stream)) {
            throw new Rx_Console_Exception('Failed to open stream to stdout');
        }
        $writer = new Zend_Log_Writer_Stream($this->_stream);
        $writer->setFormatter($this->_getFormatter());
        $this->getLog()->addWriter($writer);
    }

    /**
     * Write message into console output without line feed
     *
     * @param string $message Message to write
     * @return void
     */
    public function write($message)
    {
        if (!$this->_enabled) {
            return;
        }
        if (!is_resource($this->_stream)) {
            $this->_initLog();
        }
        fwrite($this->_stream, $message);
        $this->_needLf = true;
    }

    /**
     * Write message into console output with line feed
     *
     * @param string $message OPTIONAL Message to write
     * @return void
     */
    public function writeln($message = '')
    {
        if (!$this->_enabled) {
            return;
        }
        $this->write($message . "\n");
        $this->_needLf = false;
    }

}
