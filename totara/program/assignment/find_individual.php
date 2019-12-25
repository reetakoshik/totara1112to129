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
 * @package totara
 * @subpackage program
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');
require_once("{$CFG->dirroot}/totara/program/lib.php");

$PAGE->set_context(context_system::instance());
require_login();

// Get program id and check capabilities
$programid = required_param('programid', PARAM_INT);
$program_context = context_program::instance($programid);
require_capability('totara/program:configureassignments', $program_context);

// Items selected but not yet saved.
$selected = optional_param('selected', array(), PARAM_SEQUENCE);
$removed = optional_param('removed', array(), PARAM_SEQUENCE);

$selectedids = totara_prog_removed_selected_ids($programid, $selected, $removed, ASSIGNTYPE_INDIVIDUAL);

// Get all users
$guest = guest_user();
$usernamefields = get_all_user_name_fields(true);
$items = $DB->get_records_select('user', 'deleted = 0 AND suspended = 0 AND id != ?', array($guest->id), '',
    'id, ' . $usernamefields . ', email', 0, TOTARA_DIALOG_MAXITEMS + 1);

// We'll remove users from $selected whose id is not in $selectedids.
foreach ($items as $item) {
    $item->fullname = fullname($item);
}

// Get a list of all selected users
$allselected = [];
if (count($selectedids) > 0) {
    $usernamefields = get_all_user_name_fields(true);

    $batches = array_chunk($selectedids, $DB->get_max_in_params());

    foreach ($batches as $batch) {
        list($insql, $inparams) = $DB->get_in_or_equal($batch);
        $selecteditems = $DB->get_records_select('user', "deleted = 0 AND suspended = 0 AND id $insql", $inparams, '',
            'id, ' . $usernamefields . ', email');

        foreach ($selecteditems as $item) {
            $item->fullname = fullname($item);

            $allselected[$item->id] = $item;
        }
    }
}

// Don't let them remove the currently selected ones
$unremovable = $allselected;

///
/// Setup dialog
///

// Load dialog content generator; skip access, since it's checked above
$dialog = new totara_dialog_content();

$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;

$dialog->items = $items;

// Set disabled/selected items
$dialog->selected_items = $allselected;

// Set unremovable items
$dialog->unremovable_items = $unremovable;

// Set title
$dialog->selected_title = 'itemstoadd';
$dialog->searchtype = 'user';

// Addition url parameters
$dialog->urlparams = array('programid' => $programid);

$dialog->set_context($program_context);

// Display
echo $dialog->generate_markup();
