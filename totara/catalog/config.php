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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\form\base_config_form_controller;
use totara_catalog\local\config_form_helper;

global $CFG, $PAGE;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

if ($CFG->catalogtype !== 'totara') {
    print_error('totara_catalog_disabled', 'totara_catalog');
}

admin_externalpage_setup('configurecatalog');
$systemcontext = context_system::instance();
require_capability('totara/catalog:configurecatalog', $systemcontext);

$title = get_string('configurecatalog', 'totara_catalog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$tab = optional_param('tab', 'contents', PARAM_ALPHA);
$possible_tabs = config_form_helper::create()->get_form_keys();
$tab = in_array($tab, $possible_tabs) ? $tab : 'contents';

$PAGE->requires->js_call_amd('totara_catalog/config_form', 'init', [$tab]);

$form_controller = base_config_form_controller::create_from_key($tab);
$form = $form_controller->get_form_instance();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/totara/catalog/config.php', ['tab' => $tab]));
}

/** @var totara_catalog_renderer $output */
$output = $PAGE->get_renderer('totara_catalog');
echo $output->header();
echo $output->heading($title);

echo $output->config_tabs($tab);

echo '<div class="totara_catalog_admin_config_form">';

if (!$form->is_reloaded()) {
    if ($data = $form_controller->get_submission_data()) {
        $process_result = $form_controller->process_data();

        if (isset($process_result['success_msg'])) {
            echo $output->notification($process_result['success_msg'], 'notifysuccess');
        }
        if (isset($process_result['warning_msg'])) {
            echo $output->notification($process_result['warning_msg'], 'warning');
        }
    }
}
echo $form->render();

echo '</div>';
echo $output->footer();
