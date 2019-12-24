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

// Get program ID.
$programid = required_param('programid', PARAM_INT);

require_login();
require_sesskey();

// Check capabilities.
$context = context_program::instance($programid);
require_capability('totara/program:configureassignments', $context);
$PAGE->set_context($context);

// Already selected items
$selected = optional_param('selected', array(), PARAM_SEQUENCE);
$removed = optional_param('removed', array(), PARAM_SEQUENCE);

$selectedids = totara_prog_removed_selected_ids($programid, $selected, $removed, ASSIGNTYPE_COHORT);

// Get cohorts.
$contextids = array_filter($context->get_parent_context_ids(true),
    function($a) {return has_capability("moodle/cohort:view", context::instance_by_id($a));});
list($contextssql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_QM, 'param', true, null);

$sql = "SELECT id, name, idnumber FROM {cohort} WHERE contextid {$contextssql}";

// Add all current cohorts even if user would not be able to select them again - changed permissions or moved cohort.
if (!empty($selectedids)) {
    list($selectedsql, $selectedparams) = $DB->get_in_or_equal($selectedids);
    $selected = $DB->get_records_select('cohort', "id {$selectedsql}", $selectedparams, 'name, idnumber', 'id, name as fullname');
    $sql .= " OR (id {$selectedsql})";
    $params = array_merge($params, $selectedparams);
}
$sql .= " ORDER BY name ASC, idnumber ASC";

$items = $DB->get_records_sql($sql, $params, 0, TOTARA_DIALOG_MAXITEMS + 1);

// Check if we are dealing with a program or a certification.
$type = $DB->get_field('prog', 'certifid', array('id' => $programid));
$instancetype = empty($type) ? COHORT_ASSN_ITEMTYPE_PROGRAM : COHORT_ASSN_ITEMTYPE_CERTIF;

// Don't let them remove the currently selected ones
$unremovable = $selected;

///
/// Setup dialog
///

// Load dialog content generator; skip access, since it's checked above
$dialog = new totara_dialog_content();
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->searchtype = 'cohort';
$dialog->customdata['instancetype'] = $instancetype;
$dialog->customdata['instanceid'] = $programid;

$dialog->items = $items;

// Set disabled/selected items
$dialog->selected_items = $selected;

// Set unremovable items
$dialog->unremovable_items = $unremovable;

// Set title
$dialog->selected_title = 'itemstoadd';

$dialog->urlparams = array('programid' => $programid);

// Display
echo $dialog->generate_markup();
