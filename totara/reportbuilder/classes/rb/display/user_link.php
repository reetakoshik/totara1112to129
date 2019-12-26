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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for showing a user's name and link to their profile
 * When exporting, only the user's full name is displayed (without link)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class user_link extends base {

    /**
     * Handles the display. Rules are pretty simple:
     * + Don't show link in the spreadsheet.
     * + Don't show link if actor cannot view target user profile in specific context
     * + Show link with course id if the actor can view with course context
     * + Show link with course id if the actor is viewing actor's self profile.
     * + Show link without course id if the the course is a SITE.
     * + Show link without course id if the actor is viewing self's profile but not enrolled
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $USER, $PAGE, $DB;

        // Extra fields expected are fields from totara_get_all_user_name_fields_join() and totara_get_all_user_name_fields_join()
        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        if (isset($extrafields->user_id)) {
            $fullname = $value;
        } else {
            $fullname = fullname($extrafields);
        }

        if (empty($fullname)) {
            return '';
        }

        $userid = $extrafields->id;
        if ($isexport || $userid == 0) {
            return $fullname;
        }

        if (isset($extrafields->deleted)) {
            $isdeleted = $extrafields->deleted;
        } else {
            // Grab one if needed.
            debugging(
                "For the performance speed, please include the field 'deleted' in your report builder SQL",
                DEBUG_DEVELOPER
            );

            $isdeleted = $DB->get_field('user', 'deleted', ['id' => $userid], MUST_EXIST);
        }

        if ($isdeleted) {
            // If the user is deleted, don't show link.
            return $fullname;
        }

        $url = new \moodle_url("/user/view.php", ["id" => $userid]);
        if (CLI_SCRIPT && !PHPUNIT_TEST) {
            // It is CLI_SCRIPT, most likely that the course is not being set for $PAGE, and neither the $PAGE itself.
            return \html_writer::link($url, $fullname);
        }

        $course = $PAGE->course;

        // A hacky way to detect whether we are displaying the user name link within a course context or not.
        // TL-18965 reported with inconsistency link for username that it goes to system context instead of course
        // context even though the embedded report was view within course.
        if ($course->id == SITEID) {
            return \html_writer::link($url, $fullname);
        }

        // Only adding the course id if user the course is not one of the SITE course
        $context = \context_course::instance($course->id);
        if (is_enrolled($context, $userid) || is_viewing($context, $userid)) {
            $url->param('course', $course->id);
            return \html_writer::link($url, $fullname);
        } else if ($userid == $USER->id) {
            // If the user is self, throw the profile url
            return \html_writer::link($url, $fullname);
        } else {
            $usercontext = \context_user::instance($userid);
            $capabilities = [
                'moodle/user:viewdetails',
                'moodle/user:viewalldetails'
            ];

            if (has_any_capability($capabilities, $usercontext)) {
                // User has capability to view other's full detail within site profile.
                return \html_writer::link($url, $fullname);
            } else {
                // Last resource of checking. Well, it should never get to here at all, but who knows ¯\_(ツ)_/¯
                $userobj = new \stdClass();
                $userobj->id = $userid;
                $userobj->deleted = $isdeleted;

                if (user_can_view_profile($userobj)) {
                    // Yep, the current actor is able to view the target user, but could be in any course context,
                    // and it will be redirected to user profile page.
                    return \html_writer::link($url, $fullname);
                }
            }
        }

        // The current actor is not able to view the profile of target user within course.
        return $fullname;
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
