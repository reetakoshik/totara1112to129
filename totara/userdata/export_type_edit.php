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
$duplicate = optional_param('duplicate', null, PARAM_INT);

admin_externalpage_setup('userdataexporttypes');
require_capability('totara/userdata:config', context_system::instance());

if ($id) {
    $exporttype = \totara_userdata\local\export_type::prepare_for_update($id);
} else {
    $exporttype = \totara_userdata\local\export_type::prepare_for_add($duplicate);
}

$form = new \totara_userdata\form\export_type_edit($exporttype);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/totara/userdata/export_types.php'));
}

if ($data = $form->get_data()) {
    \totara_userdata\local\export_type::edit($data);
    redirect(new moodle_url('/totara/userdata/export_types.php'));
}

$PAGE->requires->js_call_amd('totara_userdata/item_select', 'init', array('name' => 'itemselection_desc', 'form' => 'export'));

echo $OUTPUT->header();
if ($id) {
    echo $OUTPUT->heading(get_string('exporttypeupdate', 'totara_userdata'));
} else {
    echo $OUTPUT->heading(get_string('exporttypeadd', 'totara_userdata'));
}
echo $form->render();
echo $OUTPUT->footer();
