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

require('../../config.php');

$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/userdata/purge_manually_cancel.php', array('id' => $id));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(''); // This page should always redirect

require_login();
require_capability('totara/userdata:purgemanual', \context_system::instance());
require_sesskey();

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/totara/userdata/purges.php');
}

$purge = $DB->get_record('totara_userdata_purge', array('id' => $id, 'usercreated' => $USER->id, 'origin' => 'manual'));
if (!$purge or $purge->result !== null) {
    $message = get_string('errorpurgecancel', 'totara_userdata');
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_ERROR);
}

$update = new stdClass();
$update->id = $purge->id;
$update->result = \totara_userdata\userdata\item::RESULT_STATUS_CANCELLED;
$DB->update_record('totara_userdata_purge', $update);

$message = get_string('purgecancelled', 'totara_userdata');
redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
