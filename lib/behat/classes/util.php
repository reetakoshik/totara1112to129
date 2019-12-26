<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Utils for behat-related stuff
 *
 * @package    core
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../../testing/classes/util.php');
require_once(__DIR__ . '/behat_command.php');
require_once(__DIR__ . '/behat_config_manager.php');

require_once(__DIR__ . '/../../filelib.php');

/**
 * Init/reset utilities for Behat database and dataroot
 *
 * @package   core
 * @category  test
 * @copyright 2013 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_util extends testing_util {

    /**
     * The behat test site fullname and shortname.
     */
    const BEHATSITENAME = "Acceptance test site";

    /**
     * @var array Files to skip when dropping dataroot folder
     */
    protected static $datarootskipondrop = array('.', '..', 'lock');

    /**
     * Installs a site using $CFG->dataroot and $CFG->prefix
     * @throws coding_exception
     * @return void
     */
    public static function install_site() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/user/lib.php');
        if (!defined('BEHAT_UTIL')) {
            throw new coding_exception('This method can be only used by Behat CLI tool');
        }

        $tables = $DB->get_tables(false);
        if (!empty($tables)) {
            behat_error(BEHAT_EXITCODE_INSTALLED);
        }

        // Torara: Empty dataroot and initialise it.
        self::drop_dataroot();
        testing_initdataroot($CFG->dataroot, 'behat');
        self::reset_dataroot();

        $options = array();
        $options['adminuser'] = 'admin';
        $options['adminpass'] = 'admin';
        $options['fullname'] = self::BEHATSITENAME;
        $options['shortname'] = self::BEHATSITENAME;

        install_cli_database($options, false);

        // Undo Totara changed defaults to allow upstream testing without hacks.
        set_config('forcelogin', 0);
        set_config('guestloginbutton', 1);
        // NOTE: completion is automatically enabled since Moodle 3.1
        set_config('completionstartonenrol', 0, 'moodlecourse');
        set_config('enrol_plugins_enabled', 'manual,guest,self,cohort');
        set_config('catalogtype', 'moodle');
        set_config('preventexecpath', 0);
        set_config('enableblogs', 1);
        $DB->set_field('role', 'name', 'Manager', array('shortname' => 'manager'));
        $DB->set_field('role', 'name', 'Teacher', array('shortname' => 'editingteacher'));
        $DB->set_field('role', 'name', 'Non-editing teacher',array('shortname' => 'teacher'));
        $DB->set_field('role', 'name', 'Student', array('shortname' => 'student'));
        $DB->set_field('modules', 'visible', 1, array('name'=>'workshop'));

        // Some more Totara tricks.
        $DB->set_field('task_scheduled', 'disabled', 1, array('component' => 'tool_langimport')); // No cron lang updates in behat.

        // Totara: there is no need to save filedir files, we do not delete them in tests!

        $frontpagesummary = new admin_setting_special_frontpagedesc();
        $frontpagesummary->write_setting(self::BEHATSITENAME);

        // Update admin user info.
        $user = $DB->get_record('user', array('username' => 'admin'));
        $user->email = 'moodle@example.com';
        $user->firstname = 'Admin';
        $user->lastname = 'User';
        $user->city = 'Perth';
        $user->country = 'AU';
        user_update_user($user, false);

        // Disable email message processor.
        $DB->set_field('message_processors', 'enabled', '0', array('name' => 'email'));

        // Sets maximum debug level.
        set_config('debug', DEBUG_DEVELOPER);
        set_config('debugdisplay', 1);

        // Disable some settings that are not wanted on test sites.
        set_config('noemailever', 1);

        // Enable web cron.
        set_config('cronclionly', 0);

        // Set noreplyaddress to an example domain, as it should be valid email address and test site can be a localhost.
        set_config('noreplyaddress', 'noreply@example.com');

        // Totara: Add behat filesystem repository to eliminate problematic file uploads in behat.
        // NOTE: Repository API is a total mess, let's just insert the records directly here
        //       and allow all registered users to access the repo.
        $maxorder = $DB->get_field('repository', 'MAX(sortorder)', array());
        $typeid = $DB->insert_record('repository', (object)array('type' => 'filesystem', 'sortorder' => $maxorder + 1, 'visible' => 1));
        $instanceid = $DB->insert_record('repository_instances',
            (object)array('name' => 'behat', 'typeid' => $typeid, 'contextid' => SYSCONTEXTID, 'timecreated' => time(), 'timemodified' => time()));
        $DB->insert_record('repository_instance_config', (object)array('instanceid' => $instanceid, 'name' => 'fs_path', 'value' => 'behat'));
        $DB->insert_record('repository_instance_config', (object)array('instanceid' => $instanceid, 'name' => 'relativefiles', 'value' => '0'));
        $userrole = $DB->get_record('role', array('shortname' => 'user'));
        assign_capability('repository/filesystem:view', CAP_ALLOW, $userrole->id, SYSCONTEXTID, true);

        // Set editor autosave to high value, so as to avoid unwanted ajax.
        set_config('autosavefrequency', '604800', 'editor_atto');

        // Set noreplyaddress to an example domain, as it should be valid email address and test site can be a localhost.
        set_config('noreplyaddress', 'noreply@example.com');

        // Disable Totara registrations.
        set_config('registrationenabled', 0);
        set_config('sitetype', 'development');
        set_config('registrationcode', '');

        // Totara: purge log tables to speed up DB resets.
        $DB->delete_records('config_log');
        $DB->delete_records('log_display');
        $DB->delete_records('upgrade_log');

        // Totara: Renable site legacy site administration menu
        set_config('legacyadminsettingsmenu', 1);

        // Totara: there is no need to save filedir files, we do not delete them in tests!

        // Keeps the current version of database and dataroot.
        self::store_versions_hash();

        // Unfortunately we cannot randomise the new id numbers yet, there are still some sloppy totara tests that rely on hardcoded ids!
        $DB->get_manager()->reset_all_sequences(0, 0);

        // Stores the database contents for fast reset.
        self::store_database_state();
    }

    /**
     * Drops dataroot and remove test database tables
     * @throws coding_exception
     * @return void
     */
    public static function drop_site() {

        if (!defined('BEHAT_UTIL')) {
            throw new coding_exception('This method can be only used by Behat CLI tool');
        }

        self::drop_database(true);
        self::drop_dataroot();
    }

    /**
     * Delete files and directories under dataroot.
     */
    public static function drop_dataroot() {
        global $CFG;

        if ($CFG->behat_dataroot === $CFG->behat_dataroot_parent) {
            // It should never come here.
            throw new moodle_exception("Behat dataroot should not be same as parent behat data root.");
        }

        // As behat directory is now created under default $CFG->behat_dataroot_parent, so remove the whole dir.
        remove_dir($CFG->dataroot, false);
    }

    /**
     * Checks if $CFG->behat_wwwroot is available and using same versions for cli and web.
     *
     * @return void
     */
    public static function check_server_status() {
        global $CFG;

        $url = $CFG->behat_wwwroot . '/admin/tool/behat/tests/behat/fixtures/environment.php';

        // Get web versions used by behat site.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'BEHAT=1');
        $result = curl_exec($ch);
        $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statuscode !== 200 || empty($result) || (!$result = json_decode($result, true))) {

            behat_error (BEHAT_EXITCODE_REQUIREMENT, $CFG->behat_wwwroot . ' is not available, ensure you specified ' .
                'correct url and that the server is set up and started.' . PHP_EOL . ' More info in ' .
                behat_command::DOCS_URL . '#Running_tests' . PHP_EOL);
        }

        // Check if cli version is same as web version.
        $clienv = self::get_environment();
        if ($result != $clienv) {
            $output = 'Differences detected between cli and webserver...'.PHP_EOL;
            foreach ($result as $key => $version) {
                if ($clienv[$key] != $version) {
                    $output .= ' ' . $key . ': ' . PHP_EOL;
                    $output .= ' - web server: ' . $version . PHP_EOL;
                    $output .= ' - cli: ' . $clienv[$key] . PHP_EOL;
                }
            }
            echo $output;
            ob_flush();
        }
    }

    /**
     * Checks whether the test database and dataroot is ready
     * Stops execution if something went wrong
     * @throws coding_exception
     * @return void
     */
    protected static function test_environment_problem() {
        global $CFG, $DB;

        if (!defined('BEHAT_UTIL')) {
            throw new coding_exception('This method can be only used by Behat CLI tool');
        }

        if (!self::is_test_site()) {
            behat_error(1, 'This is not a behat test site!');
        }

        $tables = $DB->get_tables(false);
        if (empty($tables)) {
            behat_error(BEHAT_EXITCODE_INSTALL, '');
        }

        if (!self::is_test_data_updated()) {
            behat_error(BEHAT_EXITCODE_REINSTALL, 'The test environment was initialised for a different version');
        }
    }

    /**
     * Enables test mode
     *
     * It uses CFG->behat_dataroot
     *
     * Starts the test mode checking the composer installation and
     * the test environment and updating the available
     * features and steps definitions.
     *
     * Stores a file in dataroot/behat to allow Moodle to switch
     * to the test environment when using cli-server.
     * @param bool $themesuitewithallfeatures List themes to include core features.
     * @param string $tags comma separated tag, which will be given preference while distributing features in parallel run.
     * @param int $parallelruns number of parallel runs.
     * @param int $run current run.
     * @throws coding_exception
     * @return void
     */
    public static function start_test_mode($themesuitewithallfeatures = false, $tags = '', $parallelruns = 0, $run = 0) {
        global $CFG;

        if (!defined('BEHAT_UTIL')) {
            throw new coding_exception('This method can be only used by Behat CLI tool');
        }

        // Checks the behat set up and the PHP version.
        if ($errorcode = behat_command::behat_setup_problem()) {
            exit($errorcode);
        }

        // Check that test environment is correctly set up.
        self::test_environment_problem();

        // Updates all the Moodle features and steps definitions.
        behat_config_manager::update_config_file('', true, $tags, $themesuitewithallfeatures, $parallelruns, $run);

        if (self::is_test_mode_enabled()) {
            return;
        }

        $contents = '$CFG->behat_wwwroot, $CFG->behat_prefix and $CFG->behat_dataroot' .
            ' are currently used as $CFG->wwwroot, $CFG->prefix and $CFG->dataroot';
        $filepath = self::get_test_file_path();
        if (!file_put_contents($filepath, $contents)) {
            behat_error(BEHAT_EXITCODE_PERMISSIONS, 'File ' . $filepath . ' can not be created');
        }
    }

    /**
     * Returns the status of the behat test environment
     *
     * @return int Error code
     */
    public static function get_behat_status() {

        if (!defined('BEHAT_UTIL')) {
            throw new coding_exception('This method can be only used by Behat CLI tool');
        }

        // Checks the behat set up and the PHP version, returning an error code if something went wrong.
        if ($errorcode = behat_command::behat_setup_problem()) {
            return $errorcode;
        }

        // Check that test environment is correctly set up, stops execution.
        self::test_environment_problem();
    }

    /**
     * Disables test mode
     * @throws coding_exception
     * @return void
     */
    public static function stop_test_mode() {

        if (!defined('BEHAT_UTIL')) {
            throw new coding_exception('This method can be only used by Behat CLI tool');
        }

        $testenvfile = self::get_test_file_path();
        behat_config_manager::set_behat_run_config_value('behatsiteenabled', 0);

        if (!self::is_test_mode_enabled()) {
            echo "Test environment was already disabled\n";
        } else {
            if (!unlink($testenvfile)) {
                behat_error(BEHAT_EXITCODE_PERMISSIONS, 'Can not delete test environment file');
            }
        }
    }

    /**
     * Checks whether test environment is enabled or disabled
     *
     * To check is the current script is running in the test
     * environment
     *
     * @return bool
     */
    public static function is_test_mode_enabled() {

        $testenvfile = self::get_test_file_path();
        if (file_exists($testenvfile)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the path to the file which specifies if test environment is enabled
     * @return string
     */
    public final static function get_test_file_path() {
        return behat_command::get_parent_behat_dir() . '/test_environment_enabled.txt';
    }

    /**
     * Purge dataroot directory
     * @static
     * @return void
     */
    public static function reset_dataroot() {
        global $CFG;

        // Totara: Clear file status cache to make sure we know about all files.
        clearstatcache();

        parent::reset_dataroot();

        // Totara: Add behat filesystem repository to eliminate problematic file uploads in behat.
        mkdir("$CFG->dataroot/repository/behat", 02777, true);
    }

    /**
     * Reset contents of all database tables to initial values, reset caches, etc.
     */
    public static function reset_all_data() {
        // Reset database.
        self::reset_database();

        // Purge dataroot directory.
        self::reset_dataroot();

        // Purge all data from the caches. This is required for consistency between tests.
        // Any file caches that happened to be within the data root will have already been clearer (because we just deleted cache)
        // and now we will purge any other caches as well.  This must be done before the cache_factory::reset() as that
        // removes all definitions of caches and purge does not have valid caches to operate on.
        cache_helper::purge_all();
        // Reset the cache API so that it recreates it's required directories as well.
        cache_factory::reset();

        // Reset all static caches.
        accesslib_clear_all_caches(true);
        // Reset the nasty strings list used during the last test.
        nasty_strings::reset_used_strings();

        filter_manager::reset_caches();

        // Reset course and module caches.
        if (class_exists('format_base')) {
            // If file containing class is not loaded, there is no cache there anyway.
            format_base::reset_course_cache(0);
        }
        get_fast_modinfo(0, 0, true);

        // Inform data generator.
        self::get_data_generator()->reset();

        // Initialise $CFG with default values. This is needed for behat cli process, so we don't have modified
        // $CFG values from the old run. @see set_config.
        initialise_cfg();

        // Totara: make sure all browser caches are invalidated too.
        js_reset_all_caches();
        theme_reset_all_caches();

        \totara_catalog\cache_handler::reset_all_caches();
    }
}
