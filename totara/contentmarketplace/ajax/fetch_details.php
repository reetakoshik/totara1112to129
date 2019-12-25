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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */

use totara_contentmarketplace\plugininfo\contentmarketplace;

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

$id = required_param('id', PARAM_INT);
$marketplace = required_param('marketplace', PARAM_ALPHA);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/contentmarketplace/ajax/fetch_details.php', ['id' => $id, 'marketplace' => $marketplace]));

// Order of checking is important as getting it wrong can give away information.
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
$search = $mp->search();
$lo = $search->get_details($id);

if (!$lo) {
    $data->success = false;
    echo $OUTPUT->header();
    echo json_encode($data);
    exit;
}

$data = new stdClass();
$data->success = true;
$data->title = $lo->title;
$data->description = clean_text($lo->description);
$data->delivery = $lo->delivery;
$data->delivery_has_items = !empty($lo->delivery);
$data->items = $lo->items;
$data->has_items = !empty($lo->items);
$data->image = $lo->image;
$data->has_image = !empty($lo->image);
$data->reviews = $lo->reviews;
$data->provider = $lo->provider;
$data->stars = '';
$data->price = call_user_func([$search, 'price'], $lo);
$data->delivery->duration = call_user_func([$search, 'duration'], $lo);

if (!is_null($lo->reviews->rating)) {
    $lo->has_reviews = true;
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $lo->reviews->rating) {
            $data->stars .= $OUTPUT->flex_icon('star');
        } else {
            $data->stars .= $OUTPUT->flex_icon('star-off');
        }
    }

    $lo->strratings = get_string('ratingsx', 'totara_contentmarketplace', count($lo->items));
} else {
    $lo->has_reviews = false;
}

if ($data->delivery_has_items) {
    if ($data->delivery->duration > 0) {
        $data->delivery->duration_label = $data->delivery->duration . " \u{2022} ";
    } else {
        $data->delivery->duration_label = '';
    }
}

echo $OUTPUT->header();
echo json_encode($data);
