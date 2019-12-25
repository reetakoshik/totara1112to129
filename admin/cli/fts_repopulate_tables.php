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
list($options, $unrecognized) = cli_get_params(array('help' => false, 'execute' => false, 'list' => false),
    array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// NOTE: there is no point in localising this script, admins must at least understand English.

if ($options['help'] or (!$options['execute'] and !$options['list'])) {
    $help =
        "Repopulate all data in tables used for full text searching
        
This script is intended to be used when value of \$CFG->dboptions['fts3bworkaround']
setting changes.

Options:
-h, --help            Print out this help
--list                List all FTS tables that can be repopulated
--execute             Repopulate all FTS auxiliary tables

Example:
\$sudo -u www-data /usr/bin/php admin/cli/fts_repopulate_tables.php --execute
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
    cli_writeln('Upgrade pending, repopulation of search tables is not possible now.');
    exit(1);
}

$hook = new \totara_core\hook\fts_repopulation();
$hook->execute();
$methods = $hook->get_methods();

if ($options['list']) {
    foreach($methods as $tablename => $method) {
        cli_writeln($tablename);
    }
    exit(0);
}

if ($options['execute']) {
    cli_writeln('Repopulating fts tables');
    cli_separator();
    foreach($methods as $tablename => $method) {
        cli_write($tablename . ' ...');
        call_user_func($method, $tablename);
        cli_writeln(' done.');
    }
    cli_separator();
    exit(0);
}
