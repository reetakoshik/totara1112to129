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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$marketplace = required_param('marketplace', PARAM_ALPHA);
$mode = optional_param('mode', \totara_contentmarketplace\explorer::MODE_EXPLORE, PARAM_ALPHAEXT);
$category = optional_param('category', 0, PARAM_INT);

if ($category == 0) {
    $context = context_system::instance();
} else {
    $context = context_coursecat::instance($category);
}

$PAGE->set_context($context);
$PAGE->set_url('/totara/contentmarketplace/explorer.php', ['marketplace' => $marketplace]);

require_login();
require_capability('totara/contentmarketplace:add', $context);
\totara_contentmarketplace\local::require_contentmarketplace();

$explorer = new \totara_contentmarketplace\explorer($marketplace, $mode, $category);

/** @var totara_contentmarketplace\plugininfo\contentmarketplace $plugin */
$plugin = core_plugin_manager::instance()->get_plugin_info("contentmarketplace_{$marketplace}");
if (!$plugin->is_enabled()) {
    throw new moodle_exception('error:disabledmarketplace', 'totara_contentmarketplace', '', $plugin->displayname);
}

$PAGE->set_pagelayout('noblocks');

if ($mode === \totara_contentmarketplace\explorer::MODE_CREATE_COURSE) {
    $PAGE->navbar->add(get_string('administrationsite'));
    $PAGE->navbar->add(get_string('courses'));
    $PAGE->navbar->add(get_string('createcourse', 'totara_contentmarketplace'));
    $PAGE->navbar->add($plugin->displayname);
} else {
    $PAGE->navbar->add(get_string('contentmarketplace', 'totara_contentmarketplace'));
    $PAGE->navbar->add($plugin->displayname);
    $PAGE->navbar->add(get_string('explore', 'totara_contentmarketplace'));
}

if (has_capability('totara/contentmarketplace:config', $context)) {
    $url = $plugin->contentmarketplace()->settings_url("content_settings");
    $searchform = $OUTPUT->single_button($url, get_string("manage_available_content", "totara_contentmarketplace"), "get");
    $PAGE->set_button($searchform);
}

$PAGE->set_title($explorer->get_heading());
$PAGE->set_heading($explorer->get_heading());

echo $OUTPUT->header();
echo $explorer->render();
echo $OUTPUT->footer();
