<?php
/*
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
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   theme_roots
 */

defined('MOODLE_INTERNAL' || die());

use theme_roots\output\site_logo;

class theme_basis_site_logo_testcase extends advanced_testcase {
    public function test_theme_settings() {
        global $CFG, $SITE, $OUTPUT, $PAGE;
        $PAGE->theme->settings->logo = new moodle_url('/theme/image.php/_s/basis/totara_core/1/logo');
        $logoalt = 'Theme Logo';
        $this->resetAfterTest();
        $expected = array(
            'siteurl' => $CFG->wwwroot .'/',
            'shortname' => $SITE->shortname,
            'logourl' => $PAGE->theme->setting_file_url('logo', 'logo'),
            'logoalt' => $logoalt,
            'faviconurl' => $OUTPUT->favicon()
        );

        // Set theme level logo and confirm it overwrites defaults
        $PAGE->theme->settings->alttext = $logoalt;

        $logolib = new site_logo();
        $this->assertEquals($expected, $logolib->export_for_template($OUTPUT));

        $this->assertDebuggingCalled('The class theme_roots\output\site_logo has been deprecated since 12.0. Use totara\core\classes\output\masthead_logo.php instead.');
    }
}