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
 * @package    course
 * @author     Russell England <russell.england@catalyst-eu.net>
 */

/**
 * Deletes course completion records and archives activities for a course
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

$id = required_param('id', PARAM_INT); // course id
$archive = optional_param('archive', '', PARAM_ALPHANUM); // archive confirmation hash

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
require_login($course);

// Set up page.
$url = new moodle_url('/course/archivecompletions.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('admin-course-archivecompletions');

// If the user can't delete then they can't archive
if (!can_delete_course($id)) {
    print_error('cannotarchivecompletions', 'completion');
}

$status = array(COMPLETION_STATUS_COMPLETE, COMPLETION_STATUS_COMPLETEVIARPL);
list($statussql, $statusparams) = $DB->get_in_or_equal($status, SQL_PARAMS_NAMED, 'status');
$sql = "SELECT DISTINCT cc.userid
        FROM {course_completions} cc
        WHERE cc.course = :courseid
        AND cc.status {$statussql}";
$params = array_merge(array('courseid' => $course->id), $statusparams);
$users = $DB->get_records_sql($sql, $params);

$category = $DB->get_record('course_categories', array('id' => $course->category));
$courseshortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));
$categoryname = format_string($category->name, true, array('context' => context_coursecat::instance($category->id)));
$strarchivecheck = get_string('archivecheck', 'completion', $courseshortname);

// Archiving restricted when course is part of a program or certification.
$cssql = "SELECT p.id, p.fullname, p.certifid
          FROM {prog_courseset_course} pcc
          JOIN {prog_courseset} pc
            ON pcc.coursesetid = pc.id
          JOIN {prog} p
            ON pc.programid = p.id
         WHERE pcc.courseid = :cid
      GROUP BY p.id, p.fullname, p.certifid";
$csparams = array('cid' => $course->id);
$coursesets = $DB->get_records_sql($cssql, $csparams);
if (!empty($coursesets)) {
    // The course is part of one or more program(s) or cert(s).
    $prognames = array();
    $certnames = array();

    foreach ($coursesets as $cs) {
        if ($cs->certifid) {
            $certnames[$cs->id] = format_string($cs->fullname);
        } else {
            $prognames[$cs->id] = format_string($cs->fullname);
        }
    }

    echo $OUTPUT->header();

    // Print generic error message.
    echo $OUTPUT->notification(get_string('error:cannotarchiveprogcourse', 'completion'), 'notifyproblem');

    // Print list of programs.
    if (!empty($prognames)) {
        echo html_writer::start_tag('div', array('class' => 'programlist'));
        echo get_string('programs', 'totara_program');
        echo html_writer::start_tag('ul');
        foreach ($prognames as $progname) {
            echo html_writer::tag('li', $progname);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');
    }

    // Print list of certifications.
    if (!empty($certnames)) {
        echo html_writer::start_tag('div', array('class' => 'certificationlist'));
        echo get_string('certifications', 'totara_certification');
        echo html_writer::start_tag('ul');
        foreach ($certnames as $certname) {
            echo html_writer::tag('li', $certname);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');
    }

    echo $OUTPUT->footer();
    die();
}

// first time round - get confirmation
$strarchivingcourse = get_string('archivingcompletions', 'completion', $courseshortname);
if (!$archive) {
    $strarchivecompletionscheck = get_string('archivecompletionscheck', 'completion');
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strarchivingcourse);

    if (empty($users)) {
        echo $OUTPUT->box(get_string('nouserstoarchive', 'completion'));
        $viewurl = new moodle_url('/course/view.php', array('id' => $course->id));
        echo $OUTPUT->continue_button($viewurl);
    } else {
        $message = $strarchivecompletionscheck;
        $message .= html_writer::empty_tag('br');
        $message .= html_writer::empty_tag('br');
        $message .= format_string($course->fullname, true, array('context' => $coursecontext));
        $message .= ' (' . $courseshortname . ')';
        $message .= html_writer::empty_tag('br');
        $message .= html_writer::empty_tag('br');
        $message .= get_string('archiveusersaffected', 'completion', count($users));

        $archiveurl = new moodle_url('/course/archivecompletions.php',
                array('id' => $course->id, 'archive' => md5($course->timemodified), 'sesskey'=>sesskey()));
        $viewurl = new moodle_url('/course/view.php', array('id' => $course->id));
        echo $OUTPUT->confirm($message, $archiveurl, $viewurl);
    }
} else {
    // user confirmed archive
    if ($archive != md5($course->timemodified)) {
        print_error('invalidmd5');
    }

    require_sesskey();

    foreach ($users as $user) {
        // Archive the course completion record before the activities to get the grade
        archive_course_completion($user->userid, $course->id);
        archive_course_activities($user->userid, $course->id);
    }

    \totara_core\event\course_completion_archived::create_from_course($course)->trigger();

    // The above archive_course_activities() calls set_module_viewed() which needs to be called before $OUTPUT->header()
    echo $OUTPUT->header();

    echo $OUTPUT->heading($strarchivingcourse);
    echo html_writer::tag('p', get_string('usersarchived', 'completion', count($users)));
    $viewurl = new moodle_url('/course/view.php', array('id' => $course->id));
    echo $OUTPUT->continue_button($viewurl);
}
echo $OUTPUT->footer();
