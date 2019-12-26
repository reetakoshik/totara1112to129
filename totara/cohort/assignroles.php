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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/totaratablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/cohort/locallib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->dirroot. '/lib/accesslib.php');

require_login();
// According T-11067 'moodle/role:assign' capability allow with system context only.
$contextsystem = context_system::instance();
require_capability('moodle/role:assign', $contextsystem);

$id = required_param('id', PARAM_INT);
$roles = optional_param_array('roles', array(), PARAM_INT);

$cohort  = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);
$contextcohort = context::instance_by_id($cohort->contextid, MUST_EXIST);

admin_externalpage_setup('cohorts');

// Get list of all roles.
$assignableroles = get_assignable_roles($contextcohort, ROLENAME_BOTH, false);

$success = '';
if (($data = data_submitted()) && confirm_sesskey()) {
    // Remove any roles which are not assignable_roles.
    foreach (array_diff_key($roles, $assignableroles) as $key => $value) {
        unset($roles[$key]);
    }
    $success = totara_cohort_process_assigned_roles($cohort->id, $roles);
}

// Get list of all roles assigned to this cohort.
$rolesassigned = array_keys(totara_get_cohort_roles($cohort->id));

$PAGE->set_context($contextsystem);
$PAGE->set_url('/totara/cohort/assignroles.php', array('id' => $id));
$PAGE->set_title(format_string($cohort->name));
$PAGE->set_heading(format_string($cohort->name));

$strheading = get_string('assignroles', 'totara_cohort');
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);

echo $OUTPUT->header();

$PAGE->requires->js_call_amd('totara_cohort/assignroles', 'init');

echo cohort_print_tabs('roles', $cohort->id, $cohort->cohorttype, $cohort);

// Assign roles header.
echo $OUTPUT->heading(get_string('assignroles', 'totara_cohort'), 3);

// Notify result operation.
if ($success) {
    echo $OUTPUT->notification(get_string('updatedrolesuccessful','totara_cohort'), 'notifysuccess');
} else if ($success === false) {
    echo $OUTPUT->notification(get_string('updatedroleunsuccessful','totara_cohort'));
}

echo html_writer::tag('p', get_string('instructions:assignmentroles', 'totara_cohort', $contextcohort->get_context_name()));
echo html_writer::start_tag('form', array('name' => 'form_cohort_roles', 'method' => 'post'));
echo html_writer::tag('div', '', array('class' => 'hide', 'id' => 'noticeupdate'));
$table = new totara_table('cohort-assignroles');
$table->define_baseurl(new moodle_url('/totara/cohort/assignroles.php', array('id' => $id)));
$table->set_attribute('class', ' flexible fullwidth generalbox');

$columns = array ('selectedroles', 'role', 'context');
$headers = array (
    html_writer::link(null, get_string('selectallornone', 'form'), array('id' => 'selectallnoneroles')),
    get_string('role', 'totara_cohort'),
    get_string('context', 'role')
);
$table->define_columns($columns);
$table->define_headers($headers);
$table->setup();

$attributes = array('id' => 'updateroles', 'type' => 'submit', 'value' => get_string('updateroles', 'totara_cohort'));
$updatebutton = html_writer::empty_tag('input', $attributes);
$table->add_toolbar_content($updatebutton, 'left' , 'top', 1);

// Roles in the system context.
foreach ($assignableroles as $roleid => $rolename) {
    $data = array();
    $checked = in_array($roleid, $rolesassigned);
    $checkbox = html_writer::checkbox('roles['.$roleid.']', $contextcohort->id, $checked, '', array('class' => 'selectedroles'));
    $data[] = $checkbox;
    $url = new moodle_url('/' . $CFG->admin . '/roles/assign.php', array('contextid' => $contextcohort->id, 'roleid' => $roleid));
    $data[] = html_writer::link($url, $rolename);
    $data[] = $contextcohort->get_context_name();
    $table->add_data($data);
}
$table->get_no_records_message(get_string('norolestoassign', 'totara_cohort'));
$table->finish_html();
echo html_writer::empty_tag('input', array('name' => 'id', 'type' => 'hidden', 'value' => $id));
echo html_writer::empty_tag('input', array('name' => 'sesskey', 'type' => 'hidden', 'value' => sesskey()));
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
