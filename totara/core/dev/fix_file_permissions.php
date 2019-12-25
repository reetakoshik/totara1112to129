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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'list'    => false,
        'fix'   => false,
        'help'    => false,
        'check' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!$options['fix'] and !$options['list'] and !$options['check']) {
    $help =
        "Find and fix all files that incorrectly have the execute bit set.

Options:
-h, --help            Print out this help
--list                List all files with the execute bit set for which it should not be set.
--fix                 Drop the execute bit on all files that should not have it.
--check               Checks for incorrect files and exits with an error code if any are found.
";

    echo $help;
    exit(0);
}

$files = \totara_core\helper::get_incorrectly_executable_files();

if ($options['list']) {
    if (!empty($files)) {
        foreach ($files as $path => $file) {
            echo "{$path}\n";
        }
    }
}
if ($options['fix']) {
    foreach ($files as $path => $file) {
        $perms = $file->getPerms() & 0666;
        echo $path . " [" . substr(decoct($file->getPerms()), -3)." => ".substr(decoct($perms), -3)."]\n";
        chmod($file->getPathname(), $perms);
    }
}

if ($options['check']) {
    if (!empty($files)) {
        exit(count($files));
    } else {
        exit(0);
    }
}