<?php

class Rx_Application_Resource_Path extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config');
    /**
     * Options for the resource
     *
     * @var $_options array
     */
    protected $_options = array(
        'root' => array(
            'server' => null,
            'site'   => '^public',
            'url'    => '/',
        ),
        'map'  => array(
            'app'     => '^application',
            'rx'      => 'APPLICATION_RX_LIB',
            'classes' => ':app/classes',
            'logs'    => '^logs',
            'cache'   => '^cache',
        ),
    );

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // Define root paths
        $serverRoot = $this->getOption('root.server');
        if (!$serverRoot) {
            $serverRoot = APPLICATION_ROOT;
        }
        Rx_Path::setServerRoot($serverRoot);
        $siteRoot = $this->getOption('root.site');
        if (!$siteRoot) {
            if (array_key_exists('DOCUMENT_ROOT', $_SERVER)) {
                $siteRoot = $_SERVER['DOCUMENT_ROOT'];
            }
        }
        if (!$siteRoot) {
            $siteRoot = $serverRoot;
        }
        Rx_Path::setSiteRoot($siteRoot);
        Rx_Path::setBaseUrl($this->getOption('root.url'));

        // Determine path to temp directory and register it by default
        $tempPath = getenv('TEMP');
        if (!$tempPath) {
            $tempPath = getenv('TMP');
        }
        if ((!$tempPath) && (Rx_Path::isUnix())) {
            $tempPath = '/tmp';
        }
        if ($tempPath) {
            Rx_Path::register('temp', $tempPath, Rx_Path::ABSOLUTE);
        }

        // Register paths that are came from application-wide constants
        $pathMap = array(
            'APPLICATION_PATH'    => 'app',
            'APPLICATION_LIBRARY' => 'lib',
        );
        foreach ($pathMap as $const => $name) {
            if ((defined($const)) && (!Rx_Path::isRegistered($name))) {
                Rx_Path::register($name, constant($const));
            }
        }
        // Register path to root of Rx library to allow to construct references to paths inside it
        Rx_Path::register('rx', dirname(__FILE__) . '/../../');

        // Register paths that came from application's configuration
        $paths = $this->getOption('map', array());
        // Make sure that references to server/site roots (prefixed with ^ and ~ respectively)
        // will be registered before any path references (prefixed with :) to avoid problem
        // when reference points to not-yet-resolvable path. To achieve this - all paths should be
        // sorted by "registration priority" before actual registration process will start
        $priorities = array(
            1 => array(),
            2 => array(),
            3 => array(),
        );
        foreach ($paths as $name => $path) {
            $priority = 2;
            if (defined($path)) {
                $path = constant($path);
            }
            $prefix = substr($path, 0, 1);
            switch ($prefix) {
                case '^':
                case '~':
                    $priority = 1;
                    break;
                case ':':
                    $priority = 3;
                    break;
            }
            $priorities[$priority][$name] = $path;
        }
        // Register paths
        foreach ($priorities as $paths) {
            foreach ($paths as $name => $path) {
                Rx_Path::register($name, $path);
            }
        }
    }

}
