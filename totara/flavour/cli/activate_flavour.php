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
 * @package totara_flavour
 */

use \totara_flavour\helper;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir. '/clilib.php');

// CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'activate'   => false,
        'deactivate' => false,
        'show'       => false,
        'list'       => false,
        'help'       => false,
    ),
    array(
        's'=>'show',
        'l'=>'list',
        'h' => 'help',
    )
);

// Display help.
if (!empty($options['help']) or (!$options['deactivate'] and !$options['activate'] and !$options['show'] and !$options['list'])) {
    echo "
Utility for enforcing of current flavour.

Options:
--activate=NAME         Activate flavour
--deactivate            Deactivate any active flavour
-s --show               Show active flavour name
-l, --list              Show available flavours

-h, --help              Print out this help

Example from Totara root directory:
\$ sudo -u www-data php totara/flavour/cli/activate_flavour.php
";
    // Exit with error unless we're showing this because they asked for it.
    exit(empty($options['help']) ? 1 : 0);
}

$admin = get_admin();
\core\session\manager::set_user($admin);

if ($options['show']) {
    $flavour = helper::get_active_flavour_definition();
    if ($flavour) {
        $dirname = preg_replace('/^flavour_/', '', $flavour->get_component());
        echo $dirname . "\n";
    }
    exit(0);
}

$flavours = helper::get_available_flavour_definitions();

if ($options['list']) {
    foreach ($flavours as $flavour) {
        $dirname = preg_replace('/^flavour_/', '', $flavour->get_component());
        echo "$dirname\n";
    }
    exit(0);
}

if (isset($CFG->forceflavour)) {
    cli_error('Cannot change flavours because $CFG->forceflavour is set.');
}

if ($options['deactivate']) {
    echo "Deactivating flavours...";
    helper::set_active_flavour('');
    echo " done\n";
    exit(0);
}

$dirname = $options['activate'];
$component = 'flavour_' . $options['activate'];
if (!isset($flavours[$component])) {
    die("Invalid flavour name $dirname");
}

$flavour = $flavours[$component];
$name = $flavour->get_name();

echo "Activating flavour $name ($dirname)...";
helper::set_active_flavour($flavour->get_component());
echo " done\n";

exit(0);
