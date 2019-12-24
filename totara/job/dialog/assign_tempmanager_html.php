<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_job
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/job/dialog/assign_manager.php');
require_once($CFG->dirroot . '/totara/job/lib.php');

$userid = required_param('userid', PARAM_INT);
$managerid = optional_param('parentid', false, PARAM_ALPHANUM);
$usualmanagerid = optional_param('usualmgrid', 0, PARAM_INT);

require_login(null, false, null, false, true);

// First check that the user really does exist and that they're not a guest.
$userexists = !isguestuser($userid) && $DB->record_exists('user', array('id' => $userid, 'deleted' => 0));

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

if (!$canedittempmanager) {
    print_error('nopermissions', '', '', 'Assign temporary managers');
}

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);

$dialog = new totara_job_dialog_assign_manager($userid, $managerid, $usualmanagerid);
$dialog->load_data();

echo $dialog->generate_markup();
