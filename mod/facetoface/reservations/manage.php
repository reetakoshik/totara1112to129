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
 * @author  Maria Torres <maria.torres@totaralearning.com>
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$sid = required_param('s', PARAM_INT);

$seminarevent = new \mod_facetoface\seminar_event($sid);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/facetoface/reservations/manage.php', ['s' => $sid]);
$PAGE->set_url($url);

require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:managereservations', $context);

$reservations = \mod_facetoface\reservations::get($seminarevent);
$backurl = new moodle_url('/mod/facetoface/view.php', ['id' => $cm->id]);

$title = get_string('managereservations', 'mod_facetoface');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$output = $PAGE->get_renderer('mod_facetoface');
$output->setcontext($context);

echo $output->header();
echo $output->heading(format_string($seminar->get_name()));
/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, false);
echo $output->print_reservation_management_table($reservations);
echo $output->single_button($backurl, get_string('goback', 'mod_facetoface'), 'post');
echo $output->footer();
