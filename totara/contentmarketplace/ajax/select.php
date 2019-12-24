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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

use totara_contentmarketplace\plugininfo\contentmarketplace;

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

$marketplace = required_param('marketplace', PARAM_ALPHA);
$query = optional_param('query', '', PARAM_RAW_TRIMMED);
$mode = optional_param('mode', \totara_contentmarketplace\explorer::MODE_EXPLORE, PARAM_ALPHANUMEXT);
$category = optional_param('category', 0, PARAM_INT);

if ($category == 0) {
    $context = context_system::instance();
} else {
    $context = context_coursecat::instance($category);
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/contentmarketplace/ajax/select.php', ['marketplace' => $marketplace]));

require_login(null, false, null, false, true);
require_capability('totara/contentmarketplace:add', $context);
require_sesskey();
\totara_contentmarketplace\local::require_contentmarketplace();

$mp = contentmarketplace::plugin($marketplace, false);
if ($mp === null || !$mp->is_enabled()) {
    echo $OUTPUT->header();
    echo json_encode(false);
    exit;
}

$filters = \totara_contentmarketplace\ajax_helper::extract_explorer_filters();

$search = $mp->search();
$selection = $search->select_all($query, $filters, $mode, $context);

$data = new stdClass();
$data->success = true;
$data->selection = $selection;

echo $OUTPUT->header();
echo json_encode($data);
