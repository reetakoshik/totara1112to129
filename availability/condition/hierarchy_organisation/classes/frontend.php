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
 * @package availablity_hierarchy_organisation
 */

namespace availability_hierarchy_organisation;

use totara_reportbuilder\rb\display\format_string;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/hierarchy/prefix/organisation/lib.php");

/**
 * Front-end class.
 */
class frontend extends \core_availability\frontend {

    /**
     * Restrict the adding of this restriction to people who have the capability
     * to view audiences.
     *
     * @param stdClass course
     * @param \cm_info $cm
     * @param \section_info $section
     * @return bool True if the user can add this restriction.
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        $context = \context_system::instance();

        if (has_capability('totara/hierarchy:vieworganisation', $context)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Gets strings to be used in the condition's JavaScript
     * file
     *
     * @return array Array of required string identifiers
     */
    protected function get_javascript_strings() {
        return array('chooseorganisation', 'error_selectfield', 'searchorganisations');

    }

    /**
     * Gets additional parameters so we can set the select2 field for the
     * current selected organisation.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $DB;

        $org_names = array();

        if (!empty($cm->availability)) {
            $ids = array();
            self::for_each_condition_in_availability_json(
                $cm->availability, function ($condition) use (&$ids) {
                    if ($condition->type == 'hierarchy_organisation') {
                        $ids[$condition->organisation] = $condition->organisation;
                    }
                }
            );

            if (!empty($ids)) {
                list($insql, $params) = $DB->get_in_or_equal($ids);
                $sql = "SELECT id, fullname
                        FROM {org}
                        WHERE id $insql";
                $org_names = $DB->get_records_sql($sql, $params);

                foreach ($org_names as $id => $value) {
                    $value->fullname = format_string($value->fullname);
                    $org_names[$id] = $value;
                }
            }
        } else if (!empty($section->availability)) {
            // A fallback for section_info if cm_info is null
            $availability = json_decode($section->availability, true);
            if ($availability && !empty($availability['c'])) {
                $organisation = new \organisation();
                $organisationids = array();
                array_walk_recursive($availability['c'], function($item, $key) use(&$organisationids) {
                    if ($key == "organisation") {
                        $organisationids[] = (int)$item;
                    }
                });

                foreach ($organisationids as $organisationid) {
                    $record = $organisation->get_item($organisationid);
                    if (!$record) {
                        continue;
                    }
                    $single = new \stdClass();
                    $single->fullname = format_string($record->fullname);
                    $org_names[$record->id] = $single;
                }
            }
        } else {
            $org_names = $DB->get_records_sql('SELECT id, fullname FROM {org}');
            foreach ($org_names as $id => $value) {
                $value->fullname = format_string($value->fullname);
                $org_names[$id] = $value;
            }
        }

        $data = new \stdClass();
        $data->organisationNames = $org_names;
        $result = array($data);

        return $result;
    }
}
