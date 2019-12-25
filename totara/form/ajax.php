<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_form
 */

use totara_form\form_controller;

define('AJAX_SCRIPT', true);

require('../../config.php');

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/form/ajax.php');
$PAGE->set_cacheable(false);

require_sesskey();

$class = required_param('___tf_formclass', PARAM_RAW);
$idsuffix = optional_param('___tf_idsuffix', '', PARAM_ALPHANUMEXT);

$formregex = '/^[a-z0-9_]+\\\\form\\\\[a-z0-9_]+$/';
$formtestregex = '/^[a-z0-9_]+\\\\form\\\\testform\\\\[a-z0-9_]+$/';
$isbehatsite = defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING;
if (!preg_match($formregex, $class)) {
    if (!debugging() && !$isbehatsite && !preg_match($formtestregex, $class)) {
        throw new invalid_parameter_exception('Invalid form class ' . $class);
    }
}

if (!class_exists($class)) {
    throw new invalid_parameter_exception('Cannot find form class');
}

$callable = array($class, 'get_form_controller');
if (!is_callable($callable)) {
    throw new invalid_parameter_exception('Invalid form class');
}

/** @var form_controller $controller */
$controller = call_user_func($callable);

if (!$controller or !($controller instanceof form_controller)) {
    throw new invalid_parameter_exception('Invalid or missing form_controller');
}

$form = $controller->get_ajax_form_instance($idsuffix);
if (!$form or !($form instanceof \totara_form\form)) {
    throw new invalid_parameter_exception('Invalid form object returned from form_controller');
}

// Send ajax headers and init $OUTPUT.
echo $OUTPUT->header();

if ($form->is_cancelled()) {
    $result = array(
        'formstatus' => 'cancelled',
    );

    echo json_encode($result);
    die;
}

if ($form->is_reloaded()) {
    $result = array(
        'formstatus' => 'display',
        'templatename' => $form->get_template(),
        'templatedata' => $form->export_for_template($OUTPUT),
    );

    echo json_encode($result);
    die;
}

if ($data = $form->get_data()) {
    $result = array(
        'formstatus' => 'submitted',
        'data' => $controller->process_ajax_data(),
    );

    echo json_encode($result);
    die;
}

$result = array(
    'formstatus' => 'display',
    'templatename' => $form->get_template(),
    'templatedata' => $form->export_for_template($OUTPUT),
);

echo json_encode($result);
die;
