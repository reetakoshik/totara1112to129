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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$action = optional_param('action', null, PARAM_ALPHA);

// If hardcoded off in config, don't even show this page.
if (!\totara_contentmarketplace\local::is_enabled() &&
    array_key_exists('enablecontentmarketplaces', $CFG->config_php_settings)) {

    throw new \moodle_exception('error:disabledmarketplaces', 'totara_contentmarketplace');
}

// At least one marketplace already configured, redirect to management instead.
if (\totara_contentmarketplace\local::is_enabled() &&
    !\totara_contentmarketplace\local::should_show_admin_setup_intro()) {
    redirect(new \moodle_url('/totara/contentmarketplace/marketplaces.php'));
}

admin_externalpage_setup('setup_content_marketplaces');

if ($action) {
    require_sesskey();
    $value = ($action == 'enable');
    set_config('enablecontentmarketplaces', $value);
    redirect(new \moodle_url('/totara/contentmarketplace/setup.php'));
}

// Set a more appropriate title.
$title = get_string('setup_tc', 'totara_contentmarketplace');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('setup_content_marketplaces', 'totara_contentmarketplace'));
echo $OUTPUT->render_from_template('totara_contentmarketplace/setup_description', []);

if (!\totara_contentmarketplace\local::is_enabled()) {
    $action = 'enable';
    $buttonstr = get_string('enablecontentmarketplaces', 'totara_contentmarketplace');
} else {
    $action = 'disable';
    $buttonstr = get_string('disablecontentmarketplaces', 'totara_contentmarketplace');
}
$url = new \moodle_url('/totara/contentmarketplace/setup.php', [
    'action' => $action,
]);
$togglemarketplacesbutton = new single_button($url, $buttonstr, 'post');
echo $OUTPUT->render($togglemarketplacesbutton);

$table = new html_table();
$head = [
    get_string('contentmarketplace', 'totara_contentmarketplace'),
    '',
    get_string('description', 'totara_contentmarketplace'),
];
if (\totara_contentmarketplace\local::is_enabled()) {
    $head[] = get_string('actions', 'totara_contentmarketplace');
}
$table->head  = $head;
$table->attributes['class'] = 'contentmarketplaces generaltable';
$table->data  = array();

/** @var totara_contentmarketplace\plugininfo\contentmarketplace[] $plugins */
$plugins = core_plugin_manager::instance()->get_plugins_of_type('contentmarketplace');
foreach ($plugins as $plugin) {

    $prov = $plugin->contentmarketplace();

    $data = [
        $prov->get_logo_html(60),
        $prov->fullname,
        $prov->descriptionhtml,
    ];
    if (\totara_contentmarketplace\local::is_enabled()) {
        $data[] = !$plugin->is_enabled() ? $prov->get_setup_html(get_string('enable', 'totara_contentmarketplace')) : '';
    }
    $table->data[] = $data;
    $table->rowclasses[] = $plugin->component;
}

echo $OUTPUT->render($table);
echo $OUTPUT->footer();
