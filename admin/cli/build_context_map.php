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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 * @subpackage cli
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');

$help = "Rebuild context map

This script is intended mostly for performance testing,
there should not be any need to use this script on production sites.

Options:
--purge_map_first     Purge the contents of the context map before rebuild.
-h, --help            Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/build_context_map.php
";

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'purge_map_first'=>false),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo $help;
    exit(0);
}

cli_heading('Site size overview');
cli_writeln('Users             ' . $DB->count_records('user'));
cli_writeln('Course categories ' . $DB->count_records('course_categories'));
cli_writeln('Courses           ' . $DB->count_records('course'));
cli_writeln('Course modules    ' . $DB->count_records('course_modules'));
cli_writeln('Blocks            ' . $DB->count_records('block_instances'));
cli_writeln('Contexts          ' . $DB->count_records('context'));
cli_writeln('Max context depth ' . $DB->get_field('context', "MAX(depth)", array()));
cli_writeln('Context map       ' . $DB->count_records('context_map'));

if ($options['purge_map_first']) {
    cli_heading('Context map purge');
    $DB->delete_records('context_map');
    cli_writeln('... done!');
}

$timestart = time();

cli_heading('Vacuuming context tables');
if ($DB->get_dbfamily() === 'postgres') {
    $DB->execute("VACUUM ANALYZE {context_map}");
    $DB->execute("VACUUM ANALYZE {context}");
} else if ($DB->get_dbfamily() === 'mysql') {
    $DB->execute("OPTIMIZE TABLE {context_map}");
    $DB->execute("OPTIMIZE TABLE {context}");
}
cli_writeln('... done!');

cli_heading('Context map check');
\context_helper::build_all_paths(true, true);

$duration = time()  - $timestart;
$seconds = $duration % 60;
$minutes = (int)floor($duration / 60);

cli_writeln('... done, context map entries: ' . $DB->count_records('context_map') . ", total execution time $minutes'$seconds\"");

exit(0);
