<?php

/**
 * Main application class
 * Main purposes for overriding Zend_Application are:
 *  - Early initialization of application-wide constants
 *  - Support for setting bootstrap class in a case of Composer-based environment
 *  - Support for multi-level inheritance of application's configuration files (not yet implemented)
 */
class Rx_Application extends Zend_Application
{
    /**
     * Class constructor.
     * Unlike Zend_Application its purpose is to perform very early initialization
     * of several application-based constants that will help to define application
     * layout and behavior.
     *
     * Method defined following constants, each of them can be overridden:
     *
     * APPLICATION_ROOT - Path to application's root directory (highly recommended to be defined)
     * APPLICATION_PATH - Path to application's directory (normally "/application")
     * APPLICATION_ENV - Environment to run application in (normally "production")
     * APPLICATION_DEBUG - TRUE to allow debugging functionality, FALSE to disable it (normally enabled to "development" environment)
     * APPLICATION_CONFIG - Application's configuration in a case of multi-configuration applications (normally "web")
     * APPLICATION_CONFIG_PATH - Path to main application's configuration file (normally path is determined from other constants)
     * APPLICATION_ENCODING - Application's encoding (normally "utf-8")
     * APPLICATION_VERSION - Current application's version (determined programmatically if not defined)
     * APPLICATION_VERSION_FILE - Path to file to store application's version information in a case it is not defined explicitly
     *
     * Following constants are only used when Composer is NOT used for loading application:
     * APPLICATION_LIBRARY - Path to application's shared libraries directory (normally "/library")
     * APPLICATION_ZF_LIB - Path to Zend Framework library (normally "/library/Zend")
     *
     * @throws Exception
     * @return Rx_Application
     */
    public function __construct()
    {
        // Define APPLICATION_ROOT - Path to application's root directory
        if (!defined('APPLICATION_ROOT')) {
            // Application root is not defined, try to determine it by looking for "application" directory
            $appRootPath = realpath(dirname(__FILE__) . '/../');
            $curAppRootPath = null;
            // Directories bubbling loop should be limited to avoid infinite loop in a case if application directory is somewhere else
            for ($i = 0; $i < 100; $i++) {
                $curAppRootPath = realpath($appRootPath . '/application');
                if (is_dir($curAppRootPath)) {
                    define('APPLICATION_ROOT', $appRootPath);
                    break;
                }
                $appRootPath = dirname($appRootPath);
            }
            if (!defined('APPLICATION_ROOT')) {
                throw new Exception('APPLICATION_ROOT constant is not defined and can\'t be determined automatically');
            }
        }

        // Define APPLICATION_PATH - Path to application's directory (normally "application")
        if (!defined('APPLICATION_PATH')) {
            $appPath = realpath(APPLICATION_ROOT . '/application');
            if (is_dir($appPath)) {
                define('APPLICATION_PATH', $appPath);
            } else {
                throw new Exception('APPLICATION_PATH constant is not defined and can\'t be determined automatically');
            }
        }

        // Define path to root of Rx library
        if (!defined('APPLICATION_RX_LIB')) {
            define('APPLICATION_RX_LIB', dirname(__FILE__));
        }

        // If application doesn't use Composer for autoloading -
        // determine paths to libraries and configure include paths
        if (!class_exists('Composer\Autoload\ClassLoader', false)) {
            // Configure include paths for libraries
            $includePaths = array();
            if (defined('APPLICATION_ZF_LIB')) {
                $includePaths[] = APPLICATION_ZF_LIB;
            }
            if (getenv('ZF_LIB')) {
                $includePaths[] = getenv('ZF_LIB');
            }
            if (defined('APPLICATION_LIBRARY')) {
                $includePaths[] = APPLICATION_LIBRARY . '/Zend';
                $includePaths[] = APPLICATION_LIBRARY;
            }
            $includePaths[] = APPLICATION_ROOT . '/library';
            foreach ($includePaths as $k => $v) {
                $v = realpath($v);
                if (($v) && (is_dir($v))) {
                    $includePaths[$k] = $v;
                } else {
                    unset($includePaths[$k]);
                }
            }
            $includePaths = array_unique($includePaths);
            if (sizeof($includePaths)) {
                $includePaths[] = get_include_path();
                set_include_path(implode(PATH_SEPARATOR, $includePaths));
            }
        }

        // Define APPLICATION_ENV - current application's environment
        if (!defined('APPLICATION_ENV')) {
            $appEnv = 'production';
            if (getenv('APPLICATION_ENV')) {
                $appEnv = getenv('APPLICATION_ENV');
            } elseif (file_exists(APPLICATION_ROOT . '/.env')) {
                $appEnv = preg_replace('/[^a-z0-9\-\_]+/usi', '', file_get_contents(APPLICATION_ROOT . '/.env'));
            }
            define('APPLICATION_ENV', $appEnv);
            unset($appEnv);
        }
        // Define APPLICATION_DEBUG - indicator if application is running in debug environment
        defined('APPLICATION_DEBUG')
        || define('APPLICATION_DEBUG', (APPLICATION_ENV == 'development'));

        // Define APPLICATION_CONFIG - type of system configuration file
        // This constant may be overridden by application e.g. for console application
        defined('APPLICATION_CONFIG')
        || define('APPLICATION_CONFIG', 'web');

        // Determine path to application's configuration file
        $appCfg = null;
        if ((defined('APPLICATION_CONFIG_PATH')) && (file_exists(APPLICATION_CONFIG_PATH))) {
            $appCfg = APPLICATION_CONFIG_PATH;
        } else {
            // Try to build path application's config based on information that we already know
            $appCfg = realpath(APPLICATION_PATH . '/configs/env_' . APPLICATION_CONFIG . '.ini');
        }
        if (!file_exists($appCfg)) {
            throw new Exception('Unable to find application\'s configuration file');
        }

        // If we're about to launch console process - determine Id of current console process
        // to allow application to load process-specific configuration
        if ((APPLICATION_CONFIG == 'console') &&
            (!defined('CONSOLE_PROCESS_ID')) &&
            (array_key_exists('argv', $_SERVER)) &&
            (sizeof($_SERVER['argv']))
        ) {
            define('CONSOLE_PROCESS_ID', preg_replace('/\.[^\.]+$/', '', basename($_SERVER['argv'][0])));
        }

        $appEncoding = (defined('APPLICATION_ENCODING')) ? APPLICATION_ENCODING : 'utf-8';
        // Configure encoding for mbstring extension
        if (extension_loaded('mbstring')) {
            mb_internal_encoding($appEncoding);
            mb_regex_encoding($appEncoding);
        }
        if (extension_loaded('iconv')) {
            iconv_set_encoding('internal_encoding', $appEncoding);
        }

        // Disable warnings about undefined default timezone
        date_default_timezone_set(@date_default_timezone_get());

        // Define application version, it may be used by some application components
        // that needs their information to be changed when application itself is changed
        if (!defined('APPLICATION_VERSION')) {
            // Determine application version
            // In a case if application version should be determined from version control system -
            // it is important to define APPLICATION_VERSION_FILE constant that should define path to file
            // which will store current version number to avoid parsing of VCS output on every request
            $vFilter = '/[^0-9a-f\.\-]+/usi';
            $vPath = (defined('APPLICATION_VERSION_FILE')) ? APPLICATION_VERSION_FILE : null;
            $vValid = file_exists($vPath);
            if (APPLICATION_DEBUG) {
                // For development purposes refresh version file from time to time
                if ((file_exists($vPath)) &&
                    (filemtime($vPath) <= strtotime('-10 minutes'))
                ) {
                    unlink($vPath);
                    $vValid = false;
                }
            }
            if ($vValid) {
                $vVersion = file_get_contents($vPath);
            } else {
                $vVersion = null;
                $vCmd = null;
                if (is_dir(APPLICATION_ROOT . '/.svn')) {
                    // Get version information from Subversion
                    $vCmd = 'svn info ' . APPLICATION_ROOT . ' | grep "Revision:"';
                    $vFilter = '/\D+/usi';
                } elseif (is_dir(APPLICATION_ROOT . '/.git')) {
                    // Get version information from Git
                    $vCmd = 'git log -n 1 --format="format:%h"';
                    $vFilter = '/[^0-9a-f]+/usi';
                }
                if ($vCmd) {
                    $fp = popen($vCmd, 'r');
                    if (is_resource($fp)) {
                        $vVersion = fread($fp, 4096);
                        pclose($fp);
                    }
                }
                if ($vVersion) {
                    $vVersion = preg_replace($vFilter, '', $vVersion);
                    if ($vPath) {
                        $vDir = dirname($vPath);
                        if (!is_dir($vDir)) {
                            mkdir($vDir, 0775, true);
                        }
                        file_put_contents($vPath, $vVersion);
                        chmod($vPath, 0664);
                    }
                }
            }
            $vVersion = preg_replace($vFilter, '', $vVersion);
            if (!strlen($vVersion)) {
                $vVersion = date('YmdHi');
            }
            define('APPLICATION_VERSION', $vVersion);
        }

        // Run standard class constructor
        parent::__construct(APPLICATION_ENV, $appCfg);
    }

    /**
     * Set bootstrap path/class
     *
     * @param  string $path
     * @param  string $class
     * @throws Zend_Application_Exception
     * @return Zend_Application
     */
    public function setBootstrap($path, $class = null)
    {
        // If we're working with Composer - we need to attempt to load given class
        if ((class_exists('Composer\Autoload\ClassLoader', false)) &&
            ($class === null) &&
            (class_exists($path, true))
        ) {
            return (parent::setBootstrap($path, $path));
        }
        return (parent::setBootstrap($path, $class));
    }

}
