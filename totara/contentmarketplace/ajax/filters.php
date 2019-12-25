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

$category = optional_param('category', 0, PARAM_INT);
$mode = optional_param('mode', \totara_contentmarketplace\explorer::MODE_EXPLORE, PARAM_ALPHANUMEXT);

if ($category == 0) {
    $context = context_system::instance();
} else {
    $context = context_coursecat::instance($category);
}

$PAGE->set_context($context);

echo $OUTPUT->header();
require_sesskey();
require_login();
if (empty($CFG->enablecontentmarketplaces)) {
    throw new \moodle_exception('error:disabledmarketplaces', 'totara_contentmarketplace');
}

$marketplacename = required_param('marketplace', PARAM_ALPHA);

$marketplace = contentmarketplace::plugin($marketplacename);
if (!$marketplace->is_enabled()) {
    echo json_encode(false);
    exit;
}

$search = $marketplace->search();
echo json_encode($search->get_filter_seeds($context, $mode));
