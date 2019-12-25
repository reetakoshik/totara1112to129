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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/cohort/lib.php');

$selected   = optional_param('selected', array(), PARAM_SEQUENCE);

require_login();
try {
    require_sesskey();
} catch (moodle_exception $e) {
    echo html_writer::tag('div', $e->getMessage(), array('class' => 'notifyproblem'));
    die();
}

// Check user capabilities.
$contextsystem = context_system::instance();

$capable = false;
if (has_capability('totara/dashboard:manage', $contextsystem)) {
    $capable = true;
}

$PAGE->set_context($contextsystem);
$PAGE->set_url('/totara/dashboard/dialog/cohort.php');

if (!$capable) {
    echo html_writer::tag('div', get_string('error:capabilitycohortview', 'totara_cohort'), array('class' => 'notifyproblem'));
    die();
}

if (!empty($selected)) {
    $selectedlist = explode(',', $selected);
    list($placeholders, $params) = $DB->get_in_or_equal($selectedlist);
    $records = $DB->get_records_select('cohort', "id {$placeholders}", $params, '', 'id, name as fullname');
} else {
    $records = array();
}

$items = $DB->get_records('cohort');

// Don't let them remove the currently selected ones.
$unremovable = $records;

// Setup dialog.
// Load dialog content generator; skip access, since it's checked above.
$dialog = new totara_dialog_content();
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->items = $items;
$dialog->selected_items = $records;
$dialog->unremovable_items = $unremovable;
$dialog->selected_title = 'itemstoadd';
$dialog->searchtype = 'cohort';

echo $dialog->generate_markup();
