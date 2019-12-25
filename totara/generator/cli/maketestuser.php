<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_generator
 */

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

// No logging.
define('LOG_MANAGER_CLASS', '\core\log\dummy_manager');

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir. '/clilib.php');
require_once($CFG->dirroot . '/lib/testing/generator/data_generator.php');

$CFG->noemailever = 1;

// CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'count' => false,
        'userpassword' => false,
        'quiet' => false,
        'bypasscheck' => false,
    ),
    array(
        'h' => 'help'
    )
);

// Display help.
if (!empty($options['help'])) {
    echo "
Utility for bulk user creation.

Not for use on live sites! Only works if debugging is set to DEVELOPER level.
Progess is indicated by one dot for each 1000 users created.

Options:
--count=n               Number of users to create (default 1)
--userpassword=password Password for all generated user accounts (default \$CFG->tool_generator_users_password)
--quiet                 Do not show any output

-h, --help              Print out this help

Example from Totara root directory:
\$ sudo -u www-data php totara/generator/cli/maketestuser.php --userpassword=Passw0rd! --count=1000
";
    // Exit with error unless we're showing this because they asked for it.
    exit(empty($options['help']) ? 1 : 0);
}

// Check debugging is set to developer level.
if (empty($options['bypasscheck']) && !debugging('', DEBUG_DEVELOPER)) {
    cli_error(get_string('error_notdebugging', 'tool_generator'));
}

// Get options.
$count = $options['count'] ? $options['count'] : 1;
$quiet = $options['quiet'];

$admin = get_admin();
\core\session\manager::set_user($admin);

$generator = new testing_data_generator();
$start = time();

$record = array();
$record['descriptionformat'] = FORMAT_HTML;
if (!empty($options['userpassword'])) {
    $record['password'] = $options['userpassword'];
} else if (!empty($CFG->tool_generator_users_password)) {
    $record['password'] = $CFG->tool_generator_users_password;
}

$options =  array('noinsert' => true);

$users = array();
for ($i = 0; $i < $count; $i++ ) {
    // Add long description to simulate real user data.
    $record['description'] = "Some user description $i" . str_repeat('<br />' . $generator->loremipsum, 8);

    $users[] = $generator->create_user($record, $options);
    if ($i > 0 and $i % 1000 === 0) {
        if (!$quiet) {
            echo '.';
        }
        $DB->insert_records('user', $users);
        $users = array();
    }
}
if ($users) {
    $DB->insert_records('user', $users);
}
context_helper::build_all_paths(false);

if (!$quiet) {
    echo "\nTotal time to create $count users: " . (time() - $start) . " seconds\n";
}
