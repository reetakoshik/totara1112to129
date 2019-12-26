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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package totara
 * @subpackage cohort
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

$id     = optional_param('id', false, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format','',PARAM_TEXT); //export format
$debug  = optional_param('debug', 0, PARAM_INT); // Debug level.

if (!$id) {
    $context = context_system::instance();
    $PAGE->set_context($context);

    $url = new moodle_url('/cohort/members.php', array('id' => $id, 'format' => $format, 'debug' => $debug));
    admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout' => 'report'));

    echo $OUTPUT->header();
    $url = new moodle_url('/cohort/index.php');
    echo $OUTPUT->container(get_string('cohortmembersselect', 'totara_cohort', $url->out()));
    echo $OUTPUT->footer();
    exit;
} else {
    $cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);

    $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
    $PAGE->set_context($context);

    $url = new moodle_url('/cohort/members.php', array('id' => $id));

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout' => 'report'));
    } else {
        // We call require_login here instead of admin_externalpage_setup because we need the capability checks to be made
        // in the category context, not the system context.
        // Actual access checks will be made by rb_cohort_members_embedded::is_capable when the report is constructed.
        require_login();
        $PAGE->set_heading($COURSE->fullname);
        $PAGE->set_pagelayout('report');
        $PAGE->set_url($url);
        $PAGE->set_title($cohort->name . ' : ' . get_string('members', 'totara_cohort'));
    }
}

// Verify global restrictions.
$shortname = 'cohort_members';
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = (new rb_config())->set_global_restriction_set($globalrestrictionset)->set_embeddata(['cohortid' => $id])->set_sid($sid);
$report = reportbuilder::create_embedded($shortname, $config);

if ($format != '') {
    $report->export_data($format);
    die;
}

$report->include_js();

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid'=>$cohort->contextid)));

} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}
$strheading = get_string('viewmembers', 'totara_cohort');
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

echo $OUTPUT->header();
// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;
if (isset($id)) {
    echo $OUTPUT->heading(format_string($cohort->name));
    echo cohort_print_tabs('viewmembers', $cohort->id, $cohort->cohorttype, $cohort);
}

$report->display_restrictions();

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();
echo $reporthtml;
$output->export_select($report, $sid);

echo $OUTPUT->footer();
