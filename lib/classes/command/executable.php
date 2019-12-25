<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core
 */

namespace core\command;

defined('MOODLE_INTERNAL') || die();

/**
 * Executable command class.
 *
 * Implements the Serializable interface just to block serialisation of the executable class.
 * It would be theoretically possible to execute malicious code by corrupting a serialised executable.
 * For this reason we block it - don't ever change it!
 *
 * This class is final, you shouldn't need to extend it, if you do, and you have a valid reason please
 * request that we remove the final keyword.
 * Once the API has proven itself and is considered stable we would consider removing the final keyword.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core
 */
final class executable implements \Serializable {

    /**
     * @var string containing what should be the full pathname for the file that will be executed.
     */
    private $pathname;

    /**
     * @var \core\command\argument[]
     */
    private $arguments = array();

    /**
     * @var string the default operator between key/value pairs for this command instance.
     */
    private $defaultoperator;

    /**
     * @var null|array After execution, this will contain output returned by executed program,
     * with one line per element.
     */
    private $output = null;

    /**
     * @var null|int After execution, this will contain the exit code returned by the executed program.
     */
    private $return_status = null;

    /**
     * @var bool - true if we'd like to redirect STDERR to STDOUT instead, making it available
     * in the $output variable and leaving STDERR clean.
     */
    private $stderr2stdout = false;

    /**
     * @var bool - true if this originates from a web request. False if originates from CLI.
     */
    private static $iswebrequest;

    /**
     * @var bool - true if we're on a Windows OS.
     */
    private static $iswindows;

    /**
     * @var bool - true if pcntl functions are allowed and available to be used.
     */
    private static $canusepcntl;

    /**
     * @param string $pathname - path to the executable being run in the command.
     * @param string $defaultoperator - to go between arguments in key/value pairs. Is not used when adding switches
     *   or values on their own. Allows an empty string.
     *   It is not escaped. Never allow user input for this value.
     * @throws exception for any action that is not allowed.
     */
    public function __construct($pathname, $defaultoperator = ' ') {
        global $CFG;

        $pathname = trim($pathname);
        if (empty($pathname)) {
            throw new exception('Action not allowed');
        }

        if (($pathname === 'php') and (self::can_use_pcntl())) {
            // PCNTL requires a full pathname.
            $pathname = $CFG->pcntl_phpclipath;
        }

        if (!$this->check_path_against_whitelist($pathname)) {
            throw new exception('Action not allowed');
        }

        // The pathname must be quoted due to spaces in folder names.
        $this->pathname = escapeshellarg($pathname);
        $this->defaultoperator = $defaultoperator;
    }

    /**
     * @param string $key It is not escaped. Never allow user input for this value.
     * @param string $value
     * @param null|string $operator To go between the key and value when the command is built. If left
     *   as null, the $operator supplied to the __construct function will be used.
     *   It is not escaped. Never allow user input for this value.
     * @param string|int $paramtype Determines valid character patterns for the value only.
     *   See phpdocs for \core\command\argument::set_value().
     * @param bool $escape_ifnopcntl Commands not done via pcntl are escaped by default, set this to true if you don't
     *   want values escaped that appear on the command line.
     *   (This is risky, only do this if you have to and are certain the value is safe).
     * @return executable
     */
    public function add_argument($key, $value, $paramtype = argument::REGEX_ALPHANUM_UNDERSCORE, $operator = null,
                                 $escape_ifnopcntl = true) {
        if (empty($paramtype)) {
            // An empty param type is invalid, but could be supplied as a convenient way of not having to
            // explicitly use the default value when just wanting to overwrite the 4th argument ($operator).
            $paramtype = argument::REGEX_ALPHANUM_UNDERSCORE;
        }

        if (!isset($operator)) {
            $operator = $this->defaultoperator;
        }

        $argument = new argument();
        $this->add($argument->set_key($key)
                            ->set_operator($operator)
                            ->set_value($value, $paramtype, $escape_ifnopcntl));

        return $this;
    }

    /**
     * No validation is performed on this. The supplied value should never be user input.
     *
     * @param string $switch
     * @return executable
     */
    public function add_switch($switch) {
        $argument = new argument();
        $this->add($argument->set_key($switch));

        return $this;
    }

    /**
     * See phpdocs for \core\command\argument::set_value() for more information.
     *
     * @param string $value
     * @param string $paramtype
     * @param bool $escape_ifnopcntl
     * @return executable
     */
    public function add_value($value, $paramtype = argument::REGEX_ALPHANUM_UNDERSCORE, $escape_ifnopcntl = true) {

        if (empty($paramtype)) {
            // An empty param type is invalid, but could be supplied as a convenient way of not having to
            // explicitly use the default value when just wanting to overwrite the 3rd argument ($operator).
            $paramtype = argument::REGEX_ALPHANUM_UNDERSCORE;
        }

        $argument = new argument();
        $this->add($argument->set_value($value, $paramtype, $escape_ifnopcntl));

        return $this;
    }

    /**
     * Adds the argument object to the arguments array.
     *
     * @param argument $argument
     * @throws exception if the given argument is not in fact an argument.
     */
    private function add($argument) {
        if ($argument instanceof argument) {
            $this->arguments[] = $argument;
            return;
        }

        throw new exception('Invalid object');
    }

    /**
     * Redirects STDERR to STDOUT, STDERR will be kept clean and those contents are
     * available via get_output() instead.
     *
     * @param bool $redirect - True to redirect or false to turn off redirection.
     */
    public function redirect_stderr_to_stdout($redirect = true) {
        $this->stderr2stdout = $redirect;
    }

    /**
     * Run the command. Will run the command via a php script containing pcntl_exec() if possible,
     * otherwise via the shell.
     *
     * Use this function to execute unless you have a specific need for the other functions such as
     * passthru.  Only this function currently gets run via pcntl (when available).
     *
     * @return executable
     */
    public function execute() {
        $this->output = array();
        $this->return_status = null;

        if (self::can_use_pcntl()) {
            $this->pcntl_execute();
        } else {
            $command = $this->get_command();
            exec($command, $this->output, $this->return_status);
        }

        return $this;
    }

    /**
     * Runs the built-in php function passthru().
     *
     * @return executable
     */
    public function passthru() {
        $this->output = array();
        $this->return_status = null;

        $command = $this->get_command();
        passthru($command, $this->return_status);

        return $this;
    }

    /**
     * Runs the built-in php function popen().
     *
     * @param string $mode See {@link http://php.net/manual/en/function.popen.php}
     * @return resource
     */
    public function popen($mode) {
        $this->output = array();
        $this->return_status = null;

        $command = $this->get_command();
        return popen($command, $mode);
    }

    /**
     * Runs the built-in php function proc_open().
     *
     * Please refer to {@link http://php.net/manual/en/function.proc-open.php} for information on the arguments.
     *
     * @param array $descriptorspec
     * @param array &$pipes Passed by reference.
     * @param string $cwd
     * @param array $env
     * @param array $other_options
     * @return resource
     */
    public function proc_open($descriptorspec, &$pipes, $cwd = null, $env = null, $other_options = null) {
        $this->output = array();
        $this->return_status = null;

        $command = $this->get_command();
        return proc_open($command, $descriptorspec, $pipes, $cwd, $env, $other_options);
    }

    /**
     * Get the output from the executed command. Each element of the array contains a line of output.
     *
     * @return string[]|null
     */
    public function get_output() {
        return $this->output;
    }

    /**
     * Get the exit code returned following execution of a command.
     *
     * @return int|null
     */
    public function get_return_status() {
        return $this->return_status;
    }

    /**
     * Execute the command using pcntl_exec().
     *
     * Make sure you only run this if true is returned by self::can_use_pcntl().
     *
     * @return executable
     */
    private function pcntl_execute() {
        global $CFG;

        $phpfile = $this->create_pcntl_file();

        exec($CFG->pcntl_phpclipath . ' ' . $phpfile, $output, $return_var);

        // Delete the pcntl file after use.
        unlink($phpfile);

        $this->output = $output;
        $this->return_status = $return_var;

        return $this;
    }

    /**
     * Create a php file that will run the pcntl_exec command.
     *
     * @return string The filename of the php file created for executing a command via pcntl.
     */
    private function create_pcntl_file() {
        $argarray = array();
        foreach ($this->arguments as $argument) {
            $argarray = array_merge($argarray, $argument->get_argument_array());
        }

        $phpcode = "<?php\n";
        if ($this->stderr2stdout) {
            $phpcode .= "fclose(STDERR);\n\$STDERR = fopen('php://stdout', 'w');\n";
        }
        $phpcode .= 'pcntl_exec(' . $this->pathname . ', ' . var_export($argarray, true) . ');';

        $phpfile = make_temp_directory('pcntl') . '/' . sha1($phpcode) . '.php';

        file_put_contents($phpfile, $phpcode);

        return $phpfile;
    }

    /**
     * Get the full command that will be run on the command line.
     *
     * @return string
     */
    private function get_command() {

        $commandstring = $this->pathname;
        foreach($this->arguments as $argument) {
                $commandstring .= ' ' . $argument->get_argument_string();
        }

        if ($this->stderr2stdout) {
            $commandstring .= ' 2>&1';
        }

        return $commandstring;
    }

    /**
     * Check the pathname against the whitelist.
     *
     * @return bool true if pathname has been found to be valid based on whitelist.
     */
    private function check_path_against_whitelist($pathname) {
        $whitelist = $this->get_whitelist();
        if (isset($whitelist[$pathname])) {
            if (self::is_web_request()) {
                // If a web request, we'll return the boolean value assigned to this pathname.
                return $whitelist[$pathname];
            } else {
                // If on the cli, then as long as it's in the whitelist it's fine.
                return true;
            }
        }

        return false;
    }

    /**
     * Get the whitelist for allowed pathnames.
     *
     * The whitelist is not loaded into a static cache as updates could occur between execution of commands.
     *
     * @return array containing pathnames in keys and true/false in values, where true means can be
     *   used from web request or cli, while false means cli only.
     * @throws exception
     */
    private function get_whitelist() {
        global $CFG;

        if (during_initial_install()) {
            throw new exception('Action not allowed during install');
        }

        $whitelist = array();

        // For pdf exports with wkhtmltopdf.
        if (isset($CFG->pathtowkhtmltopdf)) {
            $whitelist[$CFG->pathtowkhtmltopdf] = true;
        }

        // For TeX processing.
        $whitelist[get_config('filter_tex', 'pathlatex')] = true;
        $whitelist[get_config('filter_tex', 'pathdvips')] = true;
        $whitelist[get_config('filter_tex', 'pathdvisvgm')] = true;
        $whitelist[get_config('filter_tex', 'pathconvert')] = true;

        // The algebra2tex perl script.
        $whitelist[$CFG->dirroot . '/filter/algebra/algebra2tex.pl'] = true;

        // There are several ways that mimetex could be found.
        $whitelist[get_config('filter_tex', 'pathmimetex')] = true;

        $whitelist[$CFG->dirroot . '/filter/tex/mimetex.exe'] = true;
        $whitelist[$CFG->dirroot . '/filter/tex/mimetex'] = true;
        $whitelist[$CFG->dirroot . '/filter/tex/mimetex.linux'] = true;
        $whitelist[$CFG->dirroot . '/filter/tex/mimetex.darwin'] = true;
        $whitelist[$CFG->dirroot . '/filter/tex/mimetex.freebsd'] = true;

        // GhostScript, used for editing assignment pdf submissions.
        if (isset($CFG->pathtogs)) {
            $whitelist[$CFG->pathtogs] = true;
        }

        // For Clam AV (anti-virus program).
        // MDL-50887 moved antivirus scanning to a plugin; so must read plugin
        // config settings for executable path instead of the old $CFG->pathtoclam.
        $antiviruspath = \get_config('antivirus_clamav', 'pathtoclam');
        if (!empty($antiviruspath)) {
            $whitelist[$antiviruspath] = true;
        }

        // For the function get_directory_size().
        if (isset($CFG->pathtodu)) {
            $whitelist[$CFG->pathtodu] = true;
        }

        // For XHProf, Dot is used to draw a graph.
        if (isset($CFG->pathtodot)) {
            $whitelist[$CFG->pathtodot] = true;
        }

        // This is one of the more dangerous commands for us. Let's try to keep it as cli only.
        $whitelist['php'] = false;

        if (isset($CFG->pcntl_phpclipath)) {
            // This is not added to the whitelist for running just any pcntl command, it's for when the command
            // you want to ultimately run is 'php' - running commands via pcntl requires a full path, not just 'php'.
            $whitelist[$CFG->pcntl_phpclipath] = false;
        }

        // Merge any third party pathnames from the config file.
        // If any of the pathnames match what has been added already,
        // the third party true/false values will override them.
        if (!empty($CFG->thirdpartyexeclist)) {
            $whitelist = array_merge($whitelist, $CFG->thirdpartyexeclist);
        }

        // Now we iterate through the whitelist to remove pathnames that are definitely invalid.
        $finalwhitelist = array();
        foreach ($whitelist as $key => $allowedonweb) {

            // We trim the item because we want to make sure we're checking an actual pathname,
            // not just spaces.
            $listitem_path = trim($key);
            if (empty($listitem_path) or !is_string($listitem_path)) {
                // We can't have empty pathnames. If it's not a string, that could be due to an
                // incorrectly configured $CFG->thirdpartyexeclist or something added incorrectly
                // above. It's certainly not a pathname, so just drop it.
                continue;
            }

            $finalwhitelist[$listitem_path] = $allowedonweb;
        }

        return $finalwhitelist;
    }

    /**
     * Check if PCNTL functions are allowed and available.
     *
     * @param bool $forcerecheck Set to true to ignore static cache and perform actual check.
     * @return bool True if PCNTL functions can be used.
     */
    public static function can_use_pcntl($forcerecheck = false) {
        global $CFG;

        if ($forcerecheck or !isset(self::$canusepcntl)) {
            if (empty($CFG->pcntl_phpclipath)) {
                self::$canusepcntl = false;
            } else if (self::is_windows()) {
                // The pcntl_exec is not available on Windows.
                self::$canusepcntl = false;
            } else {
                // This should give us an exit code of 0 if all was successful and pcntl is available.
                // We need to run this against the configured version of PHP, we can't just check the environment we are running
                // in as that is not necessarily the environment that will be used.
                exec($CFG->pcntl_phpclipath . ' -r \'(extension_loaded("pcntl") && function_exists("pcntl_exec")) || exit(1);\'', $output, $exitcode);

                if ($exitcode === 0) {
                    self::$canusepcntl = true;
                } else {
                    self::$canusepcntl = false;
                }
            }
        }

        return self::$canusepcntl;
    }

    /**
     * Check if this request originated from the web or the CLI.
     *
     * @param bool $forcerecheck Set to true to ignore static cache and perform actual check.
     * @return bool True if this request originated from the web.
     */
    private static function is_web_request($forcerecheck = false) {

        if ($forcerecheck or !isset(self::$iswebrequest)) {
            if (defined('CLI_SCRIPT') and (CLI_SCRIPT)) {
                self::$iswebrequest = false;
            } else {
                self::$iswebrequest = true;
            }
        }

        return self::$iswebrequest;
    }

    /**
     * Check if we are on a Windows OS.
     *
     * @param bool $forcerecheck Set to true to ignore static cache and perform actual check.
     * @return bool True if we are on Windows.
     */
    public static function is_windows($forcerecheck = false) {

        if ($forcerecheck or !isset(self::$iswindows)) {
            if (stripos(PHP_OS, "WIN") === 0) {
                self::$iswindows = true;
            } else {
                self::$iswindows = false;
            }
        }

        return self::$iswindows;
    }

    /**
     * Instances of this class should never need to be serialized. Preventing it so that it can't be exploited.
     * @throws exception
     */
    public function serialize() {
        throw new exception('Operation prohibited. You cannot serialize an executable instance.');
    }

    /**
     * Instances of this class should never need to be serialized. Preventing it so that it can't be exploited.
     * @throws exception
     */
    public function unserialize($serialized) {
        throw new exception('Operation prohibited: You cannot serialize an executable instance.');
    }
}