<?php

interface Rx_ErrorsHandler_Listener_Interface
{
    /**
     * Handle application error
     *
     * @param array $error      Error details:
     *                          date        - Date when error occurs (timestamp)
     *                          level       - Error level (one of E_xxx constants)
     *                          message     - Error message text
     *                          filename    - Name of file, error occurs in
     *                          line        - Line number where error occurs
     *                          backtrace   - Backtrace for error
     * @return void
     */
    public function handleError($error);

    /**
     * Handle application exception
     *
     * @param array $exception  Exception details:
     *                          date        - Date when exception occurs (timestamp)
     *                          type        - Exception type
     *                          level       - Error level ("exception" string)
     *                          message     - Exception message text
     *                          code        - Exception code
     *                          filename    - Name of file, exception occurs in
     *                          line        - Line number where exception occurs
     *                          backtrace   - Backtrace for exception
     * @return void
     */
    public function handleException($exception);

}