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

use \totara_userdata\local\purge_type;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('userdatapurgetypes');
require_capability('totara/userdata:config', context_system::instance());

$purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $id));
$returnurl = new moodle_url('/totara/userdata/purge_types.php');

if (!$purgetype or !purge_type::is_deletable($id)) {
    $message = get_string('errorpurgetypedelete', 'totara_userdata');
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_ERROR);
}

if ($confirm) {
    require_sesskey();
    $success = purge_type::delete($id);
    if ($success) {
        redirect($returnurl);
    }
    $message = get_string('errorpurgetypedelete', 'totara_userdata');
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('purgetypedelete', 'totara_userdata'));

$a = format_string($purgetype->fullname);
$message = get_string('purgetypedeleteconfirm', 'totara_userdata', $a);

$yesurl = new moodle_url('/totara/userdata/purge_type_delete.php', array('id' => $id, 'confirm' => 1, 'sesskey' => sesskey()));
$yebutton = new single_button($yesurl, get_string('delete'), 'post', true);
echo $OUTPUT->confirm($message, $yebutton, $returnurl);

echo $OUTPUT->footer();
