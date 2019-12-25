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
 * @package core
 */

// Disable Totara specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

// We need just the values from config.php and minlib.php.
define('ABORT_AFTER_CONFIG', true);
require('../../config.php'); // This stops immediately at the beginning of lib/setup.php.
require_once("$CFG->dirroot/lib/jslib.php");
require_once("$CFG->dirroot/lib/classes/requirejs.php");

$slashargument = min_get_slash_argument();
if (!$slashargument) {
    // The above call to min_get_slash_argument should always work.
    $rev = -1;
} else {
    // Get the revision, the file is ignored, for now it is always 'config.js'.
    $slashargument = ltrim($slashargument, '/');
    list($rev) = explode('/', $slashargument, 1);
    $rev = min_clean_param($rev, 'INT');
}

// Use the caching only for meaningful revision numbers to prevent future cache poisoning.
if ($rev > 0 and $rev < (time() + 60 * 60)) {
    $etag = sha1($rev);
    $candidate = $CFG->localcachedir . '/requirejsconfig/' . $rev;

    if (file_exists($candidate)) {
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) || !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            // We do not actually need to verify the etag value because content
            // of this file never change because we increment the rev parameter.
            js_send_unmodified(filemtime($candidate), $etag);
            exit(0);
        }
        js_send_cached($candidate, $etag, 'config.js');
        exit(0);
    }

    $content = \core_requirejs::get_config_file_content($rev);
    js_write_cache_file_content($candidate, $content);

    // Verify nothing failed in cache file creation.
    clearstatcache();
    if (file_exists($candidate)) {
        js_send_cached($candidate, $etag, 'config.js');
        exit(0);
    }
}

$content = \core_requirejs::get_config_file_content(-1);
js_send_uncached($content, 'config.js');
