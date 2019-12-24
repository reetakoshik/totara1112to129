<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_certifications.class.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

$parentid = optional_param('parentid', 'cat0', PARAM_ALPHANUM);
preg_match('/([0-9]+)$/', $parentid, $matches);
$parentid = $matches[1];

require_login();

$PAGE->set_context(context_system::instance());

// Load dialog content generator.
$dialog = new totara_dialog_content_certifications($parentid);
$dialog->searchtype = 'certification';
$dialog->load_certifications();
$dialog->customdata['instanceid'] = $parentid;
// Display page.
echo $dialog->generate_markup();