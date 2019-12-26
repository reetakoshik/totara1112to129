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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');

require_login();
require_sesskey();

$fid = required_param('fid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$seminar = new \mod_facetoface\seminar($fid);
$cm = get_coursemodule_from_instance('facetoface', $seminar->get_id(), $seminar->get_course(), false, MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
require_capability('mod/facetoface:view', $context);

$managers = \mod_facetoface\approver::get_managers($userid);

$dialog = new totara_dialog_content();
$dialog->searchtype = 'user';
$dialog->items = $managers;
$dialog->customdata['current_user'] = $userid;
$dialog->urlparams['userid'] = $userid;
$dialog->urlparams['fid'] = $fid;

echo $dialog->generate_markup();
