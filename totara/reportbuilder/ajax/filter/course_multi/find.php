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
 * @author Brendan Cox <brendan.cox@totaralms.com>
 * @package totara_reportbuilder
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_courses.class.php');

// Parent ID.
$parentid = optional_param('parentid', 'cat0', PARAM_ALPHANUM);

// Strip 'cat' from begining of parentid.
$parentid = (int) substr($parentid, 3);

$PAGE->set_context(context_system::instance());

// Permissions checks.
require_login();
require_sesskey();

// Load dialog content generator.
$dialog = new totara_dialog_content_courses($parentid);

$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;

// Set heading for selected items.
$dialog->selected_title = 'itemstoadd';
$dialog->select_title = '';

$dialog->load_courses();

// Display.
echo $dialog->generate_markup();
