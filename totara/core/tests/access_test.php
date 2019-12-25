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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Detect common problems in all db/access.php files
 */
class totara_core_access_testcase extends advanced_testcase {
    public function test_access_files() {
        global $CFG;

        // Please make sure that any added capabilities here are really needed BEFORE creating a new course,
        // the creator gets assigned a new teacher level role in the new course right after creation.
        $allowedcreatorcaps = array(
            'moodle/restore:rolldates', 'moodle/category:viewhiddencategories', 'moodle/course:create',
            'moodle/course:viewhiddencourses', 'repository/coursefiles:view', 'repository/filesystem:view',
            'repository/local:view', 'repository/webdav:view', 'totara/certification:viewhiddencertifications',
            'totara/program:viewhiddenprograms', 'tool/uploadcourse:uploadcourses', 'totara/contentmarketplace:add');

        $files['core'] = "$CFG->dirroot/lib/db/access.php";

        $types = core_component::get_plugin_types();
        foreach ($types as $type => $unused) {
            $plugins = core_component::get_plugin_list($type);
            foreach ($plugins as $name => $fulldir) {
                $file = "$fulldir/db/access.php";
                if (file_exists($file)) {
                    $files[$type . '_' . $name] = $file;
                }
            }
        }

        $expecteddatakeys = [
            'archetypes',
            'captype',
            'clonepermissionsfrom',
            'contextlevel',
            'legacy',
            'riskbitmask',
        ];

        foreach ($files as $plugin => $file) {
            $capabilities = array();
            // Legacy, we don't want to see this, ever!
            ${$plugin.'_capabilities'} = null;

            include($file);

            $this->assertInternalType('array', $capabilities);
            $this->assertNull(${$plugin.'_capabilities'});

            foreach ($capabilities as $capname => $data) {

                $this::assertCapabilityNameCorrect($capname, $plugin);
                foreach (array_keys($data) as $datakey) {
                    $this->assertContains($datakey, $expecteddatakeys);
                }

                if (isset($data['archetypes'])) {
                    foreach ($data['archetypes'] as $archetype => $permission) {
                        $this->assertNotEquals(CAP_PREVENT, $permission, "Do not use CAP_PREVENT in $file, it does nothing");
                        $this->assertNotEquals(CAP_INHERIT, $permission, "Do not use CAP_INHERIT in $file, it does nothing");
                        if ($archetype !== 'guest') {
                            $this->assertNotEquals(CAP_PROHIBIT, $permission, "CAP_PROHIBIT in $file is wrong, when defining roles use it only for guest archetype");
                        }
                        if ($archetype === 'coursecreator' and !in_array($capname, $allowedcreatorcaps)) {

                            // Check if the plugin has any valid course creator plugins, exclude standard plugins.
                            // Standard plugins MUST add there caps to $allowedcreatorcaps.
                            // Of course that should be discussed with the team lead first!
                            $pluginallowedcreatorcaps = [];
                            list($plugin_type, $plugin_name) = core_component::normalize_component($plugin);
                            $standardplugins = core_plugin_manager::standard_plugins_list($plugin_type);
                            if ($standardplugins === false) {
                                $this->fail('There is something wrong with capability ' . $capname . ' - it is not supposed to be enabled for course creators!');
                            }
                            if (!in_array($plugin_name, $standardplugins)) {
                                $libfile = core_component::get_plugin_directory($plugin_type, $plugin_name) . '/lib.php';
                                if (file_exists($libfile)) {
                                    require_once($libfile);
                                    // Big and obtuse!
                                    $function = $plugin . '_get_permitted_course_creator_caps_for_testing';
                                    if (function_exists($function)) {
                                        $pluginallowedcreatorcaps = call_user_func($function);
                                    }
                                }
                            }

                            $this->assertContains($capname, array_merge($allowedcreatorcaps, $pluginallowedcreatorcaps), "Course creator archetype is intended for course creation only");
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks that the given capability name is correct.
     *
     * Please note that this assertion is for best practices only!
     * It should not be executed on capabilities coming from non-core plugins, and has a whitelist
     * for capabilities where best practice has not been followed previously.
     *
     * @param string $capname
     * @return void
     */
    private static function assertCapabilityNameCorrect($capname, $plugin) {
        // This check is copied from update_capabilities() in accesslib.php.
        if (!preg_match('|^([a-z]+)/([a-z_0-9]+):([a-z_0-9]+)$|', $capname, $matches)) {
            self::fail('Invalid capability name '.$capname);
        }
        $cap_component = $matches[1];
        $cap_plugin = $matches[2];
        $cap_cap = $matches[3];

        $subsystems = \core_component::get_core_subsystems();
        $plugintypes = \core_component::get_plugin_types();
        $pluginman = core_plugin_manager::instance();

        if ($cap_component === 'moodle') {

            if (in_array($cap_plugin, [
                'category', // Should have been course, and category in the name.
                'community', // Should have been block:community/blah.
                'filter', // Should have been filters.
                'grade', // Should have been grades.
                'restore', // Should have been backup, and restore in the name.
                'site', // Should have been core.
            ])) {
                // Exceptions for some moodle capabilities that break naming conventions.
                return;
            }
            self::assertArrayHasKey($cap_plugin, $subsystems, 'Invalid core capability name ' . $capname);
        } else {

            $cap_plugin = \core_component::normalize_componentname("{$cap_component}_{$cap_plugin}");

            // Check the capability is located within the correct plugin.
            if ($plugin !== $cap_plugin && $plugin !== 'totara_core') {
                // Totara core has a whole wadge of capabilities within it from around the system.
                // We'll just blanket ignore these for the time being, there is no real point in fixing them presently.
                // This test is just about encouraging best practice.
                self::fail("Capability is located in the wrong plugin\nExpected {$plugin}\nActual {$cap_plugin}");
            }

            // Check the capability exists.
            if (array_key_exists($cap_component, $plugintypes)) {
                // It exists, fine.
                return;
            }

            // If it isn't is the plugin it comes from a standard plugin?
            $plugininfo = $pluginman->get_plugin_info($cap_component);
            if (!$plugininfo->is_standard()) {
                // It's a third party plugin. Exclude it from this test.
                return;
            }
            // It's standard plugin, and the capability is not named as per best practices.
            self::fail('Invalid plugin capability name ' . $capname);
        }
    }
}

