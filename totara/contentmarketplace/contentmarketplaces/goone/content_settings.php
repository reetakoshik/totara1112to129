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
 * @package contentmarketplace_goone
 */

use totara_contentmarketplace\local;

defined('MOODLE_INTERNAL') || die();

/** @var totara_contentmarketplace\plugininfo\contentmarketplace $plugin */
$plugin = core_plugin_manager::instance()->get_plugin_info("contentmarketplace_goone");
if (!$plugin->is_enabled()) {
    throw new moodle_exception('error:disabledmarketplace', 'totara_contentmarketplace', '', $plugin->displayname);
}

$api = new \contentmarketplace_goone\api();
$all = $api->get_learning_objects_total_count();
$subscribed = $api->get_learning_objects_subscribed_count();
$collection = $api->get_learning_objects_collection_count();

$courses_subscribed = get_config('contentmarketplace_goone', 'learning_objects_subscribed');
$creators = get_config('contentmarketplace_goone', 'content_settings_creators');
$pay_per_seat = get_config('contentmarketplace_goone', 'pay_per_seat');
if ($pay_per_seat === false) {
    $pay_per_seat = null;
} else {
    $pay_per_seat = (int) $pay_per_seat;
}

$form = new \contentmarketplace_goone\form\content_settings_form([
    'creators' => !empty($creators) ? $creators : 'all',
    'pay_per_seat' => $pay_per_seat,
], [
    'courses_all' => (!empty($all)) ? local::format_integer($all) : get_string('notapplicable', 'contentmarketplace_goone'),
    'courses_subscribed' => (!empty($subscribed)) ? local::format_integer($subscribed) : get_string('notapplicable', 'contentmarketplace_goone'),
    'courses_collection' => (!empty($collection)) ? local::format_integer($collection) : get_string('notapplicable', 'contentmarketplace_goone'),
]);

$data = $form->get_data();
if ($data) {
    \contentmarketplace_goone\contentmarketplace::save_content_settings_data($data);
    \core\notification::success(get_string('settings_saved', 'contentmarketplace_goone'));
}

echo html_writer::tag('h3', get_string('content_settings', 'contentmarketplace_goone'));
echo html_writer::tag('p', get_string('content_settings_description', 'contentmarketplace_goone'));

echo $form->render();
