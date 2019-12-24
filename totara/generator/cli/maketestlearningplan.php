<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_generator
 */

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

// No logging.
define('LOG_MANAGER_CLASS', '\core\log\dummy_manager');

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir. '/clilib.php');

// No emails here!
$CFG->noemailever = 1;

// CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'name' => NULL,
        'size' => false,
        'fixeddataset' => false,
        'filesizelimit' => false,
        'bypasscheck' => false,
        'quiet' => false
    ),
    array(
        'h' => 'help'
    )
);

// Display help.
if (!empty($options['help']) || empty($options['size'])) {
    echo "
Utility to create standard test learning plan.

Not for use on live sites! Only works if debugging is set to DEVELOPER level.

Options:
--size           Size of learning plan to create XS, S, M, L, XL, or XXL (required)
--name           Name of learning plan to create
--fixeddataset   Use a fixed data set instead of randomly generated data
--filesizelimit  Limits the size of the generated files to the specified bytes
--quiet          Do not show any output

-h, --help     Print out this help

Example from Totara root directory:
\$ sudo -u www-data php totara/generator/cli/maketestlearningplan.php --name=SIZE_S --size=S
";
    // Exit with error unless we're showing this because they asked for it.
    exit(empty($options['help']) ? 1 : 0);
}

// Check debugging is set to developer level.
if (empty($options['bypasscheck']) && !debugging('', DEBUG_DEVELOPER)) {
    cli_error(get_string('error_notdebugging', 'tool_generator'));
}

// Get options.
$name = $options['name'];
$sizename = $options['size'];
$fixeddataset = $options['fixeddataset'];
$filesizelimit = $options['filesizelimit'];

// Switch to admin user account.
$admin = get_admin();
\core\session\manager::set_user($admin);

// Check size.
$size = totara_generator_course_backend::size_for_name($sizename);

// Do backend code to generate course.
$backend = new totara_generator_learning_plan_backend($size, $name, $fixeddataset, $filesizelimit, empty($options['quiet']));
$id = $backend->make();
