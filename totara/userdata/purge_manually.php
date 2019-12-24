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
use \totara_userdata\local\purge;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$purgetypeid = optional_param('purgetypeid', 0, PARAM_INT);

$syscontext = context_system::instance();
$returnurl = new moodle_url('/totara/userdata/user_info.php', array('id' => $id));

$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/userdata/purge_manually.php', array('id' => $id, 'purgetypeid' => $purgetypeid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('purgemanually', 'totara_userdata'));

require_login();
require_capability('totara/userdata:purgemanual', $syscontext);

$user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
$targetuser = new target_user($user);
$purgetypes = manager::get_purge_types($targetuser->status, 'manual');

if (!isset($purgetypes[$purgetypeid])) {
    $purgetypeid = 0;
}

$selectform = new \totara_userdata\form\purge_manually(array('id' => $id));
if ($selectform->is_submitted()) {
    if ($selectform->is_cancelled()) {
        redirect($returnurl);
    }
    if ($data = $selectform->get_data()) {
        $purgetypeid = $data->purgetypeid;
    } else {
        $purgetypeid = 0;
    }
}
if (!$purgetypeid or !isset($purgetypes[$purgetypeid])) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('purgemanually', 'totara_userdata'));
    echo $selectform->render();
    echo $OUTPUT->footer();
    die;
}

if (purge::is_execution_pending('manual', $purgetypeid, $id, $syscontext->id)) {
    // This should not happen, but it should be fine because it is already pending.
    redirect($returnurl);
}

$currentdata = new stdClass();
$currentdata->id = $id;
$currentdata->purgetypeid = $purgetypeid;
$confirmform = new \totara_userdata\form\purge_manually_confirm($currentdata);
if ($confirmform->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $confirmform->get_data()) {
    \totara_userdata\local\purge_type::trigger_manual_purge($data->purgetypeid, $data->id, $syscontext->id);
    redirect($returnurl, get_string('purgemanuallytriggered', 'totara_userdata'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('purgemanually', 'totara_userdata'));
echo $confirmform->render();
echo $OUTPUT->footer();
