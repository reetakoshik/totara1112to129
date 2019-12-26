<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use \mod_facetoface\asset;

$facetofaceid = required_param('facetofaceid', PARAM_INT);
$itemseq = required_param('itemids', PARAM_SEQUENCE);
$itemids = explode(',', $itemseq);
if (empty($itemids) || empty($itemids[0])) {
    exit();
}

if (!$facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/asset/ajax/asset_item.php', array(
    'itemids' => $itemseq
));

$assets = array();
foreach($itemids as $itemid) {
    $asset = new asset($itemid);
    $res = (object)[
        'id' => $asset->get_id(),
        'name' => $asset->get_name(),
        'hidden' => $asset->get_hidden(),
        'custom' => $asset->get_custom()
    ];

    $assets[] = $res;
}

// Render assets list.
echo json_encode(array_values($assets));
