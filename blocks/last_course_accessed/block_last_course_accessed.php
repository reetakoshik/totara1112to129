<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * Block for displaying the last course accessed by the user.
 *
 * @package block_last_block_accessed
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Recent learning block
 *
 * Displays recent completed courses
 */
class block_last_course_accessed extends block_base {

    public function init() {
        $this->title = get_string('lastcourseaccessed', 'block_last_course_accessed');
    }

    public function get_content() {
        global $CFG, $DB, $USER;

        // Required for generating course completion progress bar.
        require_once("{$CFG->libdir}/completionlib.php");

        // Use the hook to retrieve any course IDs we don't want to use
        $exclude_courses = array();
        $hook = new \block_last_course_accessed\hook\exclude_courses($exclude_courses);
        $hook->execute();

        // If the content is already defined, return it.
        if ($this->content !== null) {
            return $this->content;
        }

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Mainain a list of accessed courses so we have one to display if we have to
        // exclude any.
        $accessed_courses = [];

        // The USER global has the data we need about last course access.
        // It will only exist if a course has been accessed. If it doesn't
        // exist, retrieve the data directly.
        if (!empty($USER->currentcourseaccess)) {
            $courseaccess = $USER->currentcourseaccess;
            // Get the data from the last course access.
            arsort($courseaccess);
            $timestamp = reset($courseaccess);
            $courseid = key($courseaccess);

            $accessed_courses[$timestamp] = $courseid;
        }

        $params = array('userid' => $USER->id);
        // Get the course data delivered in the right order with the latest first, so use get_records.
        $last_access = $DB->get_records('user_lastaccess', $params, 'timeaccess DESC', "courseid, timeaccess");

        if ($last_access) {
            foreach ($last_access as $access) {
                $accessed_courses[$access->timeaccess] = $access->courseid;
            }
        }

        if ($accessed_courses) {
            $permitted_courses = array_diff($accessed_courses, $exclude_courses);
            $courseid = reset($permitted_courses);
            $timestamp = key($permitted_courses);
        }

        if (!isset($courseid) || !isset($timestamp)) {
            return $this->content;
        }

        // Get the course and completion data for the course and user. Using a LEFT JOIN allows for
        // the possibility of no completion data, in which case we won't display the progress bar.
        $sql = "SELECT c.id, c.fullname, cc.status, " . context_helper::get_preload_record_columns_sql('ctx') . "
                FROM {course} c
                LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                LEFT JOIN {course_completions} cc ON c.id = cc.course AND cc.userid = :userid
                WHERE c.id = :courseid";
        $params = array('courseid' => $courseid, 'userid' => $USER->id, 'contextlevel' => CONTEXT_COURSE);

        // Get visibility sql for the courses the user can view.
        list($visibilitysql, $visibilityparams) = totara_visibility_where($USER->id, 'c.id', 'c.visible', 'c.audiencevisible');
        $sql .= " AND {$visibilitysql} ";
        $params = array_merge($params, $visibilityparams);

        $course = $DB->get_record_sql($sql, $params);

        if (!$course) {
            return $this->content;
        }

        // Get the text that describes when the course was last accessed.
        $last_accessed = totara_core_get_relative_time_text($timestamp, null, true);

        // As we have the instance from the database we can use it to set the context for format_string below.
        context_helper::preload_from_record($course);
        $context = context_course::instance($course->id);

        // Build the data object for the template.
        $templateobject = new stdClass();
        $templateobject->course_url = (string) new moodle_url('/course/view.php', array('id' => $course->id));
        $templateobject->course_name = format_string($course->fullname, true, $context);
        $templateobject->course_name_link_title = get_string('access_course', 'block_last_course_accessed', $templateobject->course_name);
        $templateobject->last_accessed = $last_accessed;

        // Use the hook to retrieve any custom content for the block template.
        $hook = new \block_last_course_accessed\hook\template_content($templateobject);
        $hook->execute();

        // Set the class to be used depending on the length of the course name.
        if (\core_text::strlen($templateobject->course_name) > 200) {
            $templateobject->course_name_class = 'small';
        } else if (\core_text::strlen($templateobject->course_name) > 100) {
            $templateobject->course_name_class = 'medium';
        } else {
            $templateobject->course_name_class = 'large';
        }

        // Get the renderer so we can render templates.
        /** @var totara_core_renderer $renderer */
        $renderer = $this->page->get_renderer('totara_core');

        // If there's no status, there's no completion data, so no progress bar.
        $templateobject->progress = $renderer->export_course_progress_for_template($USER->id, $courseid, $course->status);

        // Get the block content from the template.
        $this->content->text = $renderer->render_from_template('block_last_course_accessed/block', $templateobject);

        return $this->content;
    }
}
