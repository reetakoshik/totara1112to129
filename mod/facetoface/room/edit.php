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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('modfacetofacerooms');

if ($id) {
    $room = $DB->get_record('facetoface_room', array('id' => $id, 'custom' => 0), '*', MUST_EXIST);
} else {
    $room = false;
}

$roomlisturl = new moodle_url('/mod/facetoface/room/manage.php');

$form = facetoface_process_room_form($room, false, false,
    function() use ($roomlisturl, $id) {
        if (!$id) {
            $successstr = 'roomcreatesuccess';
        } else {
            $successstr = 'roomupdatesuccess';
        }
        totara_set_notification(get_string($successstr, 'facetoface'), $roomlisturl, array('class' => 'notifysuccess'));
    },
    function() use ($roomlisturl) {
        redirect($roomlisturl);
    }
);

$url = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));

if ($id == 0) {
    $pageheading = get_string('addroom', 'facetoface');
} else {
    $pageheading = get_string('editroom', 'facetoface');
}

echo $OUTPUT->header();

echo $OUTPUT->heading($pageheading);

$form->display();

echo $OUTPUT->footer();
