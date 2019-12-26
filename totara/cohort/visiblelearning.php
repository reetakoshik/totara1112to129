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
 * @package totara
 * @subpackage cohort
 */
/**
 * This page displays the embedded report for the "visible learning" items for a single cohort
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

$sid    = optional_param('sid', '0', PARAM_INT);
$id     = optional_param('id', false, PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$debug  = optional_param('debug', 0, PARAM_INT);

if (!$id) {
    $context = context_system::instance();
    $url = new moodle_url('/cohort/index.php');
    $PAGE->set_context($context);
    $PAGE->set_url($url);

    echo $OUTPUT->header();
    echo $OUTPUT->container(get_string('cohortvisiblelearningselect', 'totara_cohort', $url->out()));
    echo $OUTPUT->footer();
    exit;
}

if (empty($CFG->audiencevisibility)) {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/totara/cohort/visiblelearning.php', array('id' => $id, 'format' => $format)));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:visiblelearningdisabled', 'totara_cohort'));
    echo $OUTPUT->footer();
    exit;
}

$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);
$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
$PAGE->set_context($context);

require_capability('moodle/cohort:view', $context);
$canedit = has_all_capabilities(array('moodle/cohort:manage', 'totara/coursecatalog:manageaudiencevisibility'), $context);

$url = new moodle_url('/totara/cohort/visiblelearning.php', array('id' => $id, 'format' => $format));

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid' => $cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}

if ($context->contextlevel == CONTEXT_SYSTEM) {
    admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout'=>'report'));
} else {
    $PAGE->set_title($SITE->shortname . " : " . get_string('visiblelearning', 'totara_cohort'));
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_pagelayout('report');
    $PAGE->set_url($url);
}

$config = (new rb_config())->set_sid($sid)->set_embeddata(['cohortid' => $id]);
$report = reportbuilder::create_embedded('cohort_associations_visible', $config);
$report->include_js();

// Handle a request for export.
if($format != '') {
    $report->export_data($format);
    die;
}

// Setup lightbox.
local_js(array(TOTARA_JS_DIALOG, TOTARA_JS_TREEVIEW));

$PAGE->requires->strings_for_js(array('none'), 'moodle');
$PAGE->requires->strings_for_js(array('assignvisiblelearningcourse', 'assignvisiblelearningprogram',
                                      'assignvisiblelearningcertification', 'deletelearningconfirm', 'savinglearning'),
                                      'totara_cohort');
$jsmodule = array(
        'name' => 'totara_cohortlearning',
        'fullpath' => '/totara/cohort/dialog/learningitem.js',
        'requires' => array('json'));
$args = array('args' => '{"cohortid":' . $cohort->id . ',' .
            '"COHORT_ASSN_ITEMTYPE_CERTIF":' . COHORT_ASSN_ITEMTYPE_CERTIF . ',' .
            '"COHORT_ASSN_ITEMTYPE_PROGRAM":' . COHORT_ASSN_ITEMTYPE_PROGRAM . ',' .
            '"COHORT_ASSN_ITEMTYPE_COURSE":' . COHORT_ASSN_ITEMTYPE_COURSE . ',' .
            '"COHORT_ASSN_VALUE_VISIBLE":' . COHORT_ASSN_VALUE_VISIBLE . ',' .
            '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED . ',' .
            '"assign_value":' . COHORT_ASSN_VALUE_VISIBLE . ',' .
            '"assign_string":"' . $COHORT_ASSN_VALUES[COHORT_ASSN_VALUE_VISIBLE] .'",'.
            '"saveurl":"/totara/cohort/visiblelearning.php" }');
$PAGE->requires->js_init_call('M.totara_cohortlearning.init', $args, false, $jsmodule);

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

$strheading = get_string('visiblelearning', 'totara_cohort');
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);
echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('visiblelearning', $cohort->id, $cohort->cohorttype, $cohort);

if ($canedit) {
    echo html_writer::start_tag('div', array('class' => 'buttons visible-learning-buttons'));

    if (has_capability('moodle/course:update', context_system::instance())) {
        // Add courses.
        echo html_writer::start_tag('div', array('class' => 'singlebutton'));
        echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-course-learningitem-dialog',
            'value' => get_string('addcourses', 'totara_cohort')));
        echo html_writer::end_tag('div');
    }

    if (has_capability('totara/program:configuredetails', context_system::instance())) {
        // Add programs.
        if (totara_feature_visible('programs')) {
            echo html_writer::start_tag('div', array('class' => 'singlebutton'));
            echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-program-learningitem-dialog',
                'value' => get_string('addprograms', 'totara_cohort')));
            echo html_writer::end_tag('div');
        }
        // Add certifications.
        if (totara_feature_visible('certifications')) {
            echo html_writer::start_tag('div', array('class' => 'singlebutton'));
            echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-certification-learningitem-dialog',
                'value' => get_string('addcertifications', 'totara_cohort')));
            echo html_writer::end_tag('div');
        }
    }

    echo html_writer::end_tag('div');
}

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();
echo $reporthtml;
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();
