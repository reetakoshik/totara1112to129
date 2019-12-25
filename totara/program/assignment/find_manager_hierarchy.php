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
 * @subpackage program
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/job/dialog/assign_manager.php');

$PAGE->set_context(context_system::instance());
require_login();

// Get program id and check capabilities.
$programid = required_param('programid', PARAM_INT);
require_capability('totara/program:configureassignments', program_get_context($programid));

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);


// Get ids of already selected items.
$selected = optional_param('selected', array(), PARAM_SEQUENCE);
$removed = optional_param('removed', array(), PARAM_SEQUENCE);

$managerid = optional_param('parentid', false, PARAM_ALPHANUM);

$selectedids = totara_prog_removed_selected_ids($programid, $selected, $removed, ASSIGNTYPE_MANAGERJA);

$allselected = array();
$usernamefields = get_all_user_name_fields(true);
if (!empty($selectedids)) {
    list($selectedsql, $selectedparams) = $DB->get_in_or_equal($selectedids);
    // Query job_assignment and user table so we can get names on options already selected.
    $sql = "SELECT ja.id, ja.userid, ja.fullname AS jobname, ja.idnumber, u.email, " . $usernamefields . "
                  FROM {job_assignment} ja
                  JOIN {user} u ON u.id=ja.userid
                 WHERE ja.id " . $selectedsql;
    $allselected = $DB->get_records_sql($sql, $selectedparams);
}

// We need a selected array that matches the format of items in the dialog.
$finalselected = array();
foreach ($allselected as $manager) {
    $job = clone($manager);
    $job->fullname = $manager->jobname;
    $manager->fullname = totara_job_display_user_job($manager, $job);
    $manager->jaid = $manager->id;
    $manager->id = $manager->userid . '-' . $manager->id;
    $finalselected[$manager->id] = $manager;
}

// Don't let them remove the currently selected ones.
$unremovable = $finalselected;

$dialog = new totara_job_dialog_assign_manager(0, $managerid);

$dialog->set_as_multi_item(true);
$dialog->urlparams['programid'] = $programid;
$dialog->restrict_to_current_managers(true);
$dialog->do_not_create_empty(true);
$dialog->load_data();

// Set disabled/selected items.
$dialog->selected_items = $finalselected;

// Set unremovable items.
$dialog->unremovable_items = $unremovable;

// Set title.
$dialog->selected_title = 'itemstoadd';

$dialog->select_title = '';

// Display page
echo $dialog->generate_markup();
