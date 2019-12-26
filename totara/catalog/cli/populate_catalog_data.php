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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
use totara_catalog\provider_handler;
use totara_catalog\local\catalog_storage;

$help = "Populate catalog data

Options:
--purge_catalog_first     Purge the contents of the catalog before rebuild.
-h, --help                Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php totara/catalog/cli/populate_catalog_data.php
";

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('help' => false, 'purge_catalog_first' => false),
    array('h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo $help;
    exit(0);
}

$timestart = time();


if ($options['purge_catalog_first']) {
    cli_heading('Catalog data purge');
    $DB->delete_records('catalog');
    cli_writeln('... done!');
}

cli_heading('Populate catalog data');

foreach (provider_handler::instance()->get_active_providers() as $provider) {
    cli_writeln('Populate provider ' . $provider::get_object_type());
    catalog_storage::populate_provider_data($provider);
    cli_writeln('... done!');
}

$duration = time()  - $timestart;
$seconds = $duration % 60;
$minutes = (int)floor($duration / 60);


exit(0);
