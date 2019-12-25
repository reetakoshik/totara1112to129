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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_reportbuilder
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_global_restriction.php');

$id = optional_param('id', 0, PARAM_INT); // Restriction id.
$deleteid = optional_param('deleteid', null, PARAM_ALPHANUMEXT);
$allusers = optional_param('allusers', null, PARAM_INT);

admin_externalpage_setup('rbmanageglobalrestrictions');

if (empty($CFG->enableglobalrestrictions)) {
    print_error('globalrestrictionsdisabled', 'totara_reportbuilder');
}

/** @var totara_reportbuilder_renderer|core_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

$restriction = new rb_global_restriction($id);
$returnurl = new moodle_url('/totara/reportbuilder/restrictions/index.php');

$assign = new totara_assign_reportbuilder_user('reportbuilder', $restriction, 'user');
$grouptypes = $assign->get_assignable_grouptype_names();
$grouptypes = array_merge(array("" => get_string('assigngroup', 'totara_core')), $grouptypes);

$continueurl = new moodle_url('/totara/reportbuilder/restrictions/edit_restrictedusers.php', array('id' => $id));

if ($allusers !== null) {
    require_sesskey();
    $data = new stdClass();
    $data->allusers = $allusers ? 1 : 0;
    $restriction->update($data);
    redirect($continueurl);
}

if ($deleteid) {
    require_sesskey();
    // TODO TL-6684: add delete confirmation here.
    list($grp, $aid) = explode("_", $deleteid);
    $assign->delete_assigned_group($grp, $aid);
    redirect($continueurl);
}

if ($restriction->allusers) {
    echo $output->edit_restriction_header($restriction, 'restrictedusers');
    echo html_writer::tag('p', get_string('restrictionallusers', 'totara_reportbuilder'));

    $allusersurl = new moodle_url('/totara/reportbuilder/restrictions/edit_restrictedusers.php',
        array('id' => $id, 'allusers' => 0, 'sesskey' => sesskey()));
    echo $output->single_button($allusersurl, get_string('restrictiondisableallusers', 'totara_reportbuilder'));

    echo $output->footer();
    die;
}

$module = 'reportbuilder';
$suffix = 'user';
totara_setup_assigndialogs($module, $id, true, '', $suffix);

echo $output->edit_restriction_header($restriction, 'restrictedusers');
echo html_writer::tag('p', get_string('restrictedusersdescription', 'totara_reportbuilder'));

$allusersurl = new moodle_url('/totara/reportbuilder/restrictions/edit_restrictedusers.php',
    array('id' => $id, 'allusers' => 1, 'sesskey' => sesskey()));
echo $output->single_button($allusersurl, get_string('restrictionenableallusers', 'totara_reportbuilder'));

echo $output->heading(get_string('assignedgroups', 'totara_reportbuilder'), 3);
echo html_writer::select($grouptypes, 'groupselector', null, null, array('class' => 'group_selector', 'itemid' => $id));

$currentassignments = $assign->get_current_assigned_groups();
echo $output->display_assigned_groups($currentassignments, $id, $suffix);

echo $output->heading(get_string('assignedusers', 'totara_reportbuilder'), 3);
echo $output->display_user_datatable();

echo $output->footer();
