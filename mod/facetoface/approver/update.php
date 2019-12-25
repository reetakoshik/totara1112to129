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
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

require_sesskey();
ajax_require_login();

// Check that approval_admin is active in facetoface_approvaloptions.
$settingsoptions = isset($CFG->facetoface_approvaloptions) ? $CFG->facetoface_approvaloptions : '';
$approvaloptions = explode(',', $settingsoptions);
if (!in_array('approval_admin', $approvaloptions)) {
    print_error('error:approvaladminnotactive', 'facetoface');
}

$cid = required_param('cid', PARAM_INT);
// Users is optional as the no users may have been selected.
$users = optional_param('users', '', PARAM_SEQUENCE);

$context = context_course::instance($cid);
$PAGE->set_context($context);
require_capability('moodle/course:manageactivities', $context);

// The JS code relies on this div, so even if no users are selected we must print it.
$out = html_writer::start_tag('div', array('id' => 'activityapproverbox', 'class' => 'activity_approvers'));

if (!empty($users)) {
    foreach (explode(',', trim($users, ',')) as $userid) {
        $user = core_user::get_user($userid);
        $out .= facetoface_display_approver($user, true);
    }
}

$out .= html_writer::end_tag('div');

echo "DONE{$out}";
exit();
