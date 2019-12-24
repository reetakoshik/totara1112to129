<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_userdata
 */

use \totara_userdata\userdata\manager;
use \totara_userdata\userdata\target_user;

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
require_login();

$user = $DB->get_record('user', array('id' => $id));

$syscontext = context_system::instance();
$context = context_user::instance($user->id, IGNORE_MISSING);
if (!$context) {
    $context = $syscontext;
}

require_capability('totara/userdata:viewinfo', $context);

$extra = \totara_userdata\local\util::get_user_extras($user->id);
$targetuser = new target_user($user);
$currentuser = ($user->id == $USER->id);

$PAGE->set_context($context);
$PAGE->set_url('/totara/userdata/user_info.php', array('id' => $id));
$PAGE->set_pagelayout('admin');

if ($user->deleted) {
    admin_externalpage_setup('userdatadeletedusers');
    $PAGE->set_url('/totara/userdata/user_info.php', array('id' => $id));
} else {
    $PAGE->set_title(get_string('userinfo', 'totara_userdata'));
    $PAGE->set_heading(fullname($user));

    if (!$currentuser) {
        $PAGE->navigation->extend_for_user($user);
        $PAGE->navbar->add(get_string('userinfo', 'totara_userdata'));
    } else {
        // We are looking at our own profile.
        $myprofilenode = $PAGE->settingsnav->find('myprofile', null);
        $userinfo = $myprofilenode->add(get_string('userinfo', 'totara_userdata'));
        $userinfo->make_active();}
}

$suspendedtypes = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'suspended', $extra->suspendedpurgetypeid);
$deletedtypes = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted', $extra->deletedpurgetypeid);

$defaultsuspendedpurgetypeid = get_config('totara_userdata', 'defaultsuspendedpurgetypeid');
$defaultdeletedpurgetypeid = get_config('totara_userdata', 'defaultdeletedpurgetypeid');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('userinfo', 'totara_userdata'));

/** @var \totara_userdata_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_userdata');
echo $renderer->user_id_card($user, true);

echo '<dl class="dl-horizontal">';
echo '<dt>' . get_string('purgesuserall', 'totara_userdata') . '</dt>';
echo '<dd>';
$allcount = $DB->count_records('totara_userdata_purge', array('userid' => $user->id));
if (!$allcount) {
    echo get_string('none');
} else {
    echo html_writer::link(new moodle_url('/totara/userdata/purges.php', array('userid' => $user->id)), $allcount);
}
echo '</dd>';
echo '<dt>' . get_string('exportsuserall', 'totara_userdata') . '</dt>';
echo '<dd>';
$allcount = $DB->count_records('totara_userdata_export', array('userid' => $user->id));
if (!$allcount) {
    echo get_string('none');
} else {
    echo html_writer::link(new moodle_url('/totara/userdata/exports.php', array('userid' => $user->id)), $allcount);
}
echo '</dd>';
echo '</dl>';

echo $OUTPUT->heading(get_string('purgeoriginmanual', 'totara_userdata'), 3);
echo '<dl class="dl-horizontal">';
echo '<dt>' . get_string('purgesuserpending', 'totara_userdata') . '</dt>';
echo '<dd>';
$pendingcount = $DB->count_records('totara_userdata_purge', array('userid' => $user->id, 'result' => null));
if (!$pendingcount) {
    echo get_string('none');
} else {
    echo $pendingcount;
}
echo '</dd>';
echo '</dl>';

if (has_capability('totara/userdata:purgemanual', $syscontext) and manager::get_purge_types($targetuser->status, 'manual')) {
    echo markdown_to_html(get_string('purgemanualschedule_desc', 'totara_userdata'));

    $url = new moodle_url('/totara/userdata/purge_manually.php', array('id' => $user->id));
    echo $OUTPUT->single_button($url, get_string('selectpurgetype', 'totara_userdata'));
}

echo $OUTPUT->heading(get_string('purgeautomatic', 'totara_userdata'), 3);
echo '<dl class="dl-horizontal">';
echo '<dt>' . get_string('purgeoriginsuspended', 'totara_userdata') . '</dt>';
echo '<dd>';
if ($extra->suspendedpurgetypeid) {
    $url = new \moodle_url('/totara/userdata/purge_type.php', array('id' => $extra->suspendedpurgetypeid));
    $purgetypename = html_writer::link($url, $suspendedtypes[$extra->suspendedpurgetypeid]);
    if ($targetuser->status == $targetuser::STATUS_SUSPENDED) {
        $suspendedpurgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $extra->suspendedpurgetypeid), '*', MUST_EXIST);
        if ($extra->timesuspendedpurged === null or $extra->timesuspendedpurged < $extra->timesuspended or $extra->timesuspendedpurged < $suspendedpurgetype->timechanged) {
            echo $purgetypename . ' ' . html_writer::span(get_string('purgeautopending', 'totara_userdata'), 'dimmed');
        } else {
            echo $purgetypename . ' ' . html_writer::span(get_string('purgeautocompleted', 'totara_userdata', array('timefinished' => userdate($extra->timesuspendedpurged, get_string('strftimedatetimeshort')))), 'dimmed_text');
        }
    } else {
        echo $purgetypename;
    }
} else if ($targetuser->status == $targetuser::STATUS_ACTIVE and $defaultsuspendedpurgetypeid) {
    // Defaults are applied to active accounts only
    $url = new \moodle_url('/totara/userdata/purge_type.php', array('id' => $defaultsuspendedpurgetypeid));
    echo html_writer::link($url, get_string('purgeautodefault', 'totara_userdata', $suspendedtypes[$defaultsuspendedpurgetypeid]));
} else {
    echo get_string('none');
}
if (has_capability('totara/userdata:purgesetsuspended', $syscontext) and $suspendedtypes) {
    $editurl = new moodle_url('/totara/userdata/purge_set_suspended.php', array('id' => $user->id));
    echo ' ' . $OUTPUT->action_icon($editurl, new \core\output\flex_icon('settings', array('alt' => get_string('edit'))));

}
echo '</dd>';
echo '<dt>' . get_string('purgeorigindeleted', 'totara_userdata') . '</dt>';
echo '<dd>';
if ($extra->deletedpurgetypeid) {
    $url = new \moodle_url('/totara/userdata/purge_type.php', array('id' => $extra->deletedpurgetypeid));
    $purgetypename = html_writer::link($url, $deletedtypes[$extra->deletedpurgetypeid]);
    if ($targetuser->status == $targetuser::STATUS_DELETED) {
        $deletedpurgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $extra->deletedpurgetypeid), '*', MUST_EXIST);
        if ($extra->timedeletedpurged === null or $extra->timedeletedpurged < $extra->timedeleted or $extra->timedeletedpurged < $deletedpurgetype->timechanged) {
            echo $purgetypename . ' ' . html_writer::span(get_string('purgeautopending', 'totara_userdata'), 'dimmed_text');
        } else {
            echo $purgetypename . ' ' . html_writer::span(get_string('purgeautocompleted', 'totara_userdata', array('timefinished' => userdate($extra->timedeletedpurged, get_string('strftimedatetimeshort')))), 'dimmed_text');
        }
    } else {
        echo $purgetypename;
    }
} else if ($targetuser->status != $targetuser::STATUS_DELETED and $defaultdeletedpurgetypeid) {
    // Defaults are applied to active and suspedned accounts only.
    $url = new \moodle_url('/totara/userdata/purge_type.php', array('id' => $defaultdeletedpurgetypeid));
    echo html_writer::link($url, get_string('purgeautodefault', 'totara_userdata', $deletedtypes[$defaultdeletedpurgetypeid]));
} else {
    echo get_string('none');
}
if (has_capability('totara/userdata:purgesetdeleted', $syscontext) and $deletedtypes) {
    $editurl = new moodle_url('/totara/userdata/purge_set_deleted.php', array('id' => $user->id));
    echo ' ' . $OUTPUT->action_icon($editurl, new \core\output\flex_icon('settings', array('alt' => get_string('edit'))));

}
echo '</dd>';
echo '</dl>';

echo $OUTPUT->heading(get_string('audit', 'totara_userdata'), 3);
echo markdown_to_html(get_string('audit_desc', 'totara_userdata'));
// Use post to prevent accidental execution of data.
$url = new moodle_url('/totara/userdata/purge_manually.php', array('id' => $user->id));
echo $OUTPUT->single_button($CFG->wwwroot . '/totara/userdata/audit.php?id=' . $user->id, get_string('auditexecute', 'totara_userdata'), 'post');

echo $OUTPUT->footer();