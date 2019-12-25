<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

define('MOODLE_DEFAULT_TAG', 'v3.3.7'); // The upstream release that Totara 10 and 11 are based on.

list($options, $unrecognized) = cli_get_params(
    array(
        'run'    => false,
        'list'   => false,
        'diffupstream' => false,
        'help'    => false
    ),
    array(
        'h' => 'help'
    )
);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!$options['run'] and !$options['list'] and !$options['diffupstream']) {
    $help =
        "Bump up all Totara plugin versions to today's date

Options:
-h, --help            Print out this help
--list                List all Totara plugins
--diffupstream        List differences from upstream
--run                 Bump up all Totara plugin versions and requires

NOTE: you need to have git and 'releasemoodle' remote to use this script
";

    echo $help;
    exit(0);
}

$output = null;
exec('git --version', $output, $code);
if ($code !== 0) {
    echo $output;
    cli_error('Error executing git');
}

$output = null;
exec('git remote', $output, $code);
if ($code !== 0) {
    echo $output;
    cli_error('Error executing git');
}
if (!in_array('releasemoodle', $output)) {
    cli_error('Branch \'releasemoodle\' does not exist, cannot continue');
}

list($moodleplugins, $totaraplugins) = dev_get_totara_and_moodle_plugins();

if ($options['diffupstream']) {
    cli_heading('List of changed upstream plugin versions');

    $exitcode = 0;

    foreach ($moodleplugins as $component => $fulldir) {

        $tag = dev_get_plugin_backported($fulldir);
        if (!$tag) {
            $tag = MOODLE_DEFAULT_TAG;
        }

        $upstreamversion = (string)dev_get_plugin_version_upstream($fulldir, $tag);
        $ourversion = (string)dev_get_plugin_version($fulldir);

        if ($upstreamversion === $ourversion) {
            if (!file_exists("$fulldir/db/totara_postupgrade.php")) {
                continue;
            }
            $error = 'error, missing .01 bump (postupgrade present)';
            $exitcode = 1;

        } else {
            $error = 'error';
            if ($ourversion > $upstreamversion and $ourversion < floor($upstreamversion) + 1) {
                $error = 'looks ok';
            } else {
                $exitcode = 1;
            }
            if (file_exists("$fulldir/db/totara_postupgrade.php")) {
                $error .= ' (postupgrade present)';
            }
        }

        cli_writeln(str_pad($component, 40, ' ', STR_PAD_RIGHT) . ' ' . $upstreamversion . ' ==> ' . $ourversion . ' ' . $error);
    }
    exit($exitcode);
}

cli_heading('List of ' . count($totaraplugins) . ' Totara plugins');
$today = date('Ymd') . '00';
$requirement = dev_get_requires_version();
$error = false;
$todo = array();
foreach ($totaraplugins as $component => $fulldir) {
    $version = dev_get_plugin_version($fulldir);
    if ($version > $today) {
        cli_writeln(str_pad($component, 40, ' ', STR_PAD_RIGHT) . ' ' . $version . ' ERROR!');
        $error = true;
    } else {
        cli_writeln(str_pad($component, 40, ' ', STR_PAD_RIGHT) . ' ' . $version);
        $todo[] = array($component, $fulldir, $version);
    }
}

if ($error) {
    cli_error('Cannot bump versions, please check plugins with ERROR flag');
}

if (!$options['run']) {
    die;
}

if (dev_get_maturity() == MATURITY_STABLE) {
    cli_error('Bumping up all Totara plugin versions cannot be done in stable branches!!!');
}

$updated = array();
foreach ($todo as $data) {
    list($component, $fulldir, $version) = $data;
    $file = "$fulldir/version.php";
    $content = file_get_contents($file);
    $oldcontent = $content;
    if (preg_match('/(->version\s*=\s*)\'?[0-9\.]+\'?/', $content, $matches)) {
        $content = str_replace($matches[0], $matches[1] . $today, $content);
    }
    if (preg_match('/(->requires\s*=\s*)\'?[0-9\.]+\'?/', $content, $matches)) {
        $content = str_replace($matches[0], $matches[1] . $requirement, $content);
    }
    if ($oldcontent !== $content) {
        file_put_contents($file, $content);
        $updated[] = $component;
    }
}
cli_writeln('');
if (!$updated) {
    cli_heading("All plugins are already up-to-date");
} else {
    cli_heading("Updated ". count($updated) . " Totara plugins to $today with $requirement requirement");
    foreach ($updated as $component) {
        cli_writeln($component);
    }
}
die;


function dev_get_plugin_version($fulldir) {
    $plugin = new stdClass();
    $plugin->version = null;
    $module = $plugin;
    include($fulldir.'/version.php');

    return $plugin->version;
}

function dev_get_plugin_backported($fulldir) {
    $plugin = new stdClass();
    $plugin->backported = null;
    $module = $plugin;
    include($fulldir.'/version.php');

    return $plugin->backported;
}

function dev_get_plugin_version_upstream($fulldir, $tag = MOODLE_DEFAULT_TAG) {
    $plugin = new stdClass();
    $plugin->version = null;
    $module = $plugin;

    $versioncontent = dev_get_upstream_file_content("$fulldir/version.php", $tag);
    if ($versioncontent === false) {
        return null;
    }
    $versioncontent = str_replace('<?php', '', $versioncontent);
    try {
        eval($versioncontent);
    } catch (Throwable $t) {
        cli_writeln('Error parsing file at ' . $fulldir . '/version.php');
        var_dump($versioncontent);
        throw $t;
    }

    return $plugin->version;
}

function dev_get_totara_and_moodle_plugins() {
    $totaraplugins = array();
    $moodleplugins = array();
    $types = core_component::get_plugin_types();
    foreach ($types as $type => $unused) {
        $plugins = core_component::get_plugin_list($type);
        foreach ($plugins as $name => $fulldir) {
            if (dev_is_moodle_plugin($type, $name, $fulldir)) {
                $moodleplugins[$type . '_' . $name] = $fulldir;
                continue;
            }
            $totaraplugins[$type . '_' . $name] = $fulldir;
        }
    }
    return array($moodleplugins, $totaraplugins);
}

function dev_is_moodle_plugin($type, $name, $fulldir) {
    if ($type === 'totara') {
        return false;
    }
    if (strpos($name, 'totara') !== false) {
        return false;
    }

    return dev_is_upstream_file("$fulldir/version.php");
}

/**
 * Get current Totara version from config.php.
 *
 * @return string
 */
function dev_get_totara_version() {
    global $CFG;
    $versionfile = $CFG->dirroot . '/version.php';
    $TOTARA = null;
    include($versionfile);
    return $TOTARA->version;
}

/**
 * Get current main version from config.php for 'requires',
 * the decimals are omitted.
 *
 * @return int
 */
function dev_get_requires_version() {
    global $CFG;
    $versionfile = $CFG->dirroot . '/version.php';
    $version = null;
    include($versionfile);
    return (int)floor($version);
}

/**
 * Get maturity
 *
 * @return int
 */
function dev_get_maturity() {
    global $CFG;
    $versionfile = $CFG->dirroot . '/version.php';
    $maturity = null;
    include($versionfile);
    return $maturity;
}

/**
 * Is the given file part of upstream Moodle?
 *
 * @param string $file
 * @param string $tag optional upstream release tag
 * @return bool
 */
function dev_is_upstream_file($file, $tag = MOODLE_DEFAULT_TAG) {
    global $CFG;

    $cwd = getcwd();
    chdir($CFG->dirroot);
    $file = substr($file, strlen($CFG->dirroot) +1);
    exec("git cat-file -e {$tag}:{$file} 2>/dev/null", $output, $status);
    chdir($cwd);
    return ($status === 0);
}

/**
 * Get content of upstream file
 *
 * @param string $file
 * @param string $tag optional upstream release tag
 * @return string|false
 */
function dev_get_upstream_file_content($file, $tag = MOODLE_DEFAULT_TAG) {
    global $CFG;

    $cwd = getcwd();
    chdir($CFG->dirroot);
    $file = substr($file, strlen($CFG->dirroot) +1);
    exec("git cat-file -p {$tag}:{$file}", $output, $status);
    chdir($cwd);
    if ($status !== 0) {
        return false;
    }
    return implode("\n", $output);
}
