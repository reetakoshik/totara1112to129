<?php
/*
 * This file is part of Totara LMS
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE;

require_login();

// Set page context.
$systemcontext = context_system::instance();
$title = get_string('catalog_title', 'totara_catalog');
$heading = get_string('catalog_heading', 'totara_catalog');
$PAGE->set_context($systemcontext);
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_pagelayout('noblocks');

// Start page output.
$pageurl = new moodle_url('/totara/catalog/index.php');
$PAGE->set_url($pageurl);
echo $OUTPUT->header();
echo $OUTPUT->heading($heading, 2, 'tw-catalog__title');

if ($CFG->catalogtype !== 'totara') {
    $redirect_url = $CFG->catalogtype === 'enhanced' ? '/totara/coursecatalog/courses.php' : '/course/index.php';
    $redirect_link = html_writer::link(
        new moodle_url($redirect_url),
        get_string('redirect_message_go_to_active_catalog_link', 'totara_catalog')
    );
    echo $OUTPUT->notification(
        get_string('redirect_message_catalog_not_configured', 'totara_catalog', ['go_to_active_catalog' => $redirect_link])
        , 'info'
    );
} else {
    echo $OUTPUT->render(\totara_catalog\local\param_processor::get_template());
}

echo $OUTPUT->footer();
