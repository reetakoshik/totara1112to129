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
 * This file is the admin frontend to execute all the checks available
 * in the environment.xml file. It includes database, php and
 * php_extensions. Also, it's possible to update the xml file
 * from moodle.org be able to check more and more versions.
 *
 * @package    core
 * @subpackage admin
 * @copyright  2006 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/environmentlib.php');

// Totara: this must be included first so that the $version variable does not get overridden.
require($CFG->dirroot.'/version.php');

// Parameters
$version = optional_param('version', '', PARAM_INT); // Major Totara versions only from 9 up

$extraurlparams = array();
if ($version) {
    $extraurlparams['version'] = $version;
}
admin_externalpage_setup('environment', '', $extraurlparams);

// Get current major Totara version and use it as the default.
$current_version = (int)$TOTARA->version;
if (!$version) {
    $version = $current_version;
}

// Calculate list of versions
$versions = array();
$env_versions = get_list_of_environment_versions(load_environment_xml());
//Iterate over each version, adding bigger than current
foreach ($env_versions as $env_version) {
    if (version_compare($current_version, $env_version, '>')) {
        continue;
    }
    $versions[$env_version] = $env_version;
}

// Get the results of the environment check.
list($envstatus, $environment_results) = check_totara_environment($version);

// Display the page.
$output = $PAGE->get_renderer('core', 'admin');
echo $output->environment_check_page($versions, $version, $envstatus, $environment_results);
