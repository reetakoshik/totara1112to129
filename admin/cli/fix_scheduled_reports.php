<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportbuilder
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/email_setting_schedule.php');

list($options, $unrecognized) = cli_get_params(array('help' => false, 'verbose' => false), array('h' => 'help', 'v' => 'verbose'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}


if ($options['help']) {
    $help =
        "Fixes Report Builder scheduler reports after upgrade

Options:
-h, --help            Print out this help
-v, --verbose         Output more information

Example:
\$sudo -u www-data /usr/bin/php admin/cli/fix_scheduled_reports.php
";
    echo $help;
    exit(0);
}

if ($options['verbose']) {
    cli_writeln("");
    cli_logo();
    cli_writeln("");
    cli_writeln("Fixing scheduled reports...");
}

$reportschedules = reportbuilder_get_all_scheduled_reports_without_recipients();
$cnt = 0;
if (!empty($reportschedules)) {
    // log in as admin - email_setting_schedule::_constructor does permissions check.
    \core\session\manager::set_user(get_admin());
}
foreach ($reportschedules as $reportschedule) {
    // Add userid as recipient
    $scheduleemail = new email_setting_schedule($reportschedule->id);
    $scheduleemail->set_email_settings(array(), array($reportschedule->userid), array());
    $cnt++;
}
if ($options['verbose']) {
    cli_writeln("Done. Fixed $cnt scheduled reports.");
}