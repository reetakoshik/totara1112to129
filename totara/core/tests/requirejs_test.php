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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests of our upstream hacks and behaviour expected in Totara.
 */
class totara_core_requirejs_testcase extends advanced_testcase {
    public function test_get_config_data() {
        global $CFG;

        // NOTE: the core_requirejs class is supposed to use functions from minlib.php only!

        $this->assertSame('1', $CFG->slasharguments);
        $this->assertFileExists("{$CFG->dirroot}/lib/amd/src/first.js"); // Must exist for BC with Moodle.
        $this->assertFileNotExists("{$CFG->dirroot}/lib/amd/src/bundle.js"); // Must not exist because it would collide with bundle name.

        $config = \core_requirejs::get_config_data(-1);
        $this->assertSame('https://www.example.com/moodle/lib/requirejs.php/-1/', $config['baseUrl']);
        $this->assertRegExp('|^https://www\.example\.com/moodle/lib/javascript\.php/-1/lib/jquery/jquery-.*\.min$|', $config['paths']['jquery']);
        $this->assertRegExp('|^https://www\.example\.com/moodle/lib/javascript\.php/-1/lib/jquery/ui-.*/jquery-ui\.min$|', $config['paths']['jqueryui']);
        $this->assertSame('https://www.example.com/moodle/lib/javascript.php/-1/lib/requirejs/jquery-private', $config['paths']['jqueryprivate']);
        $this->assertSame('jqueryprivate', $config['map']['*']['jquery']);
        $this->assertSame('jquery', $config['map']['jqueryprivate']['jquery']);
        $this->assertIsArray($config['bundles']['core/bundle']);
        foreach($config['bundles']['core/bundle'] as $amd) {
            $this->assertRegExp('/^[a-z0-9_]+\/[a-z0-9_-]+$/', $amd, 'Invalid AMD module name: ' . $amd);
            $this->assertNotContains('-lazy', $amd);
        }

        $config = \core_requirejs::get_config_data(55);
        $this->assertSame('https://www.example.com/moodle/lib/requirejs.php/55/', $config['baseUrl']);
        $this->assertRegExp('|^https://www\.example\.com/moodle/lib/javascript\.php/55/lib/jquery/jquery-.*\.min$|', $config['paths']['jquery']);
        $this->assertRegExp('|^https://www\.example\.com/moodle/lib/javascript\.php/55/lib/jquery/ui-.*/jquery-ui\.min$|', $config['paths']['jqueryui']);
        $this->assertSame('https://www.example.com/moodle/lib/javascript.php/55/lib/requirejs/jquery-private', $config['paths']['jqueryprivate']);
        $this->assertSame('jqueryprivate', $config['map']['*']['jquery']);
        $this->assertSame('jquery', $config['map']['jqueryprivate']['jquery']);
        $this->assertIsArray($config['bundles']['core/bundle']);
        foreach($config['bundles']['core/bundle'] as $amd) {
            $this->assertRegExp('/^[a-z0-9_]+\/[a-z0-9_-]+$/', $amd, 'Invalid AMD module name: ' . $amd);
            $this->assertNotContains('-lazy', $amd);
        }
    }

    public function test_get_config_file_content() {
        $content = core_requirejs::get_config_file_content(55);
        $content = rtrim($content, ';');
        $content = preg_replace('/^var require =/', '', $content);

        $config = json_decode($content, JSON_OBJECT_AS_ARRAY);
        $this->assertIsArray($config);
        $this->assertSame('https://www.example.com/moodle/lib/requirejs.php/55/', $config['baseUrl']);
    }
}
