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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/rb_sources/rb_source_course_completion_all.php');

class rb_source_dp_course_completion_history extends rb_source_course_completion_all {

    protected function define_base() {
        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $base = "(SELECT cch.id, cch.userid, cch.courseid, cch.timecompleted, cch.grade, gi.grademax, gi.grademin, 0 AS iscurrent
                    FROM {course_completion_history} cch
               LEFT JOIN {grade_items} gi ON cch.courseid = gi.courseid AND gi.itemtype = 'course'
               LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = cch.userid)";
        return $base;
    }

    protected function define_sourcetitle() {
        return get_string('sourcetitle', 'rb_source_dp_course_completion_history');
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('recordoflearning');
    }
}
