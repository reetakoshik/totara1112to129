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

// This is very purposefully here, please don't move it beneath admin_externalpage_setup.
// We want users on sites where it is not enabled, or where they've followed a link on a call to action to be directed
// to a page that engages them.
if (!\totara_contentmarketplace\local::is_enabled() || \totara_contentmarketplace\local::should_show_admin_setup_intro()) {
    redirect(new moodle_url('/totara/contentmarketplace/setup.php'));
    die;
}

$id = optional_param('id', null, PARAM_ALPHAEXT);
$enable = optional_param('enable', null, PARAM_BOOL);
$disable = optional_param('disable', null, PARAM_BOOL);

admin_externalpage_setup('manage_content_marketplaces');

// Set a better title and heading.
$title = get_string('subplugintype_contentmarketplace', 'totara_contentmarketplace');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->requires->js_call_amd('totara_contentmarketplace/disable', 'init');

/** @var totara_contentmarketplace\plugininfo\contentmarketplace[] $plugins */
$plugins = core_plugin_manager::instance()->get_plugins_of_type('contentmarketplace');

if (!empty($id)) {
    // Check if it's a valid plugin ID.
    $plugin = null;
    foreach ($plugins as $pl) {
        if ($pl->name == $id) {
            $plugin = $pl;
            break;
        }
    }
    unset($pl);
    if (!$plugin) {
        redirect($PAGE->url);
    }

    if (!empty($enable)) {
        require_sesskey();
        $plugin->enable();
        redirect($PAGE->url);
    }

    if (!empty($disable)) {
        require_sesskey();
        $plugin->disable();
        redirect($PAGE->url);
    }

    if (!$plugin->is_enabled()) {
        throw new moodle_exception('error:disabledmarketplace', 'totara_contentmarketplace', '', $plugin->displayname);
    }

    $PAGE->navbar->add($plugin->displayname);
    echo $OUTPUT->header();
    $settingspage = $CFG->dirroot . '/totara/contentmarketplace/contentmarketplaces/'.$plugin->name.'/config.php';
    if (!file_exists($settingspage)) {
        echo $OUTPUT->error_text(get_string('settings_page_not_found', 'totara_contentmarketplace'));
    } else {
        require_once($settingspage);
    }
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_content_marketplaces', 'totara_contentmarketplace'));

$table = new html_table();
$table->head  = array(
    get_string('contentmarketplace', 'totara_contentmarketplace'),
    '',
    get_string('description', 'totara_contentmarketplace'),
    get_string('availability', 'totara_contentmarketplace'),
    get_string('actions', 'totara_contentmarketplace'),
);
$table->attributes['class'] = 'contentmarketplaces generaltable';
$table->data  = array();

foreach ($plugins as $plugin) {

    $marketplace = $plugin->contentmarketplace();
    $isenabled = $plugin->is_enabled();

    $actionshtml = array();
    if ($plugin->has_never_been_enabled()) {
        $actionshtml[] = $marketplace->get_setup_html(get_string('setup', 'totara_contentmarketplace'));
    } else {
        if ($isenabled) {
            $actionshtml[] = html_writer::link(
                new moodle_url($PAGE->url, array('id' => $plugin->name)),
                $OUTPUT->pix_icon('t/edit', get_string('settings', 'totara_contentmarketplace'))
            );
            $actionshtml[] = html_writer::link(
                new moodle_url($PAGE->url, array('id' => $plugin->name, 'disable' => 1, 'sesskey' => sesskey())),
                $OUTPUT->pix_icon('t/hide', get_string('disable', 'totara_contentmarketplace')),
                [
                    'class' => 'tcm-disable',
                    'data-marketplace' => $marketplace->name,
                ]
            );
        } else {
            $actionshtml[] = html_writer::span(
                $OUTPUT->pix_icon('t/edit', get_string('settings', 'totara_contentmarketplace')),
                'dimmed_text'
            );
            $actionshtml[] = html_writer::link(
                new moodle_url($PAGE->url, array('id' => $plugin->name, 'enable' => 1, 'sesskey' => sesskey())),
                $OUTPUT->pix_icon('t/show', get_string('enable', 'totara_contentmarketplace'))
            );
        }
        $actionshtml[] = html_writer::empty_tag('br');
        $actionshtml[] = $marketplace->get_setup_html(get_string('setup', 'totara_contentmarketplace'));
    }

    $enabledlabel = $isenabled
        ? get_string('enabled', 'totara_contentmarketplace')
        : get_string('disabled', 'totara_contentmarketplace');

    $row = new html_table_row(array(
        $marketplace->get_logo_html(),
        s($marketplace->fullname),
        $marketplace->descriptionhtml,
        s($enabledlabel),
        implode('', $actionshtml),
    ));
    if (!$isenabled) {
        $row->attributes['class'] = 'dimmed_text';
    }
    $table->data[] = $row;
    $table->rowclasses[] = $plugin->component;
}

echo $OUTPUT->render($table);
echo $OUTPUT->footer();
