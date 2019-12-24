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

admin_externalpage_setup('userdatapurgetypes');

$purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $id), '*', MUST_EXIST);
$usercreated = $DB->get_record('user', array('id' => $purgetype->usercreated));

$PAGE->navbar->add(format_string($purgetype->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('purgetype', 'totara_userdata'));

$availablefor = array();
if ($purgetype->allowmanual) {
    $availablefor[] = get_string('purgeoriginmanual', 'totara_userdata');
}
if ($purgetype->allowdeleted) {
    $availablefor[] = get_string('purgeorigindeleted', 'totara_userdata');
}
if ($purgetype->allowsuspended) {
    $availablefor[] = get_string('purgeoriginsuspended', 'totara_userdata');
}
$statuses = \totara_userdata\userdata\target_user::get_user_statuses();

echo '<dl class="dl-horizontal">';
echo '<dt>' . get_string('fullname', 'totara_userdata') . '</dt>';
echo '<dd>' . format_string($purgetype->fullname) . '</dd>';
echo '<dt>' . get_string('idnumber') . '</dt>';
echo '<dd>' . (trim($purgetype->idnumber) === '' ? '&nbsp;' : s($purgetype->idnumber)) . '</dd>';
echo '<dt>' . get_string('purgetypeuserstatus', 'totara_userdata') . '</dt>';
echo '<dd>';
    echo $statuses[$purgetype->userstatus];
echo '</dd>';
echo '<dt>' . get_string('description') . '</dt>';
echo '<dd>';
$description = format_text($purgetype->description, FORMAT_HTML);
if (trim($description) === '') {
    echo '&nbsp;';
} else {
    echo $description;
}
echo '</dd>';
echo '<dt>' . get_string('purgetypeavailablefor', 'totara_userdata') . '</dt>';
echo '<dd>';
if (!$availablefor) {
    echo '&nbsp;';
} else {
    echo implode(', ', $availablefor);
}
echo '</dd>';
echo '<dt>' . get_string('createdby', 'totara_userdata') . '</dt>';
echo '<dd>';
echo ($usercreated ? fullname($usercreated) : '&nbsp;');
echo '</dd>';
echo '<dt>' . get_string('timecreated', 'totara_userdata') . '</dt>';
echo '<dd>';
echo userdate($purgetype->timecreated);
echo '</dd>';
echo '<dt>' . get_string('timechanged', 'totara_userdata') . '</dt>';
echo '<dd>';
echo userdate($purgetype->timechanged);
echo '</dd>';
echo '<dt>' . get_string('purgescount', 'totara_userdata') . '</dt>';
echo '<dd>';
$count =  $DB->count_records('totara_userdata_purge', array('purgetypeid' => $purgetype->id));
if (!$count) {
    echo 0;
} else {
    echo html_writer::link(new moodle_url('/totara/userdata/purges.php', array('purgetypeid' => $purgetype->id)), $count);
}
echo '</dd>';
echo '</dl>';

echo $OUTPUT->heading(get_string('purgeitemselection', 'totara_userdata'), 3);

echo markdown_to_html(get_string('purgetypewhenitemsapplied', 'totara_userdata'));

/** @var \totara_userdata_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_userdata');
echo $renderer->purge_type_active_items($purgetype);

echo $OUTPUT->footer();
