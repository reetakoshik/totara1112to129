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

$debug = optional_param('debug', 0, PARAM_INT);

admin_externalpage_setup('userdataexporttypes');

$report = reportbuilder::create_embedded('userdata_export_types');

$buttons = array();
if (has_capability('totara/userdata:config', context_system::instance())) {
    $addurl = new moodle_url('/totara/userdata/export_type_edit.php', array('id' => 0));
    $buttons[] = $OUTPUT->single_button($addurl, get_string('exporttypeadd', 'totara_userdata'), 'post');
}
if ($button = $report->edit_button()) {
    $buttons[] = $button;
}
if ($buttons) {
    $PAGE->set_button(implode(' ', $buttons) . $PAGE->button);
}

/** @var totara_reportbuilder_renderer|core_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');
$report->include_js();

echo $output->header();
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;
echo $output->heading(get_string('sourcetitle', 'rb_source_userdata_export_types'));
echo $reporthtml;
echo $output->footer();
