<?php

class Rx_Console_Options extends Zend_Console_Getopt
{

    /**
     * Configuration option for enabling use of commands in command-line application
     */
    const CONFIG_USECOMMAND = 'useCommand';
    /**
     * List of available commands for command-line application
     *
     * @var array $_commands
     */
    protected $_commands = array();
    /**
     * Current command for command-line application
     *
     * @var string $_command
     */
    protected $_command = null;

    /**
     * Class constructor
     *
     * @param array $rules        Rules for command-line application arguments
     * @param array $argv         OPTIONAL Command-line application arguments
     * @param array $getoptConfig OPTIONAL Class configuration
     * @return Rx_Console_Options
     */
    public function __construct($rules, $argv = null, $getoptConfig = array())
    {
        // Modify object configuration to use new option
        $this->_getoptConfig[self::CONFIG_USECOMMAND] = false;
        parent::__construct($rules, $argv, $getoptConfig);
        $this->_progname = pathinfo($this->_progname, PATHINFO_BASENAME);
    }

    /**
     * Check if command-line application options parser have defined
     * any rules for command-line options
     *
     * @return boolean
     */
    public function haveRules()
    {
        return (sizeof($this->_rules) > 0);
    }

    /**
     * Check if command-line application options parser have defined
     * any rules for command-line commands
     *
     * @return boolean
     */
    public function haveCommands()
    {
        if (!$this->_getoptConfig[self::CONFIG_USECOMMAND]) {
            return (false);
        }
        return (sizeof($this->_commands) > 0);
    }

    /**
     * Configure commands for command-line applications
     *
     * @param array $commands Commands configuration
     * @return Zend_Console_Getopt      Provides a fluent interface
     */
    public function addCommands($commands)
    {
        // Implicitly enable commands usage because we can expect that they're
        // configured only when they're available
        $this->setOption(self::CONFIG_USECOMMAND, true);
        if (!is_array($commands)) {
            $commands = array();
        }
        $this->_commands = $commands;
        return ($this);
    }

    /**
     * Get command given for command-line application
     *
     * @return string|null
     */
    public function getCommand()
    {
        $this->parse();
        return ($this->_command);
    }

    /**
     * Parse command-line arguments and find both long and short
     * options.
     *
     * Also find option parameters, and remaining arguments after
     * all options have been parsed.
     *
     * @return Zend_Console_Getopt|null     Provides a fluent interface
     */
    public function parse()
    {
        if ($this->_parsed === true) {
            return null;
        }
        if ($this->_getoptConfig[self::CONFIG_USECOMMAND]) {
            $this->_command = null;
            $args = $this->_argv;
            $arg = array_shift($args);
            if ((substr($arg, 0, 1) != '-') && (array_key_exists($arg, $this->_commands))) {
                $this->_command = $arg;
            }
            if ($this->_command !== null) {
                array_shift($this->_argv);
            }
        }
        return (parent::parse());
    }

    /**
     * Return a useful option reference, formatted for display in an
     * error message.
     *
     * Note that this usage information is provided in most Exceptions
     * generated by this class.
     *
     * @return string
     */
    public function getUsageMessage()
    {
        $usage = parent::getUsageMessage();
        if ($this->_getoptConfig[self::CONFIG_USECOMMAND]) {
            $lines = explode("\n", $usage);
            $title = array_shift($lines);
            $t = explode('[', $title, 2);
            $title = array(trim(array_shift($t)), 'command', '[' . array_shift($t));
            $title = join(' ', $title);
            $sz = 1;
            foreach ($this->_commands as $command => $help) {
                $sz = max($sz, strlen($command));
            }
            $commands = array('', 'Available commands:');
            foreach ($this->_commands as $command => $help) {
                $commands[] = sprintf('%-' . $sz . 's %s', $command, $help);
            }
            $commands[] = '';
            $commands[] = 'Available options:';
            $usage = array_merge(array($title), $commands, $lines);
            $usage = join("\n", $usage);
        }
        return ($usage);
    }

}