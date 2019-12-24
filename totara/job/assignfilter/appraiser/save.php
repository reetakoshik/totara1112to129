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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_job
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/totara/core/utils.php');
require_once($CFG->dirroot.'/totara/reportbuilder/filters/lib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/filters/grpconcat_jobassignment.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

$ids = required_param('ids', PARAM_SEQUENCE);
$ids = array_filter(explode(',', $ids));
$filtername = required_param('filtername', PARAM_TEXT);
require_login();

$PAGE->set_context(context_system::instance());

// Check that the user can view the report specified and that the report contains the filter which uses this page.
// If not, then they are not permitted to view all users here.
$reportid = required_param('reportid', PARAM_INT);
$canviewreport = reportbuilder::is_capable($reportid, $USER->id);
$reporthasfilter = reportbuilder::contains_filter($reportid, 'job_assignment', 'allappraisers');
if (!($canviewreport and $reporthasfilter)) {
    print_error('accessdenied', 'admin');
}

echo $OUTPUT->container_start('list-' . $filtername);
if (!empty($ids)) {
    list($in_sql, $in_params) = $DB->get_in_or_equal($ids);
    if ($items = $DB->get_records_select('user', "id {$in_sql}", $in_params)) {
        foreach ($items as $item) {
            $item->fullname = fullname($item);
            echo display_selected_user_item($item, $filtername);
        }
    }
}
echo $OUTPUT->container_end();
