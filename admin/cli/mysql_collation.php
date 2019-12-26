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
 * MySQL collation conversion tool.
 *
 * @package    core
 * @copyright  2012 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

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

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'list'=>false, 'collation'=>false, 'available'=>false),
    array('h'=>'help', 'l'=>'list', 'a'=>'available'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
    "MySQL collation conversions script.

It is strongly recommended to stop the web server before the conversion.
This script should be executed before upgrade.

Full text search columns are not being updated correctly by this script,
use admin/cli/fts_rebuild_indexes.php afterwards.

Options:
--collation=COLLATION Convert MySQL tables to different collation
-l, --list            Show table and column information
-a, --available       Show list of available collations
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/mysql_collation.php --collation=utf8mb4_unicode_ci
";

/** @var mysql_sql_generator $generator */
$generator = $DB->get_manager()->generator;

if (!empty($options['collation'])) {
    $collations = mysql_get_collations();
    $collation = clean_param($options['collation'], PARAM_ALPHANUMEXT);
    $collation = strtolower($collation);
    if (!isset($collations[$collation])) {
        cli_error("Error: collation '$collation' is not available on this server!");
    }

    $collationinfo = explode('_', $collation);
    $charset = reset($collationinfo);

    $sql = "SHOW VARIABLES LIKE 'collation_database'";
    if (!$dbcollation = $DB->get_record_sql($sql)) {
        cli_error("Error: Could not access collation information on the database.");
    }
    $sql = "SHOW VARIABLES LIKE 'character_set_database'";
    if (!$dbcharset = $DB->get_record_sql($sql)) {
        cli_error("Error: Could not access character set information on the database.");
    }
    if ($dbcollation->value !== $collation || $dbcharset->value !== $charset) {
        // Try to convert the DB.
        echo "Setting default database collation '$collation' for $CFG->wwwroot:\n";
        $sql = "ALTER DATABASE $CFG->dbname DEFAULT CHARACTER SET $charset DEFAULT COLLATE = $collation";
        try {
            $DB->change_database_structure($sql);
            echo "DEFAULT DATABASE COLLATION WAS CHANGED\n";
        } catch (exception $e) {
            // Totara: things should work fine without the default because we detect collation from the config table.
            echo "DEFAULT DATABASE COLLATION COULD NOT BE CHANGED\n";
        }
    }

    echo "Converting tables and columns to '$collation' for $CFG->wwwroot:\n";
    $prefix = $DB->get_prefix();
    $prefix = str_replace('_', '\\_', $prefix);
    $sql = "SHOW TABLE STATUS WHERE Name LIKE BINARY '$prefix%'";
    $rs = $DB->get_recordset_sql($sql);
    $converted = 0;
    $skipped   = 0;
    $errors    = 0;
    foreach ($rs as $table) {
        echo str_pad($table->name, 50). " - ";

        $tablename = substr($table->name, strlen($DB->get_prefix()));
        $columns = $DB->get_columns($tablename, false);
        $format = $DB->get_row_format($tablename);

        // Totara: Make sure we have enough room for 4 byte unicode.
        $fixrowformat = '';
        if ($format !== 'Compressed') {
            $size = $generator->guess_antelope_row_size($columns);
            if ($size > $generator::ANTELOPE_MAX_ROW_SIZE) {
                $fixrowformat = 'Compressed';
            } else if ($format !== 'Dynamic') {
                $fixrowformat = 'Dynamic';
            }
        }

        if ($table->collation === $collation) {
            echo "NO CHANGE\n";
            $skipped++;

        } else {
            if ($fixrowformat) {
                $DB->change_database_structure("ALTER TABLE {$table->name} ROW_FORMAT=$fixrowformat");
                $fixrowformat = '';
            }
            $DB->change_database_structure("ALTER TABLE {$table->name} CONVERT TO CHARACTER SET $charset COLLATE $collation");
            echo "CONVERTED\n";
            $converted++;
        }

        $sql = "SHOW FULL COLUMNS FROM $table->name WHERE collation IS NOT NULL";
        $rs2 = $DB->get_recordset_sql($sql);
        $columndefs = array();
        foreach ($rs2 as $column) {
            $column = (object)array_change_key_case((array)$column, CASE_LOWER);
            echo '    '.str_pad($column->field, 46). " - ";
            if ($column->collation === $collation) {
                echo "NO CHANGE\n";
                $skipped++;
                continue;
            }

            if ($column->type === 'tinytext' or $column->type === 'mediumtext' or $column->type === 'text' or $column->type === 'longtext') {
                $notnull = ($column->null === 'NO') ? 'NOT NULL' : 'NULL';
                $default = (!is_null($column->default) and $column->default !== '') ? "DEFAULT '$column->default'" : '';
                // primary, unique and inc are not supported for texts
                $columndefs[] = "MODIFY COLUMN $column->field $column->type CHARACTER SET $charset COLLATE $collation $notnull $default";

            } else if (strpos($column->type, 'varchar') === 0) {
                $notnull = ($column->null === 'NO') ? 'NOT NULL' : 'NULL';
                $default = !is_null($column->default) ? "DEFAULT '$column->default'" : '';
                $columndefs[] = "MODIFY COLUMN $column->field $column->type CHARACTER SET $charset COLLATE $collation $notnull $default";
            } else {
                echo "ERROR (unknown column type: $column->type)\n";
                $errors++;
                continue;
            }
            echo "CONVERTED\n";
            $converted++;
        }
        $rs2->close();
        if ($columndefs) {
            // Totara: update all columns in one table to speed up the conversion.
            if ($fixrowformat) {
                $DB->change_database_structure("ALTER TABLE $table->name ROW_FORMAT=$fixrowformat");
                $fixrowformat = '';
            }
            $sql = "ALTER TABLE {$table->name}\n" . implode(",\n", $columndefs);
            $DB->change_database_structure($sql);
        }
    }
    $rs->close();
    echo "Converted: $converted, skipped: $skipped, errors: $errors\n";

    if (isset($CFG->config_php_settings['dboptions']['dbcollation']) and $CFG->config_php_settings['dboptions']['dbcollation'] !== $collation) {
        $configcollation = $CFG->config_php_settings['dboptions']['dbcollation'];
        echo "\nWARNING: collation '$configcollation' is set in config.php, you MUST change it to '$collation' now!!!\n";
    }

    exit(0); // success

} else if (!empty($options['list'])) {
    echo "List of tables for $CFG->wwwroot:\n";
    $prefix = $DB->get_prefix();
    $prefix = str_replace('_', '\\_', $prefix);
    $sql = "SHOW TABLE STATUS WHERE Name LIKE BINARY '$prefix%'";
    $rs = $DB->get_recordset_sql($sql);
    $ftsfields = mysql_get_fts_fields();
    $ftscount = 0;
    $ftscollations = array();
    $counts = array();
    foreach ($rs as $table) {
        if (isset($counts[$table->collation])) {
            $counts[$table->collation]++;
        } else {
            $counts[$table->collation] = 1;
        }
        echo str_pad($table->name, 50);
        echo $table->collation.  "\n";
        $collations = mysql_get_column_collations($table->name);
        foreach ($collations as $columname=>$collation) {
            if (isset($ftsfields[$table->name . '.' . $columname])) {
                $ftscount++;
                $ftscollations[$collation] = $collation;
            } else if (isset($counts[$collation])) {
                $counts[$collation]++;
            } else {
                $counts[$collation] = 1;
            }
            echo '    ';
            echo str_pad($columname, 46);
            echo $collation;
            // Totara: indicate full text search columns.
            if (isset($ftsfields[$table->name . '.' . $columname])) {
                echo ' (full text search)';
            }
            echo "\n";
        }
    }
    $rs->close();

    echo "\n";
    echo "Table collations summary for $CFG->wwwroot:\n";
    foreach ($counts as $collation => $count) {
        echo "  $collation: $count\n";
    }
    if ($ftscount) {
        echo "Full text search indexes ({$ftscount}):\n";
        echo '  ' . implode(', ', $ftscollations) . "\n";
    }
    exit(0); // success

} else if (!empty($options['available'])) {
    echo "List of available MySQL collations for $CFG->wwwroot:\n";
    $collations = mysql_get_collations();
    foreach ($collations as $collation) {
        echo " $collation\n";
    }
    die;

} else {
    echo $help;
    die;
}



// ========== Some functions ==============

function mysql_get_collations() {
    /** @var mysqli_native_moodle_database $DB */
    global $DB;

    $collations = array();
    $sql = "SHOW COLLATION
            WHERE Collation LIKE 'utf8\_%' AND Charset = 'utf8'
               OR Collation LIKE 'utf8mb4\_%' AND Charset = 'utf8mb4'";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $collation) {
        $collations[$collation->collation] = $collation->collation;
    }
    $rs->close();

    $collation = $DB->get_dbcollation();
    if (isset($collations[$collation])) {
        $collations[$collation] .= ' (default)';
    }

    $ftscollation = $DB->get_ftslanguage();
    if (isset($collations[$ftscollation])) {
        $collations[$ftscollation] .= ' (full text search)';
    }

    return $collations;
}

function mysql_get_column_collations($tablename) {
    global $DB;

    $collations = array();
    $sql = "SELECT column_name, collation_name
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE table_schema = DATABASE() AND table_name = ? AND collation_name IS NOT NULL";
    $rs = $DB->get_recordset_sql($sql, array($tablename));
    foreach($rs as $record) {
        $collations[$record->column_name] = $record->collation_name;
    }
    $rs->close();
    return $collations;
}

function mysql_get_fts_fields() {
    global $DB;

    $ftsfields = array();

    $schema = $DB->get_manager()->get_install_xml_schema();
    $prefix = $DB->get_prefix();

    /** @var xmldb_table[] $tables */
    $tables = $schema->getTables();
    foreach ($tables as $table) {
        /** @var xmldb_index[] $indexes */
        $indexes = $table->getIndexes();
        foreach ($indexes as $index) {
            if ($index->getHints() === array('full_text_search')) {
                $field = $table->getName() . '.' . implode($index->getFields());
                $ftsfields[$prefix . $field] = $field;
            }
        }
    }

    return $ftsfields;
}