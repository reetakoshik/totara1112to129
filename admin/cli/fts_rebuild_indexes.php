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
list($options, $unrecognized) = cli_get_params(array('help' => false, 'execute' => false),
    array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// NOTE: there is no point in localising this script, admins must at least understand English.

if ($options['help'] or !$options['execute']) {
    $help =
        "Rebuild all full text search indexes

Totara full text search supports one language only. If the value of
\$CFG->dboptions['ftslanguage'] changes in config.php then this script
must be executed to rebuild all full text search indexes.

Options:
-h, --help            Print out this help
--execute             Rebuild all full text search indexes

Example:
\$sudo -u www-data /usr/bin/php admin/cli/fts_rebuild_indexes.php --execute
";

    echo $help;
    if ($options['help']) {
        exit(0);
    }
    exit(1);
}

if (empty($CFG->version)) {
    cli_writeln('Database is not yet installed.');
    exit(1);
}

if (moodle_needs_upgrading()) {
    cli_writeln('Upgrade pending, reindexing is not possible now.');
    exit(1);
}

$dbmanager = $DB->get_manager();

// If ftsaccentsensitivity has changed we need to update the DB.
if (isset($CFG->dboptions['ftsaccentsensitivity']) && $CFG->dboptions['ftsaccentsensitivity'] !== 'dbdefault') {
    $value = (bool) $CFG->dboptions['ftsaccentsensitivity'];
    $dbmanager->fts_change_accent_sensitivity($value);
}

$schema = $dbmanager->get_install_xml_schema();

$errorfound = false;
$result = $dbmanager->fts_rebuild_indexes($schema);

foreach ($result as $r) {
    cli_separator();
    cli_write("{$r->table}.{$r->column} : ");
    if ($r->success) {
        cli_writeln('success');
    } else {
        $errorfound = true;
        cli_writeln('error');
        cli_writeln('');
        cli_writeln('  error: ' . $r->error);
        cli_writeln('  debuginfo: ' . $r->debuginfo);
    }
}
cli_separator();

if ($errorfound) {
    cli_writeln('Error rebuilding full text search indexes.');
    exit(1);
} else {
    cli_writeln('All full text search indexes were rebuilt successfully.');
    exit(0);
}
