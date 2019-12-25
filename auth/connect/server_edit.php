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
 * @package auth_connect
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('authconnectservers');

$server = $DB->get_record('auth_connect_servers', array('id' => $id), '*', MUST_EXIST);

$PAGE->navbar->add(
    get_string('serveredit', 'auth_connect'),
    new moodle_url('/auth/connect/server_edit.php', array('id' => $id))
);

$form = new auth_connect_form_server_edit(null, $server);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/auth/connect/index.php'));

} else if ($data = $form->get_data()) {
    \auth_connect\util::edit_server($data);
    redirect(new moodle_url('/auth/connect/index.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('serveredit', 'auth_connect'));

$form->display();

echo $OUTPUT->footer();
