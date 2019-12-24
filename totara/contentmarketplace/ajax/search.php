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
use totara_contentmarketplace\local;

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

$marketplace = required_param('marketplace', PARAM_ALPHA);
$sort = optional_param('sort', '', PARAM_STRINGID);
$query = optional_param('query', '', PARAM_RAW_TRIMMED);
$page = optional_param('page', 0, PARAM_INT);
$isfirstquerywithdefaultsort = optional_param('isfirstquerywithdefaultsort', false, PARAM_BOOL);
$mode = optional_param('mode', \totara_contentmarketplace\explorer::MODE_EXPLORE, PARAM_ALPHANUMEXT);
$category = optional_param('category', 0, PARAM_INT);

if ($category == 0) {
    $context = context_system::instance();
} else {
    $context = context_coursecat::instance($category);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/contentmarketplace/ajax/search.php', ['marketplace' => $marketplace]));

require_login(null, false, null, false, true);
require_capability('totara/contentmarketplace:add', $context);
require_sesskey();

local::require_contentmarketplace();
$mp = contentmarketplace::plugin($marketplace, false);
if ($mp === null || !$mp->is_enabled()) {
    echo $OUTPUT->header();
    echo json_encode(false);
    exit;
}

$filters = \totara_contentmarketplace\ajax_helper::extract_explorer_filters();

$search = $mp->search();
$results = $search->query($query, $sort, $filters, $page, $isfirstquerywithdefaultsort, $mode, $context);

$data = new stdClass();
$data->success = true;
$data->hits = $results->hits;
$data->filters = $results->filters;
$data->total = $results->total;

switch ($results->total) {
    case 0:
        $data->resultsummary = get_string('search:no-results', 'totara_contentmarketplace');
        break;
    case 1:
        $data->resultsummary = get_string('search:one-result', 'totara_contentmarketplace');
        break;
    default:
        $data->resultsummary = get_string('search:many-results', 'totara_contentmarketplace', local::format_integer($results->total));
        break;
}

$data->more = $results->more;
$data->sort = $results->sort;
$data->selectionmode = $results->selectionmode;
$data->template = "contentmarketplace_{$marketplace}/thumbnail";

echo $OUTPUT->header();
echo json_encode($data);
