<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package core
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $params) = cli_get_params(
    array(
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

$help = "
Perform 'ANALYZE TABLE' query (or equivalent) to the specified tables.
Use this script only when the server is not under heavy load.

Usage:
  php analyze_table.php [-h] table1,table2,...

Options:
  -h, --help      Print out this help

Parameters:
  table ...       Specify table name(s) without prefix to which 'ANALYZE TABLE' is called

Example:
  \$ php analyze_table.php context,context_map
     .. OR ..
  \$ php analyze_table.php context context_map

";

if (!empty($options['help'])) {
    echo $help;
    exit(1);
}

// Normalise command-line parameters i.e. table names.
$params = array_unique(array_reduce($params, function ($accumulation, $current) {
    return array_merge($accumulation, explode(',', $current));
}, array()));

if (empty($params)) {
    cli_error("No tables specified. Run `php analyze_table.php -h` for more information.", 1);
}

// Cut out non-existing table names.
$dbtables = $DB->get_tables(false);
$tablenames = array_intersect($params, $dbtables);

if (empty($tablenames)) {
    cli_error("No tables to analyze", 2);
}

$dbfamily = $DB->get_dbfamily();
if (!in_array($dbfamily, ['postgres', 'mysql', 'mssql'])) {
    cli_error("Unsupported database family: {$dbfamily}", 3);
}

$starttime = microtime(true);
foreach ($tablenames as $tablename) {
    cli_writeln("Analyzing {$tablename} ...");
    if ($dbfamily === 'postgres') {
        $DB->execute("ANALYZE {{$tablename}}");
    } else if ($dbfamily === 'mysql') {
        $DB->execute("ANALYZE TABLE {{$tablename}}");
    } else if ($dbfamily === 'mssql') {
        $DB->execute("UPDATE STATISTICS {{$tablename}}");
    }
}

cli_writeln("Analysis completed.");
$difftime = round(microtime(true) - $starttime, 5);
cli_writeln("Execution took {$difftime} seconds.");
exit(0);
