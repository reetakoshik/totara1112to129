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
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');

require_login();
require_sesskey();

// Check that facetoface_managerselect is set.
$facetoface_managerselect = isset($CFG->facetoface_managerselect) ? $CFG->facetoface_managerselect : 0;
if ($facetoface_managerselect != 1) {
    print_error('error:approvaladminnotactive', 'facetoface');
}

$fid = required_param('fid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$facetoface = $DB->get_record('facetoface', array('id' => $fid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course, false, MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
require_capability('mod/facetoface:view', $context);

// First check that the user really does exist and that they're not a guest.
$userexists = !isguestuser($userid) && $DB->record_exists('user', array('id' => $userid, 'deleted' => 0));

// Prepare an array of managers. If they can't see other users this will remain empty and they'll just get
// an empty request.
$managers = array();

$guest = guest_user();
// Load potential managers for this user.
$usernamefields = get_all_user_name_fields(true, 'u');
$sql = "SELECT u.id, u.email, {$usernamefields}
          FROM {user} u
         WHERE u.deleted = 0
           AND u.suspended = 0
           AND u.id != :guestid
           AND u.id != :userid
      ORDER BY {$usernamefields}";
$params = array(
    'guestid' => $guest->id,
    'userid' => $userid
);
// Limit results to 1 more than the maximum number that might be displayed
// there is no point returning any more as we will never show them.
$managers = $DB->get_records_sql($sql, $params, 0, TOTARA_DIALOG_MAXITEMS + 1);

foreach ($managers as $manager) {
    $manager->fullname = fullname($manager);
}

$dialog = new totara_dialog_content();
$dialog->searchtype = 'user';
$dialog->items = $managers;
$dialog->customdata['current_user'] = $userid;
$dialog->urlparams['userid'] = $userid;
$dialog->urlparams['fid'] = $fid;

echo $dialog->generate_markup();
