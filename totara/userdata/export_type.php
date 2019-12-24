<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('userdataexporttypes');

$exporttype = $DB->get_record('totara_userdata_export_type', array('id' => $id), '*', MUST_EXIST);
$usercreated = $DB->get_record('user', array('id' => $exporttype->usercreated));

$PAGE->navbar->add(format_string($exporttype->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('exporttype', 'totara_userdata'));

$availablefor = array();
if ($exporttype->allowself) {
    $availablefor[] = get_string('exportoriginself', 'totara_userdata');
}

echo '<dl class="dl-horizontal">';
echo '<dt>' . get_string('fullname', 'totara_userdata') . '</dt>';
echo '<dd>' . format_string($exporttype->fullname) . '</dd>';
echo '<dt>' . get_string('idnumber') . '</dt>';
echo '<dd>' . (trim($exporttype->idnumber) === '' ? '&nbsp;' : s($exporttype->idnumber)) . '</dd>';
echo '<dt>' . get_string('description') . '</dt>';
echo '<dd>';
$description = format_text($exporttype->description, FORMAT_HTML);
if (trim($description) === '') {
    echo '&nbsp;';
} else {
    echo $description;
}
echo '</dd>';
echo '<dt>' . get_string('exporttypeavailablefor', 'totara_userdata') . '</dt>';
echo '<dd>';
if (!$availablefor) {
    echo '&nbsp;';
} else {
    echo implode(', ', $availablefor);
}
echo '</dd>';
echo '<dt>' . get_string('exportincludefiledir', 'totara_userdata') . '</dt>';
echo '<dd>';
echo ($exporttype->includefiledir ? get_string('yes') : get_string('no'));
echo '</dd>';
echo '<dt>' . get_string('createdby', 'totara_userdata') . '</dt>';
echo '<dd>';
echo ($usercreated ? fullname($usercreated) : '&nbsp;');
echo '</dd>';
echo '<dt>' . get_string('timecreated', 'totara_userdata') . '</dt>';
echo '<dd>';
echo userdate($exporttype->timecreated);
echo '</dd>';
echo '<dt>' . get_string('timechanged', 'totara_userdata') . '</dt>';
echo '<dd>';
echo userdate($exporttype->timechanged);
echo '</dd>';
echo '<dt>' . get_string('exportscount', 'totara_userdata') . '</dt>';
echo '<dd>';
$count =  $DB->count_records('totara_userdata_export', array('exporttypeid' => $exporttype->id));
if (!$count) {
    echo 0;
} else {
    echo html_writer::link(new moodle_url('/totara/userdata/exports.php', array('exporttypeid' => $exporttype->id)), $count);
}
echo '</dd>';
echo '</dl>';

echo $OUTPUT->heading(get_string('exportitemselection', 'totara_userdata'), 3);

echo markdown_to_html(get_string('exporttypewhenitemsapplied', 'totara_userdata'));

/** @var \totara_userdata_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_userdata');
echo $renderer->export_type_active_items($exporttype);

echo $OUTPUT->footer();
