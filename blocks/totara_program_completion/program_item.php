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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package block
 * @subpackage totara_program_completion
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot .'/blocks/totara_program_completion/locallib.php');

require_login();
try {
    require_sesskey();
} catch (moodle_exception $e) {
    $error = array('error' => $e->getMessage());
    die(json_encode($error));
}

$blockid   = optional_param('blockid', 0, PARAM_INT);
$itemids = required_param('itemid', PARAM_SEQUENCE);
$itemids = explode(',', $itemids);

// Check user capabilities.
if ((int)$blockid > 0) {
    $context = context_block::instance((int)$blockid);
} else {
    $context = context_system::instance();
}

if (!has_any_capability(array(
        'block/totara_program_completion:addinstance',
        'block/totara_program_completion:myaddinstance'
    ), $context)) {
    print_error('error:capabilityprogramsview', 'block_totara_program_completion');
}

$PAGE->set_context($context);
$PAGE->set_url('/blocks/totara_program_completion/program_item.php');

$progcompletions = new block_totara_program_completion_programs();
$progcompletions->set_items($itemids);

foreach ($progcompletions as $itemhtml) {
    $rows[] = $itemhtml;
}

echo json_encode(array(
    'items' => $rows
));
