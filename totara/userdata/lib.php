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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

use totara_userdata\local\export;

/**
 * File serving code for user data plugin.
 *
 * @param stdClass $course course object
 * @param cm_info $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function totara_userdata_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if (\core\session\manager::is_loggedinas()) {
        // No login as here for privacy reasons!
        send_file_not_found();
    }

    if ($filearea !== 'export') {
        return false;
    }

    $exportid = (int)array_shift($args);
    $relativepath = implode('/', $args);

    if ($relativepath !== 'export.tgz') {
        return false;
    }

    $export = $DB->get_record('totara_userdata_export', array('id' => $exportid));
    if (!$export) {
        return false;
    }

    if (!export::is_export_file_available($export)) {
        return false;
    }
    $filerecord = export::get_result_file_record($export->id);
    if (!$filerecord) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file_instance($filerecord);

    // Log each download!
    $event = \totara_userdata\event\export_downloaded::create_from_download($export, $file);
    $event->trigger();

    if (defined('BEHAT_SITE_RUNNING') and BEHAT_SITE_RUNNING) {
        echo 'behat export file access success';
        die;
    }

    // Finally send the file.
    send_stored_file($file, null, 0, 1);
}
