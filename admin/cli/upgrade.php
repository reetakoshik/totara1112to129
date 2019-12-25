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
 * This script creates config.php file and prepares database.
 *
 * This script is not intended for beginners!
 * Potential problems:
 * - su to apache account or sudo before execution
 * - not compatible with Windows platform
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Force OPcache reset if used, we do not want any stale caches
// when detecting if upgrade necessary or when running upgrade.
if (function_exists('opcache_reset') and !isset($_SERVER['REMOTE_ADDR'])) {
    opcache_reset();
}

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');       // various admin-only functions
require_once($CFG->libdir.'/upgradelib.php');     // general upgrade/install related functions
require_once($CFG->libdir.'/clilib.php');         // cli only functions
require_once($CFG->libdir.'/environmentlib.php');
require_once($CFG->dirroot.'/totara/core/db/utils.php');

// now get cli options
$lang = isset($SESSION->lang) ? $SESSION->lang : $CFG->lang;
list($options, $unrecognized) = cli_get_params(
    array(
        'non-interactive'   => false,
        'allow-unstable'    => false,
        'help'              => false,
        'lang'              => $lang
    ),
    array(
        'h' => 'help'
    )
);

if ($options['lang']) {
    $SESSION->lang = $options['lang'];
}

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Command line Totara upgrade.
Please note you must execute this script with the same uid as apache!

Site defaults may be changed via local/defaults.php.

Options:
--non-interactive     No interactive questions or confirmations
--allow-unstable      Upgrade even if the version is not marked as stable yet,
                      required in non-interactive mode.
--lang=CODE           Set preferred language for CLI output. Defaults to the
                      site language if not set. Defaults to 'en' if the lang
                      parameter is invalid or if the language pack is not
                      installed.
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/upgrade.php
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

if (empty($CFG->version)) {
    cli_error(get_string('missingconfigversion', 'debug'));
}

$version = null;
$release = null;
$branch = null;
require("$CFG->dirroot/version.php");       // defines $version, $release, $branch and $maturity
$CFG->target_release = $release;            // used during installation and upgrades

// Setup totara version variables and verify upgrade is possible,
// note that lib/setup.php does upgrade checks for 1.x/2.2.x upgrade path.
$totarainfo = totara_version_info();
if (!empty($totarainfo->totaraupgradeerror)){
    cli_error(get_string($totarainfo->totaraupgradeerror, 'totara_core', $totarainfo), 1);
}

// Totara: moodle_needs_upgrading() now checks for Totara upgrade too.
if (!moodle_needs_upgrading()) {
    cli_error(get_string('cliupgradenoneed', 'core_admin', $totarainfo->newversion), 0);
}

// Test environment first.
list($envstatus, $environment_results) = check_totara_environment();
if (!$envstatus) {
    $errors = environment_get_errors($environment_results);
    cli_heading(get_string('environment', 'admin'));
    foreach ($errors as $error) {
        list($info, $report) = $error;
        echo "!! $info !!\n$report\n\n";
    }
    exit(1);
}

// Test plugin dependencies.
$failed = array();
if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed)) {
    cli_problem(get_string('pluginscheckfailed', 'admin', array('pluginslist' => implode(', ', array_unique($failed)))));
    cli_error(get_string('pluginschecktodo', 'admin'));
}

if ($interactive) {
    echo cli_heading(get_string('databasechecking', '', $totarainfo)) . PHP_EOL;
}

// make sure we are upgrading to a stable release or display a warning
if (isset($maturity)) {
    if (($maturity < MATURITY_EVERGREEN) and !$options['allow-unstable']) {
        $maturitylevel = get_string('maturity'.$maturity, 'admin');

        if ($interactive) {
            cli_separator();
            cli_heading(get_string('notice'));
            echo get_string('maturitycorewarning', 'admin', $maturitylevel) . PHP_EOL;
            cli_separator();
        } else {
            cli_problem(get_string('maturitycorewarning', 'admin', $maturitylevel));
            cli_error(get_string('maturityallowunstable', 'admin'));
        }
    }
}

if ($interactive) {
    echo html_to_text(get_string('upgradesure', 'admin', $totarainfo))."\n";
    $prompt = get_string('cliyesnoprompt', 'admin');
    $input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
    if ($input == get_string('clianswerno', 'admin')) {
        exit(1);
    }
}

if ($totarainfo->upgradecore) {
    // Totara: this is executed when Moodle version or Totara releases changed.
    upgrade_core($version, true);
}

// unconditionally upgrade
upgrade_noncore(true);

// log in as admin - we need doanything permission when applying defaults
\core\session\manager::set_user(get_admin());

// apply all default settings, just in case do it twice to fill all defaults
admin_apply_default_settings(NULL, false);
admin_apply_default_settings(NULL, false);

echo get_string('cliupgradefinished', 'admin')."\n";
exit(0); // 0 means success
