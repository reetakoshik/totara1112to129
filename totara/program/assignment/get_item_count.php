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
 * @package totara
 * @subpackage program
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/program/lib.php');

require_login();

$cat = required_param('cat', PARAM_ALPHA); // The category name, such as positions, organisations
$itemid = required_param('itemid', PARAM_INT);
$includechildren = required_param('include', PARAM_INT);
$programid = required_param('programid', PARAM_INT);

$program = new program($programid);
// Information such as user's fullnames, manager positions can be exposed via this script,
// so check that the user has permission to do this.
require_capability('totara/program:configureassignments', $program->get_context());
$program->check_enabled();

$classname = "{$cat}_category";

if (!class_exists($classname) || !is_subclass_of($classname, 'prog_assignment_category')) {
    throw new moodle_exception('error', '', '', null, 'invalid classname');
}

/** @var prog_assignment_category $category */
$category = new $classname();

$item = $category->get_item(intval($itemid));

$item->includechildren = $includechildren;

$users = $category->user_affected_count($item, true);

$data = array(
    'name'  => $item->fullname,
    'count' => $users,
);
echo json_encode($data);
