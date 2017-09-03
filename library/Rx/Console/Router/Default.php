<?php

class Rx_Console_Router_Default extends Rx_Console_Router_Abstract
{

    /**
     * Perform routing of current request
     *
     * @param $processId
     * @param $taskId
     * @return boolean
     */
    protected function _route(&$processId, &$taskId)
    {
        $args = $this->_getArgs(true);
        $processId = pathinfo(array_shift($args), PATHINFO_FILENAME);
        $task = array_shift($args);
        if (substr($task, 0, 1) != '-') {
            $taskId = $task;
        }
        return (true);
    }

}