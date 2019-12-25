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
use block_totara_featured_links\tile\meta_tile;

require_once('../../config.php');
require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

$tileid = required_param('tileid', PARAM_INT);
$returnurl = required_param('return_url', PARAM_URL);

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new \moodle_url('/blocks/totara_featured_links/sub_tile_manage.php',
    ['tileid' => $tileid, 'return_url' => $returnurl]));

$parenttile = base::get_tile_instance($tileid);
if (!$parenttile instanceof meta_tile) {
    throw new \coding_exception('The tile passed cannot contain subtiles. It must implement meta_tile to do this.');
}
if (!$parenttile->can_edit_tile()) {
    throw new \moodle_exception(get_string('cannot_edit_tile', 'block_totara_featured_links'));
}
$parenttile->get_subtiles();

$PAGE->requires->js_call_amd('block_totara_featured_links/dragndrop', 'init');
$PAGE->requires->strings_for_js(['delete', 'cancel'], 'core');
$PAGE->requires->strings_for_js(['confirm'], 'block_totara_featured_links');
$PAGE->requires->js_call_amd('block_totara_featured_links/ajax', 'block_totara_featured_links_remove_tile');

$data = [
    'tile_data' => [],
    'editing' => true,
    'size' => 'small',
    'shape' => 'square',
    'title' => 'editing title',
    'manual_id' => 'editing manual id',
    'instanceid' => $parenttile->blockid,
    'parentid' => $parenttile->id
];

$core_renderer = $PAGE->get_renderer('core');

$tiles = $parenttile->get_subtiles();
usort($tiles, function($tile1, $tile2) {
    return $tile1->sortorder - $tile2->sortorder;
});
$tile_data = [];

foreach ($tiles as $tile) {
    if ($tile->is_visible() || $tile->can_edit_tile()) {
        $tile_data[$tile->sortorder]['content'] = $tile->render_content_wrapper($core_renderer, $data);
    }
}

$tile_data[] = base::export_for_template_add_tile($parenttile->blockid, $parenttile->id);

for ($i = 0; $i < 10; $i++) {
    $tile_data[] = ['filler' => true];
}

$data['tile_data'] = array_values($tile_data);

$editstring = get_string('stopediting', 'block_totara_featured_links');

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('managesubtiles', 'block_totara_featured_links'));
echo $core_renderer->render_from_template('block_totara_featured_links/main', $data);
echo html_writer::start_div('block-totara-featured-links-finish-button');
echo html_writer::link($CFG->wwwroot . $returnurl, $editstring, ['class' => 'btn btn-primary']);
echo html_writer::end_div();
echo $OUTPUT->footer();