<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_user
 */

require_once('../config.php');
require_once("$CFG->dirroot/lib/authlib.php");
require_once("$CFG->dirroot/user/editlib.php");
require_once("$CFG->dirroot/user/profile/lib.php");
require_once("$CFG->dirroot/user/lib.php");

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // md5 confirmation hash of user id.
$returnto = optional_param('returnto', '', PARAM_ALPHANUMEXT);
$customreturn = optional_param('returnurl', '', PARAM_LOCALURL);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/user/action.php', array('id' => $id, 'action' => $action, 'returnto' => $returnto, 'returnurl' => $customreturn));
$PAGE->set_pagelayout('noblocks'); // All we need is to confirm delete/undelete action, there is no need for navigation here.

require_login();

$user = $DB->get_record('user', array('id' => $id, 'mnethostid' => $CFG->mnet_localhost_id), '*', MUST_EXIST);
$returnurl = useredit_get_return_url($user, $returnto, null, $customreturn);

$fullname = fullname($user, true);

// Process any actions first.

if ($action === 'confirm') {
    require_capability('moodle/user:update', $context);
    require_sesskey();

    if ($user->deleted) {
        redirect($returnurl, get_string('userdeleted', 'core'), null, \core\notification::ERROR);
    }

    /** @var auth_plugin_base $auth */
    $auth = get_auth_plugin($user->auth);

    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        // Nothing to do.
        redirect($returnurl);
    }
    redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)), null, \core\notification::ERROR);
}

if ($action === 'delete') {
    require_capability('moodle/user:delete', $context);

    if (is_siteadmin($user->id)) {
        redirect($returnurl, get_string('useradminodelete', 'error'), null, \core\notification::ERROR);
    }

    if (!data_submitted() or $confirm !== md5($id)) {
        // The deletion must be confirmed.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));
        $continueurl = new moodle_url($PAGE->url, array('id' => $id, 'action' => 'delete', 'confirm' => md5($id), 'sesskey' => sesskey()));
        $continuebutton = new single_button($continueurl, get_string('delete'), 'post', true);
        $extra = $DB->get_record('totara_userdata_user', array('userid' => $user->id));
        $warning = get_string('deleteusercheckfull', 'totara_core', "'$fullname'");

        $defaultdeletedpurgetypeid = get_config('totara_userdata', 'defaultdeletedpurgetypeid');
        if ($defaultdeletedpurgetypeid or isset($extra->deletedpurgetypeid)) {
            $purgetypeid = isset($extra->deletedpurgetypeid) ? $extra->deletedpurgetypeid : $defaultdeletedpurgetypeid;
            $purgetypes = \totara_userdata\userdata\manager::get_purge_types(\totara_userdata\userdata\target_user::STATUS_DELETED, 'other'); // Other means any here.
            if (isset($purgetypes[$purgetypeid])) {
                // TODO: TL-16747 add better warning for purge types
                $warning .= '<br /><br /><strong>' . get_string('deletedpurgetype', 'totara_userdata') . ':</strong> ' . $purgetypes[$purgetypeid];
            }
        }

        echo $OUTPUT->confirm(
            $warning,
            $continuebutton,
            $returnurl
        );
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    if (!$user->deleted) {
        if (!delete_user($user)) {
            // Hmm could not delete the user, inform the current user.
            redirect($returnurl, get_string('deletednot', '', fullname($user, true)), null, \core\notification::ERROR);
        }
        // Remove stale sessions.
        \core\session\manager::gc();
    } else {
        // The user has already been deleted.
        // If it was a partial deletion then we want to do a full deletion now.
        if ($CFG->authdeleteusers !== 'partial' and strpos($user->email, '@') !== false) {
            // Do the real delete again - discard the username, idnumber and email.
            $trans = $DB->start_delegated_transaction();
            $DB->set_field('user', 'deleted', 0, array('id' => $user->id));
            $user->deleted = 0;
            delete_user($user);
            $trans->allow_commit();
        }
    }

    // Reload the user record and recalculate the return URL.
    $user = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
    $returnurl = useredit_get_return_url($user, $returnto, null, $customreturn);

    redirect($returnurl);
}

if ($action === 'undelete') {
    require_capability('totara/core:undeleteuser', $context);

    if (!$user->deleted) {
        // Already not deleted!
        redirect($returnurl);
    }
    if (!is_undeletable_user($user)) {
        // ensure we're not trying to undelete a legacy-deleted (hash in email) user
        redirect($returnurl, get_string('cannotundeleteuser', 'totara_core'), null, \core\notification::ERROR);
    }

    if (!data_submitted() or $confirm !== md5($id)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('undeleteuser', 'totara_core'));
        $continueurl = new moodle_url($PAGE->url, array('id' => $id, 'action' => 'undelete', 'confirm' => md5($id), 'sesskey' => sesskey()));
        $continuebutton = new single_button($continueurl, get_string('undelete', 'totara_core'), 'post', true);
        echo $OUTPUT->confirm(
            get_string('undeletecheckfull', 'totara_core', "'$fullname'"),
            $continuebutton,
            $returnurl
        );
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    if (!undelete_user($user)) {
        redirect($returnurl, get_string('undeletednotx', 'totara_core', $fullname), null, \core\notification::ERROR);
    }

    $user = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
    $returnurl = useredit_get_return_url($user, $returnto, null, $customreturn);

    redirect($returnurl, get_string('undeletedx', 'totara_core', $fullname), null, \core\notification::SUCCESS);
}

if ($action === 'suspend') {
    require_capability('moodle/user:update', $context);
    require_sesskey();

    if ($user->deleted) {
        redirect($returnurl, get_string('userdeleted', 'core'), null, \core\notification::ERROR);
    }
    if (is_siteadmin($user->id)) {
        redirect($returnurl, 'The admin user cannot be suspended', null, \core\notification::ERROR);
    }
    if ($USER->id == $user->id) {
        redirect($returnurl, 'You cannot suspend yourself!', null, \core\notification::ERROR);
    }

    if ($user->suspended != 1) {
        $user->suspended = 1;
        // Force logout.
        \core\session\manager::kill_user_sessions($user->id);
        user_update_user($user, false);

        \totara_core\event\user_suspended::create_from_user($user)->trigger();
    }

    redirect($returnurl);
}

if ($action === 'unsuspend') {
    require_capability('moodle/user:update', $context);
    require_sesskey();

    if ($user->deleted) {
        redirect($returnurl, get_string('userdeleted', 'core'), null, \core\notification::ERROR);
    }

    if ($user->suspended == 1) {
        $user->suspended = 0;
        user_update_user($user, false);
    }

    // Make sure user is not locked out.
    login_unlock_account($user);

    redirect($returnurl);
}

if ($action === 'unlock') {
    require_capability('moodle/user:update', $context);
    require_sesskey();

    if ($user->deleted) {
        redirect($returnurl, get_string('userdeleted', 'core'), null, \core\notification::ERROR);
    }

    login_unlock_account($user);

    redirect($returnurl);
}

throw new invalid_parameter_exception("Invalid user action requested: $action");