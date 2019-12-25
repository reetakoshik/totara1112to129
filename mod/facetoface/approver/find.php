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
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

require_login();
require_sesskey();

// Check that approval_admin is active in facetoface_approvaloptions.
$settingsoptions = isset($CFG->facetoface_approvaloptions) ? $CFG->facetoface_approvaloptions : '';
$approvaloptions = explode(',', $settingsoptions);
if (!in_array('approval_admin', $approvaloptions)) {
    print_error('error:approvaladminnotactive', 'facetoface');
}

$cid = required_param('cid', PARAM_INT);
$selected = optional_param('selected', null, PARAM_SEQUENCE);

$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$context = context_course::instance($cid);
$PAGE->set_context($context);
require_capability('moodle/course:manageactivities', $context);

// Get guest user for exclusion purposes.
$guest = guest_user();

$disable_items = array();
$systemapprovers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
foreach ($systemapprovers as $sysapprover) {
    if (!empty($sysapprover)) {
        $disable_items[$sysapprover->id] = $sysapprover;
    }
}

$select_items = array();
if (!empty($selected)) {
    $activityapprovers = explode(',', $selected);

    foreach ($activityapprovers as $actapprover) {
        $item = $DB->get_record('user', array('id' => $actapprover));
        $item->fullname = fullname($item);
        $select_items[$item->id] = $item;
    }
}

// Load potential managers for this user.
$usernamefields = get_all_user_name_fields(true, 'u');
$availableusers = $DB->get_records_sql(
   "
        SELECT
            u.id, {$usernamefields}, u.email
        FROM
            {user} u
        WHERE
            u.deleted = 0
        AND u.suspended = 0
        AND u.id != ?
        ORDER BY
            u.firstname,
            u.lastname
    ",
    array($guest->id), 0, TOTARA_DIALOG_MAXITEMS + 1);

foreach ($availableusers as $user) {
    $user->fullname = fullname($user);
}

// Limit results to 1 more than the maximum number that might be displayed.
// there is no point returning any more as we will never show them.
if (!$nojs) {
    // Display the javascript version of the page.
    $dialog = new totara_dialog_content();
    $dialog->selected_items = $select_items;
    $dialog->disabled_items = $disable_items;
    $dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
    $dialog->searchtype = 'user';
    $dialog->items = $availableusers;
    $dialog->urlparams = array('cid' => $cid);

    echo $dialog->generate_markup();
}
