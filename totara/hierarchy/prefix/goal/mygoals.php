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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

// Check if Goals are enabled.
goal::check_feature_enabled();

$userid             = optional_param('userid', $USER->id, PARAM_INT);  // Show goals of this user.
$edit               = optional_param('edit', -1, PARAM_BOOL);    // Turn editing on and off.
$display            = optional_param('display', false, PARAM_BOOL); // Determines whether or not to show the goal details in the table.
$personaldisplay    = optional_param('personaldisplay', false, PARAM_BOOL);
$format             = optional_param('format', '', PARAM_TEXT); // Export format.
$sid                = optional_param('sid', '0', PARAM_INT);

$pageparams = array(
    'userid' => $userid
);

$data = array(
    'userid' => $userid,
);

require_login();

$context = context_system::instance();

$goal = new goal();
if (!$permissions = $goal->get_permissions(null, $userid)) {
    // Error setting up page permissions.
    print_error('error:viewusergoals', 'totara_hierarchy');
}

extract($permissions);

// Set up the page.
$PAGE->set_url(new moodle_url('/totara/hierarchy/prefix/goal/mygoals.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

/* Define the "Custom Goals" embedded report */
$shortname = 'goal_custom_fields';
$config = (new rb_config())->set_sid($sid)->set_embeddata($data);
if (!$report = reportbuilder::create_embedded($shortname, $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();
/* End of defining the report */

if (!isset($USER->editing)) {
    $USER->editing = 0;
}
if ($PAGE->user_allowed_editing()) {
    $editbutton = $OUTPUT->edit_button($PAGE->url);
    $PAGE->set_button($editbutton . $PAGE->button);

    if ($edit == 1 && confirm_sesskey()) {
        $USER->editing = 1;
        $url = new moodle_url($PAGE->url, array('notifyeditingon' => 1));
        redirect($url);
    } else if ($edit == 0 && confirm_sesskey()) {
        $USER->editing = 0;
        redirect($PAGE->url);
    }
} else {
    $USER->editing = 0;
}

if (\totara_job\job_assignment::is_managing($USER->id, $userid)) {
    $username = fullname($DB->get_record('user', array('id' => $userid)));
    $strmygoals = get_string('mygoalsteam', 'totara_hierarchy', $username);
    if (totara_feature_visible('myteam')) {
        $myteamurl = new moodle_url('/my/teammembers.php', array());
        $PAGE->set_totara_menu_selected('\totara_core\totara\menu\myteam');
        $PAGE->navbar->add(get_string('team', 'totara_core'), $myteamurl);
    }
} else {
    $strmygoals = get_string('goals', 'totara_hierarchy');
    $PAGE->set_totara_menu_selected('\totara_hierarchy\totara\menu\mygoals');
}
$PAGE->navbar->add($strmygoals);
$PAGE->set_title($strmygoals);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);

if (!($can_view_company || $can_view_personal)) {
    // If you can't see any goals you shouldn't be on this page.
    print_error('error:viewusergoals', 'totara_hierarchy');
}

if ($format != '') {
    $report->export_data($format);
    die;
}

// Setup lightbox.
local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));

$showhide_params = array('userid' => $userid, 'display' => !$display);
$showhide_url = new moodle_url('/totara/hierarchy/prefix/goal/mygoals.php', $showhide_params);
$showhide_text = $display ? get_string('hidedetails', 'totara_hierarchy') : get_string('showdetails', 'totara_hierarchy');
$personalshowhide_text = $personaldisplay ? get_string('hidedetails', 'totara_hierarchy') : get_string('showdetails', 'totara_hierarchy');

$args = array('args'=>'{"id":"'.$userid.'",
                        "showhide_url":"'.$showhide_url->out().'",
                        "showhide_text":"'.$showhide_text.'",
                        "sesskey":"'.sesskey().'"}');

// Include position user js modules.
$PAGE->requires->strings_for_js(array('addgoal', 'assigngoals'), 'totara_hierarchy');
$PAGE->requires->strings_for_js(array('continue', 'cancel'), 'moodle');
$jsmodule = array(
    'name' => 'totara_assignindividual',
    'fullpath' => '/totara/hierarchy/prefix/goal/assign/individual_dialog.js',
    'requires' => array('json'));
$PAGE->requires->js_init_call('M.totara_assignindividual.init', $args, false, $jsmodule);

$renderer = $PAGE->get_renderer('totara_hierarchy');

$company = '';
if ($can_view_company) {

    // Set up the buttons for the company table.
    $company_edit = html_writer::start_tag('div', array('class' => 'buttons'));
    if ($can_edit_company) {
        // If the current user can add individual goal assignments, set up the button.
        $add_params = array('assignto' => $userid, 'assigntype' => GOAL_ASSIGNMENT_INDIVIDUAL);
        $addgoalurl = new moodle_url('/totara/hierarchy/prefix/goal/assign/find.php', $add_params);
        $add_button_text = get_string('addgoalcompany', 'totara_hierarchy');

        // Add new goals button.
        $company_edit .= html_writer::start_tag('div',
                array('class' => 'singlebutton'));
        $company_edit .= html_writer::start_tag('form',
                array('action' => $addgoalurl, 'method' => 'get'));
        $company_edit .= html_writer::start_tag('div');
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'submit', 'id' => "show-assignedgoals-dialog", 'value' => $add_button_text));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "assignto", 'value' => $userid));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "assigntype", 'value' => GOAL_ASSIGNMENT_INDIVIDUAL));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "nojs", 'value' => '1'));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "returnurl", 'value' => qualified_me()));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "s", 'value' => sesskey()));
        $company_edit .= html_writer::end_tag('div');
        $company_edit .= html_writer::end_tag('form');
        $company_edit .= html_writer::end_tag('div');
    }

    $company_edit .= html_writer::start_tag('div', array('class' => 'companygoals detailswrapper'));
    if ($DB->record_exists('goal_user_assignment', array('userid' => $userid))) {
        // Show details button.

        $company_edit .= html_writer::start_tag('div',
                array('class' => 'singlebutton'));
        $company_edit .= html_writer::start_tag('form',
                array('action' => $showhide_url, 'method' => 'get'));
        $company_edit .= html_writer::start_tag('div');
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'submit', 'id' => "showhide-goal-details", 'value' => $showhide_text));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "userid", 'value' => $userid));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "display", 'value' => !$display));
        $company_edit .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'name' => "personaldisplay", 'value' => $personaldisplay));
        $company_edit .= html_writer::end_tag('div');
        $company_edit .= html_writer::end_tag('form');
        $company_edit .= html_writer::end_tag('div');
    }
    $company_edit .= html_writer::end_tag('div');

    if ($can_edit_company) {
        // The view goals details link to hierarchy framework pages.
        $detailsurl = new moodle_url('/totara/hierarchy/framework/index.php', array('prefix' => 'goal', 'readonly' => true));
        $company_edit .= html_writer::link($detailsurl, get_string('companygoaldetails', 'totara_hierarchy'));
    }

    $company_edit .= html_writer::end_tag('div');

    // Set upt the title and edit button.
    $company .= html_writer::start_tag('div', array('id' => 'companygoals'));
    $company .= $OUTPUT->heading(get_string('companygoals', 'totara_hierarchy'), 3) . $company_edit;

    // Set up the company goal data.
    $company .= $renderer->mygoals_company_table($userid, $can_edit[GOAL_ASSIGNMENT_INDIVIDUAL], $display);
    $company .= html_writer::end_tag('div');
}

$personal = '';
if ($can_view_personal) {
    if ($can_edit_personal) {
        // Set up the personal goal data.
        $personal_edit_url = new moodle_url('/totara/hierarchy/prefix/goal/item/edit_personal.php', array('userid' => $userid));
        $personal_edit = $OUTPUT->single_button($personal_edit_url, get_string('addgoalpersonal', 'totara_hierarchy'), 'get');

        $personal_edit .= html_writer::start_tag('div', array('class' => 'personalgoals detailswrapper'));
        if ($DB->record_exists('goal_personal', array('userid' => $userid))) {
            // Show details button.

            $personal_edit .= html_writer::start_tag('div',
                    array('class' => 'singlebutton'));
            $personal_edit .= html_writer::start_tag('form',
                    array('action' => $showhide_url, 'method' => 'get'));
            $personal_edit .= html_writer::start_tag('div');
            $personal_edit .= html_writer::empty_tag('input',
                    array('type' => 'submit', 'id' => "showhide-personalgoal-details", 'value' => $personalshowhide_text));
            $personal_edit .= html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => "userid", 'value' => $userid));
            $personal_edit .= html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => "display", 'value' => $display));
            $personal_edit .= html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => "personaldisplay", 'value' => !$personaldisplay));
            $personal_edit .= html_writer::end_tag('div');
            $personal_edit .= html_writer::end_tag('form');
            $personal_edit .= html_writer::end_tag('div');
        }
         $personal_edit .= html_writer::end_tag('div');

    } else {
        $personal_edit = '';
    }

    // Set up title and add goals button.
    $personal .= html_writer::start_tag('div', array('id' => 'personalgoals'));
    $personal .= $OUTPUT->heading(get_string('personalgoals', 'totara_hierarchy'), 3);
    $personal .= html_writer::start_tag('div', array('class' => 'buttons'));
    $personal .= $personal_edit;
    $personal .= html_writer::end_tag('div');

    // Set up table.
    $personal .= $renderer->mygoals_personal_table($userid, $can_edit, $personaldisplay);
    $personal .= html_writer::end_tag('div');
}

$reportrenderer = $PAGE->get_renderer('totara_reportbuilder');

// Output everything.
echo $OUTPUT->header();
echo $OUTPUT->heading($strmygoals);
echo $company;
echo html_writer::empty_tag('br');
echo $personal;
$reportrenderer->export_select($report, $sid);
echo $OUTPUT->footer();
