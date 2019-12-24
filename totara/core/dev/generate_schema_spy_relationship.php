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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'prefix'  => $DB->get_prefix(),
        'help'    => false,
        'file'    => false
    ),
    array(
        'p' => 'help',
        'h' => 'help'
    )
);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help =
        "Produces an XML representation of Totara database relationships suitable for import into Schema Spy.

Options:
-h, --help            Print out this help
-p, --prefix          Sets a custom database prefix.
                      If not specified the prefix set in config.php is used.
--file                Writes the XML to the given file.

";

    echo $help;
    exit(0);
}

$to_file = ($options['file']);
$filepath = false;
if ($to_file) {
    $filepath = $options['file'];
    $exists = file_exists($filepath);
    if (($exists && !is_writable($filepath)) || (!$exists && !is_writable(dirname($filepath)))) {
        cli_error('The given file cannot be written to.', 3);
    }
}

$relationships = totara_core_dev_generate_schemaspy_relationships();

if ($to_file && $filepath) {
    $xml = totara_core_dev_render_relationships_as_xml($options['prefix'], $relationships, true);
    file_put_contents($filepath, $xml);
} else {
    totara_core_dev_render_relationships_as_xml($options['prefix'], $relationships);
}

/**
 * Returns a relationship array based upon the relationships defined in install.xml.
 * @return array
 */
function totara_core_dev_generate_schemaspy_relationships() {
    global $DB;
    $manager = $DB->get_manager();
    $schema = $manager->get_install_xml_schema();
    $relationships = [];
    $tables = $schema->getTables();
    foreach ($tables as $table) {
        /** @var xmldb_table $table */
        $tablename = $table->getName();

        // Keys are obviously keys!
        foreach ($table->getKeys() as $key) {
            /** @var xmldb_key $key */
            if (empty($key->getRefTable())) {
                continue;
            }
            $fields = $key->getFields();
            if (count($fields) > 1) {
                // debugging('Key found with multiple fields, skipping ['.$key->getName().'].', DEBUG_DEVELOPER);
                continue;
            }
            $reffields = $key->getRefFields();
            if (count($reffields) > 1) {
                // debugging('Key found with multiple reference fields, skipping ['.$key->getName().'].', DEBUG_DEVELOPER);
                continue;
            }

            $field = $fields[0];
            $reftable = $key->getRefTable();
            $reffield = $reffields[0];

            if (!isset($relationships[$tablename])) {
                $relationships[$tablename] = [
                    $field => [
                        $reftable => [
                            $reffield
                        ]
                    ]
                ];
            } else if (!isset($relationships[$tablename][$field])) {
                $relationships[$tablename][$field] = [
                    $reftable => [
                        $reffield
                    ]
                ];
            } else if (!isset($relationships[$tablename][$field][$reftable])) {
                $relationships[$tablename][$field][$reftable] = [
                    $reffield
                ];
            } else {
                $relationships[$tablename][$field][$reftable][] = $reffield;
            }
        }

        // Indexes are the other one, they *may* be relating.
        foreach ($table->getIndexes() as $index) {
            /** @var xmldb_index $index */
            $fields = $index->getFields();
            if (!$index->getUnique() && count($fields) === 1) {
                $field = $fields[0];
                /** @var xmldb_field $xmldbfield */
                $xmldbfield = $table->getField($field);
                if (!$xmldbfield) {
                    // No matching field, definitely not.
                    continue;
                }
                if ($xmldbfield->getType() !== XMLDB_TYPE_INTEGER) {
                    // Not an int, definitely not.
                    continue;
                }

                if (substr($field, -2) === 'id') {
                    // It's name ends with id.
                    $reftable = substr($field, -2);
                } else {
                    $reftable = $field;
                }
                if ($manager->table_exists($reftable)) {
                    // OK very bloody likely it is related. Log it.

                    $reffield = 'id';
                    if (!isset($relationships[$tablename])) {
                        $relationships[$tablename] = [
                            $field => [
                                $reftable => [
                                    $reffield
                                ]
                            ]
                        ];
                    } else if (!isset($relationships[$tablename][$field])) {
                        $relationships[$tablename][$field] = [
                            $reftable => [
                                $reffield
                            ]
                        ];
                    } else if (!isset($relationships[$tablename][$field][$reftable])) {
                        $relationships[$tablename][$field][$reftable] = [
                            $reffield
                        ];
                    } else {
                        $relationships[$tablename][$field][$reftable][] = $reffield;
                    }

                }
            }
        }
    }
    return $relationships;
}

/**
 * Takes a relationship array and renders it as XML suitable for schemaspy.
 * @param string $prefix
 * @param array $relationships
 * @param bool $return
 * @param string $tab
 * @return string
 */
function totara_core_dev_render_relationships_as_xml($prefix, array $relationships, $return = false, $tab = '    ') {
    $result = '';
    $out = function($line) use ($return, &$result) {
        if ($return === false) {
            echo $line . "\n";
        } else {
            $result .= $line . "\n";
        }
    };

    $out("<schemaMeta>");
    $out($tab . "<comments>Foreign Key information for Totara tables</comments>");
    $out($tab . "<tables>");
    foreach ($relationships as $table => $fields) {
        $out($tab . $tab . "<table name='{$prefix}{$table}'>");
        foreach ($fields as $field => $references) {
            $out($tab . $tab . $tab . "<column name='{$field}'>");
            foreach ($references as $reftable => $reffields) {
                foreach ($reffields as $reffield) {
                    $out($tab . $tab . $tab . $tab . "<foreignKey table='{$prefix}{$reftable}' column='{$reffield}'/>");
                }
            }
            $out($tab . $tab . $tab . "</column>");
        }
        $out($tab . $tab . "</table>");
    }
    $out($tab . "</tables>");
    $out("</schemaMeta>");

    if ($return) {
        return $result;
    }
}