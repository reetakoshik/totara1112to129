<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_connect
 */

use \totara_connect\util;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/totara/connect/client_edit.php', array('id' => $id));

admin_externalpage_setup('totaraconnectclients');

if (empty($CFG->enableconnectserver)) {
    die;
}

$client = $DB->get_record('totara_connect_clients', array('id' => $id), '*', MUST_EXIST);
$client->cohortid = (int)$client->cohortid; // Use 0 for no cohort.

$positionframeworks = $DB->get_records_menu('totara_connect_client_pos_frameworks', array('clientid' => $client->id), '', 'fid, fid');
$client->positionframeworks = array_keys($positionframeworks);

$organisationframeworks = $DB->get_records_menu('totara_connect_client_org_frameworks', array('clientid' => $client->id), '', 'fid, fid');
$client->organisationframeworks = array_keys($organisationframeworks);

$form = new totara_connect_form_client_edit(null, $client);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/totara/connect/index.php'));

} else if ($data = $form->get_data()) {
    \totara_connect\util::edit_client($data);
    redirect(new moodle_url('/totara/connect/index.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('clientedit', 'totara_connect'));
echo util::warn_if_not_https();

$form->display();

echo $OUTPUT->footer();
