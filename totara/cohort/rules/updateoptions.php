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
 * @author Rob tyler <rob.tyler@totaralms.com>
 * @package totara
 * @subpackage cohort/rules
 */


// This file is an ajax back-end for updating membership options.

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/cohort/lib.php');

$id = required_param('id', PARAM_INT);
$addnewmembers = optional_param('addnewmembers', null, PARAM_INT);
$removeoldmembers = optional_param('removeoldmembers', null, PARAM_INT);

require_login();
require_sesskey();

$contextid = $DB->get_field('cohort','contextid',  array('id' => $id), MUST_EXIST);
$context = context::instance_by_id($contextid, MUST_EXIST);

require_capability('totara/cohort:managerules', $context);

$result = totara_cohort_update_membership_options($id, $addnewmembers, $removeoldmembers);

if (isset($addnewmembers)) {
    echo json_encode(array('action' => 'addnewmembers', 'value' => $addnewmembers, 'result' => $result));
} else if (isset($removeoldmembers)) {
    echo json_encode(array('action' => 'removeoldmembers', 'value' => $removeoldmembers, 'result' => $result));
}