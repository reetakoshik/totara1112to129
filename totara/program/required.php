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
 * @author Ben Lobo <ben@benlobo.co.uk>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage plan
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT); // show required learning for this user
$programid = optional_param('id', 0, PARAM_INT);
$filter = optional_param('filter', 'all', PARAM_TEXT);

$courseid = optional_param('cid', 0, PARAM_INT); // Used to apply enrolments before redirecting.

$PAGE->set_context(context_system::instance());
// Check if programs or certifications are enabled.
if ($filter == 'program') {
    check_program_enabled();
} else if ($filter == 'certification') {
    check_certification_enabled();
} else if (totara_feature_disabled('programs') &&
    totara_feature_disabled('certifications')) {
    print_error('programsandcertificationsdisabled', 'totara_program');
}

$PAGE->set_url('/totara/program/required.php', array('id' => $programid, 'userid' => $userid, 'filter' => $filter));
$PAGE->set_pagelayout('report');

//
/// Permission checks
//
if (!prog_can_view_users_required_learning($userid)) {
    print_error('error:nopermissions', 'totara_program');
}

// Check if we are viewing the required learning as a manager or a learner
if ($userid != $USER->id) {
    $role = 'manager';
} else {
    $role = 'learner';
}

if ($programid) {
    $program = new program($programid);
}

if (isset($program) && $program->user_is_assigned($userid)) {
    if (prog_is_accessible($program)) {

        // Apply course enrolments before redirecting here.
        if (!empty($courseid)) {
            $url = new moodle_url('/course/view.php', array('id' => $courseid));
            // Check sesskey.
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }

            // Check $user == $USER, don't let managers trigger users enrolments.
            if ($userid != $USER->id) {
                // It's not the learner so lets just redirect to the course.
                redirect($url);
            }

            // Check for existing enrolment, no point trying to enrol someone who is already enrolled.
            $sql = "SELECT ue.id
                      FROM {user_enrolments} ue
                      JOIN {enrol} e
                        ON ue.enrolid = e.id
                     WHERE ue.userid = :uid
                       AND e.courseid = :cid";
            $params = array('uid' => $userid, 'cid' => $courseid);
            if ($DB->record_exists_sql($sql, $params)) {
                redirect($url);
            }

            // Check the course is actually a (currently accessible) part of the program.
            $courseavailable = false;
            $certifpath = empty($program->certifid) ? CERTIFPATH_STD : $DB->get_field('certif_completion', 'certifpath', array('userid' => $userid, 'certifid' => $program->certifid));
            $courseset_groups = $program->content->get_courseset_groups($certifpath);
            foreach ($courseset_groups as $cs_group) {
                $available = false;

                foreach ($cs_group as $courseset) {
                    $courses = $courseset->courses;
                    foreach ($courses as $course) {
                        if ($course->id == $courseid) {
                            // Found it, try and enrol the user.
                            $courseavailable = true;
                            break(2);
                        }
                    }

                    if ($courseset->is_courseset_complete($userid)) {
                        $available = true;
                    }
                }

                if (!$available) {
                    // Don't check courses in locked coursesets.
                    break;
                }
            }

            if (!$courseavailable) {
                // Looks like we didn't find the course, redirect before attempting to enrol.
                redirect($url);
            }

            // Run the enrolments, note: taken from require_login().
            $params = array('courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED);
            $instances = $DB->get_records('enrol', $params, 'sortorder, id ASC');
            $enrols = enrol_get_plugins(true);
            // First ask all enabled enrol instances in course if they want to auto enrol user.
            foreach ($instances as $instance) {
                if (!isset($enrols[$instance->enrol])) {
                    continue;
                }
                // Get a duration for the enrolment, a timestamp in the future, 0 (always) or false.
                $until = $enrols[$instance->enrol]->try_autoenrol($instance);
                if ($until !== false) {
                    if ($until == 0) {
                        $until = ENROL_MAX_TIMESTAMP;
                    }
                    $USER->enrol['enrolled'][$courseid] = $until;
                    $access = true;
                    break;
                }
            }

            // Finally redirect to the course view page.
            redirect($url);
        }

        //Javascript include
        local_js(array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW,
            TOTARA_JS_DATEPICKER
        ));

        $PAGE->requires->strings_for_js(array('pleaseentervaliddate', 'pleaseentervalidreason', 'extensionrequest', 'cancel', 'ok'), 'totara_program');
        $notify_html = trim($OUTPUT->notification(get_string("extensionrequestsent", "totara_program"), "notifysuccess"));
        $notify_html_fail = trim($OUTPUT->notification(get_string("extensionrequestnotsent", "totara_program"), null));
        $args = array('args'=>'{"id":'.$program->id.', "userid":'.$USER->id.', "user_fullname":'.json_encode(fullname($USER)).', "notify_html_fail":'.json_encode($notify_html_fail).', "notify_html":'.json_encode($notify_html).'}');
        $jsmodule = array(
             'name' => 'totara_programview',
             'fullpath' => '/totara/program/view/program_view.js',
             'requires' => array('json', 'totara_core')
             );
        $PAGE->requires->js_init_call('M.totara_programview.init',$args, false, $jsmodule);

        ///
        /// Display
        ///

        $heading = $program->fullname;
        $pagetitle = format_string(get_string('program', 'totara_program').': '.$heading);

        prog_add_required_learning_base_navlinks($userid);

        $PAGE->navbar->add($heading);

        $PAGE->set_title($pagetitle);
        $PAGE->set_heading(format_string($SITE->fullname));
        dp_display_plans_menu($userid, 0 , $role, 'courses', 'none', true, $program->id, true);
        echo $OUTPUT->header();

        // Trigger event.
        $dataevent = array('id' => $programid, 'other' => array('section' => 'required'));
        if ($userid !== $USER->id) {
            // If the current user is viewing another user, then add the relateduserid.
            $dataevent['relateduserid'] = $userid;
        }
        $event = \totara_program\event\program_viewed::create_from_data($dataevent)->trigger();

        // Program page content
        echo $OUTPUT->container_start('', 'program-content');

        echo $OUTPUT->heading($heading);

        echo $program->display($userid);

        echo $OUTPUT->container_end();

        echo $OUTPUT->footer();
    } else {
        // If the program is not accessible then print heading
        // and unavailiable message

        $heading = $program->fullname;
        $pagetitle = format_string(get_string('program', 'totara_program').': '.$heading);

        prog_add_required_learning_base_navlinks($userid);

        $PAGE->navbar->add($heading);

        $PAGE->set_title($pagetitle);
        $PAGE->set_heading(format_string($SITE->fullname));
        echo $OUTPUT->header();

        echo $OUTPUT->heading($heading);

        echo html_writer::start_tag('p') . get_string('programnotcurrentlyavailable', 'totara_program') . html_writer::end_tag('p');

        echo $OUTPUT->footer();
    }
} else {
    //
    // Display program list
    //

    $heading = get_string('requiredlearning', 'totara_program');
    $pagetitle = format_string(get_string('requiredlearning', 'totara_program'));

    prog_add_required_learning_base_navlinks($userid);

    $PAGE->set_title($heading);
    $PAGE->set_heading($pagetitle);
    dp_display_plans_menu($userid, 0, $role, 'courses', 'none');
    echo $OUTPUT->header();

    // Required learning page content
    echo $OUTPUT->container_start('', 'required-learning');

    if ($userid != $USER->id) {
        echo prog_display_user_message_box($userid);
    }

    echo $OUTPUT->heading($heading);

    echo $OUTPUT->container_start('', 'required-learning-description');

    if ($userid == $USER->id) {
        $requiredlearninginstructions = html_writer::start_tag('div', array('class' => 'instructional_text')) . get_string('requiredlearninginstructions', 'totara_program') . html_writer::end_tag('div');
    } else {
        $user = $DB->get_record('user', array('id' => $userid));
        $userfullname = fullname($user);
        $requiredlearninginstructions = html_writer::start_tag('div', array('class' => 'instructional_text')) . get_string('requiredlearninginstructionsuser', 'totara_program', $userfullname) . html_writer::end_tag('div');
    }

    echo $requiredlearninginstructions;

    echo html_writer::start_tag('div', array('style' => 'clear: both;')) . html_writer::end_tag('div');
    echo $OUTPUT->container_end();

    if (($filter == 'all' || $filter == 'program') && totara_feature_visible('programs')) {
        echo $OUTPUT->container_start('', 'required-learning-list');
        echo $OUTPUT->heading(get_string('programs', 'totara_program'), 3);

        $requiredlearninghtml = prog_display_required_programs($userid);

        if (empty($requiredlearninghtml)) {
            echo get_string('norequiredlearning', 'totara_program');
        } else {
            echo $requiredlearninghtml;
        }

        echo $OUTPUT->container_end();
    }

    if (($filter == 'all' || $filter == 'certification') && totara_feature_visible('certifications')) {
        echo $OUTPUT->container_start('', 'certification-learning-list');
        echo $OUTPUT->heading(get_string('certifications', 'totara_program'), 3);

        $certificationhtml = prog_display_certification_programs($userid);

        if (empty($certificationhtml)) {
            echo get_string('nocertificationlearning', 'totara_program');
        } else {
            echo $certificationhtml;
        }

        echo $OUTPUT->container_end();
    }

    echo $OUTPUT->container_end();
    echo $OUTPUT->footer();
}
