<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Simple slider block for Moodle
 *
 * @package   block_slider
 * @copyright 2015 Kamil Łuczak    www.limsko.pl     kamil@limsko.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function block_image_link_pluginfile(
	$course, 
	$birecord_or_cm, 
	$context, 
	$filearea, 
	$args, 
	$forcedownload,
	array $options = []
) {
    global $DB, $CFG;

    if ( $context->contextlevel != CONTEXT_BLOCK || 
         $filearea !== 'content' ) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    $file = $fs->get_file(
        $context->id, 
        'block_image_link', 
        'content', 
        0, 
        $filepath, 
        $filename
    ); 

    if (!$file or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);
}
