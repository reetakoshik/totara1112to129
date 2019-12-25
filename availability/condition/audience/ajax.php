<?php
/*
 * This file is part of Totara Learn
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package availability_audience
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login(null, false);

$courseid = optional_param('course', 0, PARAM_INT);
$filter = optional_param('filter', '', PARAM_TEXT);

// Permissions checks on the system context to make sure we have access to see audiences.
if ($courseid) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}
require_capability('moodle/cohort:view', $context);

$PAGE->set_context($context);

$results = array();

if ($filter !== '') {
    require_once($CFG->dirroot . '/cohort/lib.php');

    // Limit results to 5,000 - some users have many audiences with similar names.
    $cohorts = cohort_get_all_cohorts(0, 5000, $filter);

    $lcfilter = core_text::strtolower($filter);

    foreach ($cohorts['cohorts'] as $key => $data) {
        // Format string for multi-lang.
        $name = format_string($data->name);

        // We need to do another filter as the cohort_get_all_cohorts
        // filter doesn't do multilang filtering (we have a much smaller
        // dataset this time).
        $value = core_text::strtolower($name);

        if (is_string($value) && (core_text::strpos($value, $lcfilter) !== false)) {
            $item = new stdClass();
            $item->label = $name;
            $item->value = $key;
            $results[] = $item;
        }
    }
}

echo $OUTPUT->header();
echo json_encode($results);
echo $OUTPUT->footer();
