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

require_sesskey();

$fid = required_param('fid', PARAM_INT);

$facetoface = $DB->get_record('facetoface', array('id' => $fid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course));
$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/facetoface:editevents', $context);

$sessionsql = "UPDATE {facetoface_sessions}
                  SET selfapproval = 0
                WHERE facetoface = :fid";
$DB->execute($sessionsql, array('fid' => $fid));

$returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $fid));
totara_set_notification(get_string('dismissedwarning', 'mod_facetoface'), $returnurl, array('class' => 'notifysuccess'));
