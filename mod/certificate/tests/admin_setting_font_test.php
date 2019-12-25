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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package mod_certificate
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the Certificate modules font setting.
 *
 * @see mod_certificate_admin_setting_font
 */
class mod_certificate_admin_setting_font_testcase extends advanced_testcase {

    /**
     * Tests basic setting construction.
     */
    public function test_construction() {
        $setting = new mod_certificate_admin_setting_font('test_plugin/test_name', 'Test', 'I am a test', 'hysmyeongjostdmedium');

        $this->assertSame('test_plugin', $setting->plugin);
        $this->assertSame('test_name', $setting->name);
        $this->assertSame('Test', $setting->visiblename);
        $this->assertSame('I am a test', $setting->description);
        $this->assertSame('hysmyeongjostdmedium', $setting->defaultsetting);
    }

    /**
     * Test that choices are loaded correctly and that they contain the expected defaults.
     */
    public function test_load_choices() {
        global $CFG;

        // First setting without an appropriate default.
        $setting1 = new mod_certificate_admin_setting_font('test/test', 'Test', 'I am a test', 'hysmyeongjostdmedium');

        // They need to include pdflib.php when loading choices, if they don't the expected define that is asserted below will fail.
        // This would be a real bug!
        $this->assertTrue($setting1->load_choices());
        $this->assertTrue(defined('PDF_CUSTOM_FONT_PATH'));

        $fontdir_dataroot = $CFG->dataroot.'/fonts/';
        $fontdir_default = $CFG->dirroot.'/lib/tcpdf/fonts/';

        // If this has been defined and isn't what we expect it to be then skip.
        // Skipping is good as it ensures that if you run the tests of a site that has a custom font directory these tests
        // will be skipped.
        if (PDF_CUSTOM_FONT_PATH !== $fontdir_dataroot) {
            $this->markTestSkipped('Cannot generate default list as PDF_CUSTOM_FONT_PATH has been defined in config.php');
        }

        $this->assertSame(PDF_CUSTOM_FONT_PATH, $fontdir_dataroot);
        $this->assertSame(K_PATH_FONTS, $fontdir_default);
        $this->assertFalse(is_dir($fontdir_dataroot));
        $this->assertTrue(is_dir($fontdir_default));

        $files = scandir($fontdir_default);
        // Get rid of the first two, they are just . and ..
        array_shift($files);
        array_shift($files);

        $defaultfonts = [];
        foreach ($files as $file) {
            if (substr($file, -4) == '.php') {
                $font = strtolower(basename($file, '.php'));
                if (preg_match('#(b|i|bi)$#', $font)) {
                    continue;
                }
                $defaultfonts[$font] = $font;
            }
        }

        $property = new ReflectionProperty($setting1, 'choices');
        $choices = $property->getValue($setting1);
        $this->assertIsArray($choices);
        // Not all systems scandir in the same order.
        sort($defaultfonts);
        sort($choices);
        $this->assertSame($defaultfonts, $choices);
    }
}