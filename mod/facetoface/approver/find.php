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
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

require_login();
require_sesskey();

$cid = required_param('cid', PARAM_INT);
$selected = optional_param('selected', null, PARAM_SEQUENCE);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$context = context_course::instance($cid);
$PAGE->set_context($context);
require_capability('moodle/course:manageactivities', $context);
\mod_facetoface\approver::require_active_admin();

[$disable_items, $select_items, $availableusers] = \mod_facetoface\approver::find_managers($selected);

// Limit results to 1 more than the maximum number that might be displayed.
// there is no point returning any more as we will never show them.
$dialog = new totara_dialog_content();
$dialog->selected_items = $select_items;
$dialog->disabled_items = $disable_items;
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->searchtype = 'user';
$dialog->items = $availableusers;
$dialog->urlparams = array('cid' => $cid);

echo $dialog->generate_markup();

