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
 * @author Eugene Venter<eugene@catalyst.net.nz>
 * @package block
 * @subpackage totara_program_completion
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_programs.class.php');
require_once($CFG->dirroot.'/blocks/totara_program_completion/locallib.php');

$blockid = required_param('blockid', PARAM_INT);  // Block instance id.
$selectedids = optional_param('selected', '', PARAM_SEQUENCE);
$categoryid = optional_param('parentid', 'cat0', PARAM_ALPHANUM); // Category id.
// Strip cat from begining of categoryid.
$categoryid = (int) substr($categoryid, 3);

require_login();
$PAGE->set_context(context_system::instance());

// Convert to an array with no blanks.
$selectedids = array_filter(explode(',', $selectedids));
if (empty($selectedids)) {
    $selected = array();
} else {
    $visible_programs = prog_get_programs($categoryid, '', 'p.id, p.fullname');
    $selectedids = array_flip($selectedids);
    $selected = array_intersect_key($visible_programs, $selectedids);
}

// Setup dialog.

// Load dialog content generator.
$dialog = new totara_dialog_content_programs($categoryid);

// Set type to multiple.
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;

$dialog->selected_title = 'itemstoadd';

// Show all programs.
$dialog->requirecompletioncriteria = false;
$dialog->requirecompletion = false;

// Add data.
$dialog->load_programs();

// Set selected items.
$dialog->selected_items = $selected;

// Addition url parameters.
$dialog->urlparams = array('blockid' => $blockid);

// Display page.
echo $dialog->generate_markup();
