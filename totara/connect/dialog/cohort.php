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

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/cohort/lib.php');

$selected = optional_param('selected', null, PARAM_SEQUENCE);
$instanceid = required_param('instanceid', PARAM_INT);

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('totara/connect:manage', $context);

if ($instanceid == -1) {
    // Adding new client.
    $client = null;
} else {
    $client = $DB->get_record('totara_connect_clients', array('status' => util::CLIENT_STATUS_OK, 'id' => $instanceid), '*', MUST_EXIST);
}

$PAGE->set_context($context);
$PAGE->set_url('/totara/connect/dialog/cohort.php');

if ($selected) {
    list($selectedsql, $selectedparams) = $DB->get_in_or_equal(explode(',', $selected));
    $selected = $DB->get_records_select('cohort', "id {$selectedsql}", $selectedparams, 'name ASC, idnumber ASC', 'id, name AS fullname');
} else {
    $selected = array();
}

$items = $DB->get_records('cohort', null, 'name ASC, idnumber ASC');

// Don't let them remove the currently selected ones.
$unremovable = $selected;

// Setup dialog.
$dialog = new totara_dialog_content();
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->items = $items;

// Set disabled/selected items.
$dialog->selected_items = $selected;

// Set unremovable items.
$dialog->unremovable_items = $unremovable;

// Set title.
$dialog->selected_title = 'itemstoadd';

// Setup search.
$dialog->searchtype = 'cohort';
$dialog->customdata['instancetype'] = 'connect';
$dialog->customdata['instanceid'] = $instanceid;

// Display.
echo $dialog->generate_markup();
