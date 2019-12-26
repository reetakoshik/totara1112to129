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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage cohort
 */
/**
 * This file displays the embedded report to show the "enrolled learning" items for a single cohort
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

$id     = optional_param('id', false, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.
$debug  = optional_param('debug', 0, PARAM_INT);

if (!$id) {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/totara/cohort/enrolledlearning.php'));

    echo $OUTPUT->header();
    $url = new moodle_url('/cohort/index.php');
    echo $OUTPUT->container(get_string('cohortenrolledlearningselect', 'totara_cohort', $url->out()));
    echo $OUTPUT->footer();
    exit;
}

$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);

$context = context::instance_by_id($cohort->contextid);
$PAGE->set_context($context);

require_capability('moodle/cohort:view', $context);
// TODO: TL-7240 - allow moodle/cohort:manage to also work at category context, relies on dialogs being fixed.
// at this stage cohort:manage is only checked in system context - this can be changed once dialogs
// allow all necessary permissions checks at category level
$canedit = has_capability('moodle/cohort:manage', context_system::instance());

// NOTE: the manage capability is actually wrong here for courses because the enrolment changes are controlled with:
//       enrol_is_enabled('cohort') and has_capability('moodle/course:enrolconfig', $coursecontext) and has_capability('enrol/cohort:config', $coursecontext)

$config = (new rb_config())->set_sid($sid)->set_embeddata(['cohortid' => $id]);
$report = reportbuilder::create_embedded('cohort_associations_enrolled', $config);

$url = new moodle_url('/totara/cohort/enrolledlearning.php', array('id' => $id));
if ($context->contextlevel == CONTEXT_SYSTEM) {
    admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout'=>'report'));
} else {
    $PAGE->set_url($url);
    $PAGE->set_heading($COURSE->fullname);
    $PAGE->set_title($cohort->name . ' : ' . get_string('enrolledlearning', 'totara_cohort'));
    $PAGE->set_pagelayout('report');
}

// Handle a request for export
if ($format != '') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

// Setup lightbox.
local_js(
    array(
        TOTARA_JS_DIALOG,
        TOTARA_JS_TREEVIEW,
        TOTARA_JS_DATEPICKER
    )
);

$jsmodule = array(
    'name' => 'totara_cohortenrolledlearning',
    'fullpath' => '/totara/cohort/enrolledlearning.js',
    'requires' => array('json', 'totara_core'));
$PAGE->requires->js_init_call('M.totara_cohortenrolledlearning.init', null, false, $jsmodule);

// Include cohort learning js module.
$PAGE->requires->strings_for_js(array('none'), 'moodle');
$PAGE->requires->strings_for_js(array('assignenrolledlearningcourse', 'assignenrolledlearningprogram',
                                        'assignenrolledlearningcertification', 'deletelearningconfirm', 'savinglearning'),
                                        'totara_cohort');
$jsmodule = array(
        'name' => 'totara_cohortlearning',
        'fullpath' => '/totara/cohort/dialog/learningitem.js',
        'requires' => array('json'));
$args = array('args'=>'{"cohortid":'.$cohort->id.','.
        '"COHORT_ASSN_ITEMTYPE_CERTIF":' . COHORT_ASSN_ITEMTYPE_CERTIF . ',' .
        '"COHORT_ASSN_ITEMTYPE_PROGRAM":' . COHORT_ASSN_ITEMTYPE_PROGRAM . ','.
        '"COHORT_ASSN_ITEMTYPE_COURSE":' . COHORT_ASSN_ITEMTYPE_COURSE . ','.
        '"COHORT_ASSN_VALUE_VISIBLE":' . COHORT_ASSN_VALUE_VISIBLE .','.
        '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED .','.
        '"assign_value":' . COHORT_ASSN_VALUE_ENROLLED .','.
        '"assign_string":"' . $COHORT_ASSN_VALUES[COHORT_ASSN_VALUE_ENROLLED] .'",'.
        '"saveurl":"/totara/cohort/enrolledlearning.php" }');
$PAGE->requires->js_init_call('M.totara_cohortlearning.init', $args, false, $jsmodule);
// Include cohort programcompletion js module
$PAGE->requires->strings_for_js(array('datepickerlongyeardisplayformat', 'datepickerlongyearplaceholder', 'datepickerlongyearregexjs'), 'totara_core');
$PAGE->requires->strings_for_js(array('completioncriteria', 'pleaseentervaliddate',
    'pleaseentervalidunit', 'pleasepickaninstance', 'chooseitem', 'removecompletiondate'), 'totara_program');
$selected_program = json_encode(dialog_display_currently_selected(get_string('selected', 'totara_hierarchy'), 'program-completion-event-dialog'));
$jsmodule = array(
        'name' => 'totara_cohortprogramcompletion',
        'fullpath' => '/totara/cohort/dialog/programcompletion.js',
        'requires' => array('json'));
$args = array('args'=>'{"cohortid":'.$cohort->id.','.
        '"selected_program":'.$selected_program.','.
        '"COMPLETION_EVENT_NONE":'.COMPLETION_EVENT_NONE.','.
        '"COMPLETION_TIME_NOT_SET":'.COMPLETION_TIME_NOT_SET.','.
        '"COMPLETION_EVENT_FIRST_LOGIN":'.COMPLETION_EVENT_FIRST_LOGIN.','.
        '"COMPLETION_EVENT_ENROLLMENT_DATE":'.COMPLETION_EVENT_ENROLLMENT_DATE.'}');
$PAGE->requires->js_init_call('M.totara_cohortprogramcompletion.init', $args, false, $jsmodule);

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid' => $cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}
$strheading = get_string('enrolledlearning', 'totara_cohort');
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);
/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');
echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

$report->display_restrictions();

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('enrolledlearning', $cohort->id, $cohort->cohorttype, $cohort);

echo html_writer::start_tag('div', array('class' => 'buttons enrolled-learning-buttons'));

// Add courses.
if ($canedit && has_capability('moodle/course:update', $context)) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-course-learningitem-dialog',
        'value' => get_string('addcourses', 'totara_cohort')));
    echo html_writer::end_tag('div');
}

// Add programs and certifications.
if ($canedit && totara_feature_visible('programs') && has_capability('totara/program:configureassignments', $context)) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-program-learningitem-dialog',
        'value' => get_string('addprograms', 'totara_cohort')));
    echo html_writer::end_tag('div');
}
if ($canedit && totara_feature_visible('certifications') && has_capability('totara/program:configureassignments', $context)) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-certification-learningitem-dialog',
        'value' => get_string('addcertifications', 'totara_cohort')));
    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

// Course deletion and addition warrning message.
$additionwarningmessage = get_string('courseadditionwarning', 'totara_cohort');
$deletionwarningmessage = get_string('coursedeletionwarning', 'totara_cohort');
echo $OUTPUT->notification($additionwarningmessage, 'warning');
echo $OUTPUT->notification($deletionwarningmessage, 'warning');

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo $reporthtml;
$output->export_select($report, $sid);

echo $OUTPUT->footer();
