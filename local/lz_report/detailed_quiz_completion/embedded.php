<?php

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../etc/config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');

require_login();

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '',PARAM_TEXT); //export format
$debug  = optional_param('debug', 0, PARAM_INT);
$cmid = optional_param('quizid', '0', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid);
$course = get_course($cm->course);
$quizObj = quiz::create($cm->instance);
$quiz = $quizObj->get_quiz();

$context = context_module::instance($cmid);
$PAGE->set_url('/local/lz_report/detailed_quiz_completion/embedded.php', ['id' => $cmid]);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('noblocks');

require_capability(VIEW_LZ_REPORT_CAPABILITY, $context);

$shortname = 'detailed_quiz_completion';

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$report = reportbuilder_get_embedded_report(
	$shortname,
	['quizid' => $quiz->id],
	false,
	$sid,
	$globalrestrictionset
);

if (!$report) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

if ($debug) {
    $report->debug($debug);
}

$logurl = $PAGE->url->out_as_local_url();
if ($format!='') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

///
/// Display the page
///
$strheading = get_string('sourcetitle', 'rb_source_detailed_quiz_completion');

$PAGE->set_title($strheading);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading(format_string($SITE->fullname));

$report->include_js();
$PAGE->requires->js_init_call('M.totara_message.init');

$output = $PAGE->get_renderer('totara_reportbuilder');

echo $output->header();

$report->display_restrictions();

// Display heading including filtering stats.
$countfiltered = $report->get_filtered_count();
if ($report->can_display_total_count()) {
    $resultstr = 'recordsshown';
    $a = new stdClass();
    $a->countfiltered = $countfiltered;
    $a->countall = $report->get_full_count();
} else{
    $resultstr = 'recordsall';
    $a = $countfiltered;
}
echo $output->heading(get_string($resultstr, 'totara_message', $a), 3);

if (!empty($report->description)) {
    $report->description = $report->description;
}

echo $output->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo $output->showhide_button($report->_id, $report->shortname);

$report->display_table();
// Export button.
$output->export_select($report, $sid);

echo $output->footer();
