<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package core
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help'=>false),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// NOTE: there is point in localising this one off scripts, admins must at least understand English.

if ($options['help']) {
    $help =
"Delete all automated backup files stored in all course areas.

This is useful especially when switching from course storage to external backup directory
or when automated backups are disabled.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/delete_all_automated_backups.php
";

    echo $help;
    die;
}

echo "Are you sure you really want to delete all automated backups in all course areas?\n";
$prompt = get_string('cliyesnoprompt', 'admin');
$input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
if ($input == get_string('clianswerno', 'admin')) {
    exit(1);
}

$fs = get_file_storage();

$sql = "SELECT DISTINCT contextid AS id
          FROM {files}
         WHERE component = 'backup' AND filearea = 'automated'";
$contextids = $DB->get_records_sql($sql);
$count = 0;
foreach ($contextids as $contextid => $unused) {
    $fs->delete_area_files($contextid, 'backup', 'automated');
    $count++;
}

if ($count) {
    echo "Automated backups were deleted in $count areas\n";
} else {
    echo "No automated backup areas found\n";
}

exit(0);