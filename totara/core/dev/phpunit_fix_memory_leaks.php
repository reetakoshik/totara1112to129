<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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

list($options, $unrecognized) = cli_get_params(
    array(
        'fixtests' => false,
    ),
    array(
    )
);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!$options['fixtests']) {
    cli_error('Do not run this script if you do not know what your are doing!!!');
}

$phpunitxml = $CFG->dirroot . '/phpunit.xml';
if (!file_exists($phpunitxml)) {
    cli_error('Init phpunit first!');
}

$phpunit = new SimpleXMLElement(file_get_contents($phpunitxml));

//var_dump($phpunit);


foreach ($phpunit->testsuites->testsuite as $testsuite) {
    $suffix = $testsuite->directory['suffix'];
    foreach ($testsuite->directory as $directory) {
        $glob = "{$CFG->dirroot}/$directory/*{$suffix}";
        $files = glob($glob);
        foreach ($files as $file) {
            phpunit_fix_file($file);
        }
    }
}

function phpunit_fix_file($file) {
    $content = file_get_contents($file);

    preg_match_all('/(private|public|protected)\s+\$([a-zA-Z0-9_]+)/', $content, $matches);
    if (!$matches[2]) {
        return;
    }
    $properties = $matches[2];

    if (!preg_match('/function tearDown\(/', $content)) {
        // Find the first function first.
        if (!preg_match('/    (public |protected |private )?function\s+[a-zA-Z0-9_]+\s*\(/', $content, $matches)) {
            echo "ERROR: $file - cannot find any method to insert tearDown\n";
            return;
        }
        $function = $matches[0];

        if (preg_match('#\*/\s+' . preg_quote($function, '#') . '#s', $content, $matches)) {
            // Regex cannot do ungreedy match backwards.
            $chunk = substr($content, 0, strpos($content, $matches[0]) + strlen($matches[0]));
            $start = strrpos($chunk, '    /*');
            if ($start === false) {
                echo "ERROR: $file - cannot find before first method to insert tearDown\n";
            }
            $function = substr($chunk, $start);
        }
        $teardown = "    protected function tearDown() {
        parent::tearDown();
    }

";
        $content = str_replace($function, $teardown . $function, $content);
    }
    if (!preg_match('/function tearDown\(.*parent::tearDown\(/s', $content, $matches)) {
        echo "ERROR: $file - tearDown mess\n";
        return;
    }

    $oldteardown = $matches[0];
    $newteardown = $oldteardown;

    foreach ($properties as $property) {
        $cleanup =  '$this->' . $property . ' = null;';
        if (strpos($oldteardown, $cleanup) === false) {
            $newteardown = str_replace('parent::tearDown(', $cleanup . "\n        parent::tearDown(", $newteardown);
        }
    }

    if ($oldteardown === $newteardown) {
        return;
    }

    $newcontent = str_replace($oldteardown, $newteardown, $content);
    file_put_contents($file, $newcontent);
    echo "UPDATED: $file\n";
}