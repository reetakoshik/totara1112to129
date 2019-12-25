<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package totara
 * @subpackage program
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot.'/totara/program/program.class.php');

require_login();
$PAGE->set_context(context_system::instance());

// Check permissions.
$programid = required_param('programid', PARAM_INT);
$program = new program($programid);
require_capability('totara/program:configureassignments', $program->get_context());
$program->check_enabled();

$items = $DB->get_records_select('user_info_field', '', null, '', 'id, name as fullname');

///
/// Display page
///

// Load dialog content generator
$dialog = new totara_dialog_content();
$dialog->search_code = '';
$dialog->items = $items;

// Set title
$dialog->selected_title = 'currentlyselected';

// Addition url parameters.
$dialog->urlparams = array('programid' => $programid);

// Display page
echo $dialog->generate_markup();
