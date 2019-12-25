<?php

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../etc/config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

// Initialise jquery requirements.
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

require_login();

$sid = optional_param('sid', '0', PARAM_INT);
$courseid = optional_param('courseid', '0', PARAM_INT);
$format = optional_param('format', '',PARAM_TEXT); //export format
$debug  = optional_param('debug', 0, PARAM_INT);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url('/local/lz_report/detailed_activity_completion/embedded.php', ['courseid' => $courseid]);
$PAGE->set_pagelayout('noblocks');

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
buildBreadcrumbs($course);

require_capability(VIEW_LZ_REPORT_CAPABILITY, $context);

$strheading = get_string('sourcetitle', 'rb_source_detailed_activity_completion') . ' ' . $course->shortname;

$shortname = 'detailed_activity_completion';
$data = ['courseid' => $courseid];

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
$config = (new \rb_config())->set_embeddata($data)->set_nocache(true);
if (!$report = \reportbuilder::create_embedded($shortname, $config)) {
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

function buildBreadcrumbs($course)
{
	global $PAGE;
	$PAGE->navigation->add(
		get_string('courses'),
		new moodle_url('/totara/coursecatalog/courses.php')
	)->add(
		$course->shortname,
		new moodle_url("/course/view.php?id={$course->id}")
	)->add(
		get_string('menu-item-name', 'local_lz_report')
	)->add(
		get_string('sourcetitle', 'rb_source_detailed_activity_completion')
	)->make_active();
}
