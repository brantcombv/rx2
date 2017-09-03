<?php

class Rx_Application_Resource_Appstate extends Rx_Application_Resource_Abstract
{

    /**
     * Options for the resource
     *
     * @var array
     */
    protected $_options = array(
        'useSession' => null, // true to use session for application state storage,
        // false to only use local storage
        // null to auto-detect best value
        'prefixPath' => null, // Additional prefix path to register for plugin loader
    );

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        $options = $this->getOptions();
        $useSession = $options['useSession'];
        // By default we should disable sessions usage for console environment
        if ($useSession === null) {
            $useSession = (php_sapi_name() !== 'cli');
        }
        if (!Rx_AppState::isInitialized()) {
            Rx_AppState::setSessionUse($useSession);
            if ($options['prefixPath']) {
                $loader = Rx_AppState::getPluginLoader();
                $prefixes = Rx_Loader::getPrefixPath($options['prefixPath']);
                foreach ($prefixes as $prefix => $path) {
                    $loader->addPrefixPath($prefix, $path);
                }
            }
        }
    }

}
