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

use block_totara_featured_links\tile\base;
use block_totara_featured_links\tile\default_tile;

require_once('../../config.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

local_js([
    TOTARA_JS_TREEVIEW,
    TOTARA_JS_UI,
    TOTARA_JS_DIALOG
]);

$blockinstanceid = required_param('blockinstanceid', PARAM_INT);
$tileid = optional_param('tileid', null, PARAM_INT);
$return_url = optional_param('return_url', null, PARAM_LOCALURL);
$type = optional_param('type', null, PARAM_ALPHANUMEXT);
$parentid  = optional_param('parentid', null, PARAM_INT);

if (!empty($type)) {
    list($plugin_name, $class_name) = explode('-', $type, 2);
    $type = "\\$plugin_name\\tile\\$class_name";
    // Make sure the type passes is a tile type.
    if (!class_exists($type) || !is_subclass_of($type, '\block_totara_featured_links\tile\base')) {
        throw new \coding_exception('Invaide tile type');
    }
}
$PAGE->set_url(
    new \moodle_url(
        '/blocks/totara_featured_links/edit_tile_content.php',
        ['blockinstanceid' => $blockinstanceid, 'tileid' => $tileid, 'return_url' => $return_url]
    )
);

$context = \context_block::instance($blockinstanceid, MUST_EXIST);
$PAGE->set_context($context);

$PAGE->requires->js_call_amd('block_totara_featured_links/content_form', 'init');

if (!empty($tileid)) {
    $tile_instance = !empty($type) ? new $type($tileid) : base::get_tile_instance($tileid);
    // Check blocks match up.
    if ($tile_instance->blockid != $blockinstanceid) {
        throw new \coding_exception('The tile and the block did not match up');
    }
} else {
    $tile_instance = !empty($type) ? new $type() : new default_tile();
    $tile_instance->blockid = $blockinstanceid;
    $tile_instance->parentid = $parentid;
}
// Checks the user has the correct permissions.
if (!$tile_instance->can_edit_tile()) {
    print_error('cannot_edit_tile', 'block_totara_featured_links');
}


$edit_form = $tile_instance->get_content_form(['blockinstanceid' => $blockinstanceid, 'tileid' => $tileid, 'return_url' => $return_url,
    'parentid' => $parentid]);

if ($form_data = $edit_form->get_data()) {
    if (!empty($form_data->parentid)) {
        $parenttile = base::get_tile_instance($form_data->parentid);
        if (!$parenttile->can_edit_tile()) {
            throw new \moodle_exception(get_string('cannot_edit_tile', 'block_totara_featured_links'));
        }
    }
    if (empty($tileid)) {
        $tile_instance = $type::add($blockinstanceid, $parentid);
    }
    // Makes a new form from the saved data so that the form object is of the right type for the tile.
    $tile_instance->save_content($form_data);

    redirect(new \moodle_url($edit_form->get_next_url($tile_instance)));
} else if ($edit_form->is_cancelled()) {
    redirect(new \moodle_url($return_url));
}
$edit_form->requirements();

$PAGE->set_heading(get_string('content_form_title', 'block_totara_featured_links', $tile_instance->get_accessibility_text()['sr-only']));
$PAGE->set_title(get_string('content_form_title', 'block_totara_featured_links', $tile_instance->get_accessibility_text()['sr-only']));
echo $OUTPUT->header();
echo $edit_form->render();
echo $OUTPUT->footer();
