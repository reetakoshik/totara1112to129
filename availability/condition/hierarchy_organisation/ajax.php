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
 * @package availability_hierarchy_organisation
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login(null, false);

// Raw should be safe here as we are just doing text comparison.
$filter = optional_param('filter', '', PARAM_RAW);

// Permissions checks on the system context to make sure we have access to
// see Organisations.
$context = context_system::instance();
require_capability('totara/hierarchy:vieworganisation', $context);

$PAGE->set_context($context);

$results = array();

if ($filter !== '') {
    require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');

    // Do an SQL search as the hierarchy API doesn't support search or
    // limiting number of results returned.
    $searchsql = $DB->sql_like('fullname', ':fullname', false);
    $searchparam = '%' . $DB->sql_like_escape($filter) . '%';

    $sql = "SELECT id, fullname FROM {org} WHERE {$searchsql}";
    $params = array('fullname' => $searchparam);
    $organisations = $DB->get_records_sql($sql, $params, 0, 50);

    $lcfilter = core_text::strtolower($filter);

    // Do an additional search with multi-lang support,
    // we only have a max of 50 results to search here.
    foreach ($organisations as $key => $org) {
        $name = format_string($org->fullname);
        $value = core_text::strtolower($name);

        $match = false;
        if (is_string($value) && (core_text::strpos($value, $lcfilter) !== false)) {
            $match = true;
        }

        if ($match) {
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
