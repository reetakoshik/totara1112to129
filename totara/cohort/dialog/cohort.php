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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage cohort
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/cohort/lib.php');

$selected   = optional_param('selected', array(), PARAM_SEQUENCE);
$instancetype = required_param('instancetype', PARAM_INT);
$instanceid   = required_param('instanceid', PARAM_INT);

require_login();
try {
    require_sesskey();
} catch (moodle_exception $e) {
    echo html_writer::tag('div', $e->getMessage(), array('class' => 'notifyproblem'));
    die();
}

// This dialog is used from many places, the permissions checks need to be a bit relaxed.
$capable = false;
if ($instancetype === COHORT_ASSN_ITEMTYPE_COURSE) {
    $context = context_course::instance($instanceid);
    if (enrol_is_enabled('cohort') and has_capability('moodle/course:enrolconfig', $context) and has_capability('enrol/cohort:config', $context)) {
        $capable = true;
    }
    if (!empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
        $capable = true;
    }

} else if ($instancetype === COHORT_ASSN_ITEMTYPE_CATEGORY) {
    $context = context_coursecat::instance($instanceid);
    require_capability('totara/coursecatalog:manageaudiencevisibility', $context);

} else if ($instancetype === COHORT_ASSN_ITEMTYPE_PROGRAM || $instancetype === COHORT_ASSN_ITEMTYPE_CERTIF) {
    $context = context_program::instance($instanceid);
    require_capability('totara/coursecatalog:manageaudiencevisibility', $context);

} else {
    $context = context_system::instance();
    require_capability('totara/coursecatalog:manageaudiencevisibility', $context);
}

if (has_capability('moodle/cohort:view', $context) || has_capability('moodle/cohort:manage', $context)) {
    $capable = true;
}

$PAGE->set_context($context);
$PAGE->set_url('/totara/cohort/dialog/cohort.php');

if (!$capable) {
    echo html_writer::tag('div', get_string('error:capabilitycohortview', 'totara_cohort'), array('class' => 'notifyproblem'));
    die();
}

$selectedsql = '';
$selectedparams = array();
if (!empty($selected)) {
    list($selectedsql, $selectedparams) = $DB->get_in_or_equal(explode(',', $selected));
    $selected = $DB->get_records_select('cohort', "id {$selectedsql}", $selectedparams, 'name, idnumber', 'id, name as fullname');
}

$contextids = array_filter($context->get_parent_context_ids(true),
    function($a) {return has_capability("moodle/cohort:view", context::instance_by_id($a));});
list($contextssql, $params) = $DB->get_in_or_equal($contextids);

$sql = "SELECT *
          FROM {cohort}
         WHERE (contextid {$contextssql})";

if ($selected) {
// Add all current cohorts even if user would not be able to select them again - changed permissions or moved cohort.
    $sql .= " OR (id {$selectedsql})";
    $params = array_merge($params, $selectedparams);
}
$sql .= " ORDER BY name ASC, idnumber ASC";

$items = $DB->get_records_sql($sql, $params, 0, TOTARA_DIALOG_MAXITEMS + 1);

// Don't let them remove the currently selected ones
$unremovable = $selected;

// Setup dialog
// Load dialog content generator; skip access, since it's checked above
$dialog = new totara_dialog_content();
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->items = $items;

// Set disabled/selected items
$dialog->selected_items = $selected;

// Set unremovable items
$dialog->unremovable_items = $unremovable;

// Set title
$dialog->selected_title = 'itemstoadd';

// Setup search
$dialog->searchtype = 'cohort';
$dialog->customdata['instancetype'] = $instancetype;
$dialog->customdata['instanceid'] = $instanceid;

// Additional url parameters needed for pagination in the search tab.
$dialog->urlparams = array('instancetype' => $instancetype, 'instanceid' => $instanceid);

// Display
echo $dialog->generate_markup();
