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
 * MySQL table row compression tool tool.
 *
 * @package   core
 * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/clilib.php');

if ($DB->get_dbfamily() !== 'mysql') {
    cli_error('This script is used for MySQL and MariaDB databases only.');
}

/** @var mysqli_native_moodle_database $DB */

// Totara: make sure the database is configured properly, they may run this before the upgrade.
$engine = $DB->get_dbengine();
if ($engine !== 'InnoDB' and $engine !== 'XtraDB') {
    cli_error("Error: you must configure MySQL server to use InnoDB or XtraDB engine");
}
if ($DB->get_file_format() !== 'Barracuda') {
    cli_error("Error: you must configure MySQL server to use Barracuda file format");
}
if (!$DB->is_file_per_table_enabled()) {
    cli_error("Error: you must configure MySQL server to use one file per table");
}
if (!$DB->is_large_prefix_enabled()) {
    cli_error("Error: you must configure MySQL server to use large prefix");
}


list($options, $unrecognized) = cli_get_params(
    array('help' => false, 'info' => false, 'list' => false, 'fix' => false, 'showsql' => false),
    array('h' => 'help', 'i' => 'info', 'l' => 'list', 'a' => 'listall', 'f' => 'fix', 's' => 'showsql')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
    "Script for detection of row format problems in MySQL/MariaDB tables.

In older versions of InnoDB the database might have been using legacy
Antelope file format or legacy row formats which have major restrictions
on column and index row sizes.

Use this script to detect and fix database tables with potential data
overflow problems.

It is strongly recommended to stop the web server before the conversion.
This script should be executed before upgrade.

Options:
-i, --info            Show database information
-l, --list            List tables that need to be fixed
-a, --listall         List format of all tables
-f, --fix             Attempt to fix all tables
-s, --showsql         Print SQL statements for fixing of tables
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/mysql_compressed_rows.php -l
";

if (empty($options['info']) and empty($options['list']) and empty($options['fix']) and empty($options['showsql']) and empty($options['listall'])) {
    echo $help;
    exit(0);
}


/** @var mysql_sql_generator $generator */
$generator = $DB->get_manager()->generator;

$info = $DB->get_server_info();
$prefix = $DB->get_prefix();

if (!empty($options['info'])) {
    echo "Database driver:       " . get_class($DB) . "\n";
    echo "Database version:      " . $info['description'] . "\n";
    echo "Database name:         " . $CFG->dbname. "\n";
    echo "Database engine:       " . $DB->get_dbengine() . "\n";
    echo "Database collation:    " . $DB->get_dbcollation() . "\n";

    exit(0);
}

$fixcompressedtables = array();
$fixdynamictables = array();

foreach ($DB->get_tables(false) as $table) {
    $columns = $DB->get_columns($table, false);
    $size = $generator->guess_antelope_row_size($columns);
    $format = $DB->get_row_format($table);

    if ($format === 'Compressed') {
        continue;
    }
    if ($format === 'Dynamic') {
        if ($size > $generator::ANTELOPE_MAX_ROW_SIZE) {
            $fixcompressedtables[$table] = $format;
        }
        continue;
    }
    if ($size > $generator::ANTELOPE_MAX_ROW_SIZE) {
        $fixcompressedtables[$table] = $format;
        continue;
    }
    $fixdynamictables[$table] = $format;
}

if (!empty($options['list'])) {
    if (!$fixcompressedtables and !$fixdynamictables) {
        exit(0);
    }

    foreach ($fixcompressedtables as $table => $oldformat) {
        echo str_pad($prefix . $table, 50, ' ', STR_PAD_RIGHT);
        echo $oldformat;
        echo ' --> Compressed';
        echo "\n";
    }
    foreach ($fixdynamictables as $table => $oldformat) {
        echo str_pad($prefix . $table, 50, ' ', STR_PAD_RIGHT);
        echo $oldformat;
        echo ' --> Dynamic';
        echo "\n";
    }
    exit(1);
}

if (!empty($options['listall'])) {
    foreach ($DB->get_tables(false) as $table) {
        echo str_pad($prefix . $table, 50, ' ', STR_PAD_RIGHT);
        echo $DB->get_row_format($table);
        echo "\n";
    }
}

if (!empty($options['fix'])) {
    if (!$fixcompressedtables and !$fixdynamictables) {
        echo "No changes necessary\n";
        exit(0);
    }

    foreach ($fixcompressedtables as $table => $oldformat) {
        echo str_pad($prefix . $table, 50, ' ', STR_PAD_RIGHT) . $oldformat . ' --> ';
        $DB->change_database_structure("ALTER TABLE {$prefix}$table ROW_FORMAT=Compressed");
        echo "Compressed\n";
    }
    foreach ($fixdynamictables as $table => $oldformat) {
        echo str_pad($prefix . $table, 50, ' ', STR_PAD_RIGHT) . $oldformat . ' --> ';
        $DB->change_database_structure("ALTER TABLE {$prefix}$table ROW_FORMAT=Dynamic");
        echo "Dynamic\n";
    }

    exit(0);
}

if (!empty($options['showsql'])) {
    if (!$fixcompressedtables and !$fixdynamictables) {
        echo "--No changes necessary\n";
        exit(0);
    }

    echo "--Copy the following SQL statements and execute them in your database:\n\n";
    echo "USE {$CFG->dbname};\n";
    echo "SET SESSION sql_mode=STRICT_ALL_TABLES;\n";
    foreach ($fixcompressedtables as $table => $oldformat) {
        echo "ALTER TABLE {$prefix}$table ROW_FORMAT=Compressed;\n";
    }
    foreach ($fixdynamictables as $table => $oldformat) {
        echo "ALTER TABLE {$prefix}$table ROW_FORMAT=Dynamic;\n";
    }
    echo "\n";
    exit(0);
}

echo "Unknown option\n";
exit(1);
