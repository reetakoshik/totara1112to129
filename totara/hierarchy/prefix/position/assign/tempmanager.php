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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage hierarchy
 * @deprecated since 9.0
 */

/**
 * DEPRECATED FILE
 *
 * Deprecated from 9.0 and will be removed in a future release. Assigning temporary managers now needs to be
 * done via job assignments.
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');

error_log('totara/hierarchy/prefix/position/assign/tempmanager.php has been deprecated. Please update your code.');

$userid = required_param('userid', PARAM_INT);

/*
 * Setup / loading data.
 */

// Setup page.
$PAGE->set_context(context_system::instance());
require_login(null, false, null, false, true);

// First check that the user really does exist and that they're not a guest.
$userexists = !isguestuser($userid) && $DB->record_exists('user', array('id' => $userid, 'deleted' => 0));

// Will return no items if user does not have permissions.
$currentmanagerid = 0;
$managers = array();

$canedittempmanager = false;
if ($userexists && !empty($CFG->enabletempmanagers)) {
    $personalcontext = context_user::instance($userid);
    if (has_capability('totara/core:delegateusersmanager', $personalcontext)) {
        $canedittempmanager = true;
    } else if ($USER->id == $userid && has_capability('totara/core:delegateownmanager', $personalcontext)) {
        $canedittempmanager = true;
    } else if (totara_job_can_edit_job_assignments($userid)) {
        $canedittempmanager = true;
    }
}

if ($canedittempmanager) {
// Get guest user for exclusion purposes.
    $guest = guest_user();

// Load potential managers for this user.
    // The below will only check for the manager id the user's first job assignment.
    // To get around this, any custom code should be using the job assignment code.
    $job_assignment = \totara_job\job_assignment::get_first($userid);
    $currentmanagerid = $job_assignment->managerid;
    if (empty($currentmanagerid)) {
        $currentmanagerid = 0;
    }
    $usernamefields = get_all_user_name_fields(true, 'u');
    if (empty($CFG->tempmanagerrestrictselection)) {
        // All users.
        $sql = "SELECT u.id, u.email, {$usernamefields}
              FROM {user} u
             WHERE u.deleted = 0
               AND u.suspended = 0
               AND u.id NOT IN(?, ?, ?)
          ORDER BY {$usernamefields}, u.id";
    } else {
        $sql = "SELECT DISTINCT u.id, u.email, {$usernamefields}
              FROM {job_assignment} staffja
              JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
              JOIN {user} u ON managerja.userid = u.id
             WHERE u.deleted = 0
               AND u.suspended = 0
               AND u.id NOT IN(?, ?, ?)
          ORDER BY {$usernamefields}, u.id";
    }
    $managers = $DB->get_records_sql($sql, array($guest->id, $userid, $currentmanagerid));
}

foreach ($managers as $manager) {
    $manager->fullname = fullname($manager);
}

/*
 * Display page.
 */

$dialog = new totara_dialog_content();
$dialog->searchtype = 'temporary_manager';
$dialog->items = $managers;
$dialog->disabled_items = array($userid => true, $currentmanagerid => true);
$dialog->customdata['current_user'] = $userid;
$dialog->customdata['current_manager'] = $currentmanagerid;
$dialog->urlparams['userid'] = $userid;

echo $dialog->generate_markup();
