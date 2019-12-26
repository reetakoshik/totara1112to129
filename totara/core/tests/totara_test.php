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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test function from totara/core/totara.php file.
 */
class totara_core_totara_testcase extends advanced_testcase {
    public function test_totara_major_version() {
        global $CFG;

        $majorversion = totara_major_version();
        $this->assertIsString($majorversion);
        $this->assertRegExp('/^[0-9]+$/', $majorversion);

        $TOTARA = null;
        require("$CFG->dirroot/version.php");
        $this->assertSame(0, strpos($TOTARA->version, $majorversion));

        // Make sure the totara_major_version() is actually used in lang pack downloads.
        require_once("$CFG->dirroot/lib/componentlib.class.php");
        $installer = new lang_installer();
        $this->assertSame('https://download.totaralms.com/lang/T' . $majorversion . '/', $installer->lang_pack_url());
    }

    /**
     * Test that all files and directories are using a suitable bitmask.
     */
    public function test_file_bitmask() {

        $files = \totara_core\helper::get_incorrectly_executable_files();

        if (!empty($files)) {
            // We want to provide a meaningful message here.
            $lines = [];
            foreach ($files as $relpath => $file) {
                $lines[] = "{$relpath} is not correctly bitmasked, it is using ".$this->describe_bitmask($file->getPerms());
            }

            // If you get here because of a failure, to fix the perms you can run the following CLI script:
            //    totara/core/dev/fix_file_permissions.php
            $this->fail(join("\n", $lines));
        } else {
            $this->assertEmpty($files);
        }
    }

    /**
     * Make sure top level plugin directories are not symlinks.
     */
    public function test_no_plugin_sumlinks() {
        $types = core_component::get_plugin_types();
        foreach ($types as $type => $typedir) {
            $plugins = core_component::get_plugin_list($type);
            foreach ($plugins as $name => $plugindir) {
                $this->assertFalse(is_link($plugindir), "Totara plugins must not be installed via symlinks, you need to fix $plugindir");
            }
        }
    }

    /**
     * Data provider to check visibility of an item.
     *
     * @return array $data Data to be used by test_totara_is_item_visibility_hidden.
     */
    public function visibility_data() {
        $data = array(
            array(0, 1, COHORT_VISIBLE_NOUSERS, false), // Audiencevisibility off, Visible true, audiencevisible set to no users.
            array(0, 1, COHORT_VISIBLE_ALL, false), // Audiencevisibility off, Visible true, audiencevisible set to all.
            array(0, 1, COHORT_VISIBLE_AUDIENCE, false), // Audiencevisibility off, Visible true, audiencevisible set to audience.
            array(0, 1, COHORT_VISIBLE_ENROLLED, false), // Audiencevisibility off, Visible true, audiencevisible set to enrolled.
            array(0, 0, COHORT_VISIBLE_NOUSERS, true), // Audiencevisibility off, Visible false, audiencevisible set to no users.
            array(1, 0, COHORT_VISIBLE_NOUSERS, true), // Audiencevisibility on, Visible false, audiencevisible set to no users.
            array(1, 0, COHORT_VISIBLE_AUDIENCE, false), // Audiencevisibility on, Visible false, audiencevisible set to audience.
            array(1, 0, COHORT_VISIBLE_ENROLLED, false), // Audiencevisibility on, Visible false, audiencevisible set to enrolled.
            array(1, 1, COHORT_VISIBLE_NOUSERS, true), // Audiencevisibility on, Visible true, audiencevisible set to no users.
            array(1, 1, COHORT_VISIBLE_AUDIENCE, false), // Audiencevisibility on, Visible true, audiencevisible set to audience.
        );
        return $data;
    }

    /**
     * Test that totara_is_item_visibility_hidden is working as expected.
     * @param bool $audiencevisibilitysetting Setting for audience visibility (1 => ON, 0 => OFF)
     * @param bool $visible Value for normal visibility (0 => Hidden, 1 => visible)
     * @param bool $audiencevisibility Value for audience visibility.
     * @dataProvider visibility_data
     */
    public function test_totara_is_item_visibility_hidden($audiencevisibilitysetting, $visible, $audiencevisibility, $expected) {
        global $CFG;
        $this->resetAfterTest(true);

        // Create course.
        $record = array('visible' => $visible, 'audiencevisible' => $audiencevisibility);
        $course = $this->getDataGenerator()->create_course($record);

        // Set audiencevisibility setting.
        set_config('audiencevisibility', $audiencevisibilitysetting);
        $this->assertEquals($CFG->audiencevisibility, $audiencevisibilitysetting);

        // Call totara_is_item_visibility_hidden and check against the expected result.
        $this->assertEquals($expected, totara_is_item_visibility_hidden($course));
    }

    /**
     * Call totara_is_item_visibility_hidden passing an array instead of an object.
     * @expectedException coding_exception
     */
    public function test_totara_is_item_visibility_hidden_no_object() {
        $this->resetAfterTest(true);

        $item = array('visible' => 1, 'audiencevisible' => 1);
        totara_is_item_visibility_hidden($item);
    }

    /**
     * Call totara_is_item_visibility_hidden passing an object without visible property.
     * @expectedException coding_exception
     */
    public function test_totara_is_item_visibility_hidden_without_visible_property() {
        $this->resetAfterTest(true);

        $item = new stdClass();
        $item->audiencevisible = 1;
        totara_is_item_visibility_hidden($item);
    }

    /**
     * Call totara_is_item_visibility_hidden passing an object without audiencevisible property.
     * @expectedException coding_exception
     */
    public function test_totara_is_item_visibility_hidden_without_audiencevisible_property() {
        $this->resetAfterTest(true);

        $item = new stdClass();
        $item->visible = 1;
        totara_is_item_visibility_hidden($item);
    }

    /**
     * Just prints a pretty picture of the permission bitmask so that its human readable.
     *
     * @param string $perms
     * @return string
     */
    private function describe_bitmask($perms) {
        $perms = decoct($perms);
        return substr($perms, -3);
    }
}

