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
 * Tests covering totara_core\access class and common access related problems in Totara.
 */
class totara_core_access_testcase extends advanced_testcase {

    /**
     * Test that context map is being filled properly.
     */
    public function test_context_map() {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/user/lib.php");
        require_once("{$CFG->dirroot}/course/lib.php");

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Make sure the man is installed properly in tests.
        $prevmap = $DB->get_records('context_map', array(), 'id ASC');
        totara_core\access::build_context_map();
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);

        // Make sure full purge of map is rebuild with the same number of items.
        $DB->delete_records('context_map', array());
        totara_core\access::build_context_map();
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertCount(count($prevmap), $newmap);

        // Make sure context creation adds entries.
        $prevmap = $DB->get_records('context_map', array(), 'id ASC');
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $syscontext = context_system::instance();
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertCount(count($prevmap) + 2, $newmap); // System and user entry.
        $userentry = array_pop($newmap);
        $systementry = array_pop($newmap);
        $this->assertEquals($usercontext->id, $userentry->parentid);
        $this->assertEquals($usercontext->id, $userentry->childid);
        $this->assertEquals($syscontext->id, $systementry->parentid);
        $this->assertEquals($usercontext->id, $systementry->childid);
        delete_user($user);
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);

        // And finally the context moving.
        $category = $this->getDataGenerator()->create_category();
        $catcontext = context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course(array('category'=>$category->id));
        $coursecontext = context_course::instance($course->id);
        $page = $generator->create_module('page', array('course'=>$course->id));
        $modcontext = context_module::instance($page->cmid);
        $newcategory = $this->getDataGenerator()->create_category();
        $newcatcontext = context_coursecat::instance($newcategory->id);

        $prevmap = $DB->get_records('context_map', array(), 'id ASC');
        totara_core\access::build_context_map();
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);

        $this->setAdminUser();
        $course->category = $newcategory->id;
        update_course($course);

        $movedcourse = $DB->get_record('course', array('id' => $course->id));
        $movedcoursecontext = context_course::instance($movedcourse->id);
        $this->assertEquals($newcategory->id, $movedcourse->category);
        $this->assertSame("/{$syscontext->id}/{$newcatcontext->id}/{$movedcoursecontext->id}", $movedcoursecontext->path);

        $this->assertCount(4, $DB->get_records('context_map', array('childid' => $modcontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $modcontext->id, 'parentid' => $syscontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $modcontext->id, 'parentid' => $newcatcontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $modcontext->id, 'parentid' => $movedcoursecontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $modcontext->id, 'parentid' => $modcontext->id)));

        $this->assertCount(3, $DB->get_records('context_map', array('childid' => $movedcoursecontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $movedcoursecontext->id, 'parentid' => $syscontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $movedcoursecontext->id, 'parentid' => $newcatcontext->id)));
        $this->assertTrue($DB->record_exists('context_map', array('childid' => $movedcoursecontext->id, 'parentid' => $movedcoursecontext->id)));

        $prevmap = $DB->get_records('context_map', array(), 'id ASC');
        totara_core\access::build_context_map();
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);

        // Test that unexpected context tree changes are detected as hacks.
        $this->assertDebuggingNotCalled();
        $prehack = new stdClass();
        $prehack->parentid = $catcontext->id;
        $prehack->childid = $modcontext->id;
        $DB->insert_record('context_map', $prehack);
        totara_core\access::build_context_map();
        $this->assertDebuggingCalled('Incorrect entries detected in context_map table, this is likely a result of unsupported changes in context table.');
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);

        // Make sure the building can be run inside db transaction.
        $this->assertFalse($DB->is_transaction_started());
        $trans = $DB->start_delegated_transaction();
        totara_core\access::build_context_map();
        $this->assertTrue($DB->is_transaction_started());
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);
        $trans->allow_commit();
        $newmap = $DB->get_records('context_map', array(), 'id ASC');;
        $this->assertEquals($prevmap, $newmap);
    }

    public function test_get_has_capability_sql() {
        global $DB;
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create a context hierarchy.
        $category = $this->getDataGenerator()->create_category();
        $catcontext = context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course(array('category'=>$category->id));
        $coursecontext = context_course::instance($course->id);
        $page = $generator->create_module('page', array('course'=>$course->id));
        $modcontext = context_module::instance($page->cmid);

        // Create a user context.
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        // An unrelated user.
        $user2 = $this->getDataGenerator()->create_user();

        // Create the system context.
        $systemcontext = context_system::instance();

        // Test with 'moodle/site:config' as it isn't set in any role by default.
        $capability = 'moodle/site:config';

        // Define some roles to test with.
        $emptyrole = create_role('Empty role', 'emptyrole', 'This role has no permissions set');
        $prohibitrole = create_role('Prohibit Role', 'prohibitrole', 'This role has prohibit set on moodle/site:config capability');
        assign_capability($capability, CAP_PROHIBIT, $prohibitrole, $systemcontext);
        $allowrole = create_role('Allow Role', 'allowrole', 'This role has allow set on moodle/site:config capability');
        assign_capability($capability, CAP_ALLOW, $allowrole, $systemcontext);
        $preventrole = create_role('Prevent Role', 'preventrole', 'This role has prevent set on moodle/site:config capability');
        assign_capability($capability, CAP_PREVENT, $preventrole, $systemcontext);

        $allowpreventrole = create_role('Allow-Prevent Role', 'allowpreventrole', 'This role has allow set on moodle/site:config capability in the system context, but then the same capability is overridden with prevent in the course context');
        assign_capability($capability, CAP_ALLOW, $allowpreventrole, $systemcontext);
        assign_capability($capability, CAP_PREVENT, $allowpreventrole, $coursecontext);

        $preventallowrole = create_role('Prevent-Allow Role', 'preventallowrole', 'This role has prevent set on moodle/site:config capability in the system context, but then the same capability is overridden with allow in the course context');
        assign_capability($capability, CAP_PREVENT, $preventallowrole, $systemcontext);
        assign_capability($capability, CAP_ALLOW, $preventallowrole, $coursecontext);

        $allowinheritrole = create_role('Allow-Inherit Role', 'allowinheritrole', 'This role has allow set on moodle/site:config capability in the system context, then the same capability is overridden with inherit in the course context. This should make no difference!');
        assign_capability($capability, CAP_ALLOW, $allowinheritrole, $systemcontext);
        assign_capability($capability, CAP_INHERIT, $allowinheritrole, $coursecontext);

        $inheritallowrole = create_role('Inherit-Allow Role', 'inheritallowrole', 'This role has inherit explicitly set on moodle/site:config capability in the system context, then the same capability is overridden with allow in the course context.');
        assign_capability($capability, CAP_INHERIT, $inheritallowrole, $systemcontext);
        assign_capability($capability, CAP_ALLOW, $inheritallowrole, $coursecontext);

        // Test each role separately by assigning it in the system context and
        // checking in the module context.
        $roles = array($emptyrole, $prohibitrole, $allowrole, $preventrole, $allowpreventrole,
            $preventallowrole, $allowinheritrole, $inheritallowrole);
        $expectedallowmatches = array($allowrole, $preventallowrole, $allowinheritrole, $inheritallowrole);
        $expectedprohibitmatches = array($prohibitrole);
        foreach ($roles as $roleid) {
            // Assign this role in the system context.
            $this->getDataGenerator()->role_assign(
                $roleid,
                $user->id,
                $systemcontext->id);

            // Test for an allow in the module context.
            $method = new \ReflectionMethod('totara_core\access', 'get_allow_prevent_check_sql');
            $method->setAccessible(true);
            list($allowpreventsql, $allowpreventparams) = $method->invoke(null, $capability, $user->id, 'cx.id');
            $sql = "SELECT * FROM {context} cx WHERE id = :id AND EXISTS ({$allowpreventsql})";
            $params = array_merge(array('id' => $modcontext->id), $allowpreventparams);
            $out = $DB->get_records_sql($sql, $params);
            if (in_array($roleid, $expectedallowmatches)) {
                $this->assertNotEmpty($out);
            } else {
                $this->assertEmpty($out);
            }

            // Test for a prohibit in the module context.
            $method = new \ReflectionMethod('totara_core\access', 'get_prohibit_check_sql');
            $method->setAccessible(true);
            list($prohibitsql, $prohibitparams) = $method->invoke(null, $capability, $user->id, 'cx.id');
            $sql = "SELECT * FROM {context} cx WHERE id = :id AND NOT EXISTS ({$prohibitsql})";
            $params = array_merge(array('id' => $modcontext->id), $prohibitparams);
            $out = $DB->get_records_sql($sql, $params);
            if (in_array($roleid, $expectedprohibitmatches)) {
                $this->assertEmpty($out, "Role id {$roleid} expected to be prohibited but records found");
            } else {
                $this->assertNotEmpty($out, "Role id {$roleid} expected to not be prohibited but no records found");
            }

            // Unassign the role again.
            role_unassign($roleid, $user->id, $systemcontext->id);
        }

        // Now we need to test combinations:
        $grantedcombinations = array(
            array($emptyrole, $allowrole), // One allow.
            array($allowrole, $preventallowrole, $preventrole), // Prevent, but also allow.
        );
        $deniedcombinations = array(
            array($emptyrole), // No allows.
            array($emptyrole, $preventrole), // Prevent only.
            array($allowpreventrole), // Overridden prevent only.
            array($emptyrole, $allowrole, $prohibitrole), // Allow with prohibit.
            array($prohibitrole), // Prohibit alone.
            array(), // No roles.
        );

        foreach ($grantedcombinations as $rolestoassign) {
            foreach ($rolestoassign as $roleid) {
                $this->getDataGenerator()->role_assign(
                    $roleid,
                    $user->id,
                    $systemcontext->id);
            }

            // Test user is granted capability.
            list($hascapsql, $hascapparams) = totara_core\access::get_has_capability_sql($capability, 'c.id', $user->id);
            $sql = "SELECT 1 FROM {context} c WHERE c.id = :id AND ({$hascapsql})";
            $params = array_merge(array('id' => $modcontext->id), $hascapparams);
            $out = $DB->get_records_sql($sql, $params);
            $this->assertNotEmpty($out);

            // Unassign roles again.
            foreach ($rolestoassign as $roleid) {
                role_unassign($roleid, $user->id, $systemcontext->id);
            }

        }

        foreach ($deniedcombinations as $rolestoassign) {
            foreach ($rolestoassign as $roleid) {
                $this->getDataGenerator()->role_assign(
                    $roleid,
                    $user->id,
                    $systemcontext->id);
            }

            // Test user is NOT granted capability.
            list($hascapsql, $hascapparams) = totara_core\access::get_has_capability_sql($capability, 'c.id', $user->id);
            $sql = "SELECT 1 FROM {context} c WHERE c.id = :id AND ({$hascapsql})";
            $params = array_merge(array('id' => $modcontext->id), $hascapparams);
            $out = $DB->get_records_sql($sql, $params);
            $this->assertEmpty($out);

            // Unassign roles again.
            foreach ($rolestoassign as $roleid) {
                role_unassign($roleid, $user->id, $systemcontext->id);
            }

        }

        // Make sure that outside table aliases do not collide with internals.
        $this->getDataGenerator()->role_assign($allowrole, $user->id, $coursecontext->id);
        list($hascapsql, $params) = totara_core\access::get_has_capability_sql('moodle/site:config', 'c.id', $user->id);
        $sql = "SELECT c.id
                  FROM {context} c
                 WHERE {$hascapsql}
              ORDER BY c.id ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertGreaterThanOrEqual(2, $result); // Course, page and some blocks most likely.

        list($hascapsql, $params) = totara_core\access::get_has_capability_sql('moodle/site:config', 'ctx.id', $user->id);
        $sql = "SELECT ctx.id
                  FROM {context} ctx
             LEFT JOIN {context} maincontext ON maincontext.id = -1
             LEFT JOIN {context_map} lineage ON lineage.id = -1
             LEFT JOIN {role_capabilities} rc ON rc.id = -1
                 WHERE {$hascapsql}
              ORDER BY ctx.id ASC";
        $newresult = $DB->get_records_sql($sql, $params);
        $this->assertEquals($result, $newresult);
    }

    /**
     * Verify the contextidfield parameter is validated properly.
     */
    public function test_validate_contextidfield() {
        $this->resetAfterTest(true);

        $method = new \ReflectionMethod('totara_core\access', 'validate_contextidfield');
        $method->setAccessible(true);

        try {
            $method->invoke(null, '10');
            $this->fail('Exception expected when integer given');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $method->invoke(null, ':param');
            $this->fail('Exception expected when parameter given');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $method->invoke(null, '?');
            $this->fail('Exception expected when parameter given');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $method->invoke(null, '{context}.id');
            $this->fail('Exception expected when {context} table given');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $method->invoke(null, 'hascapabilitycontext.id');
            $this->fail('Exception expected when hascapabilitycontext table alias given');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $method->invoke(null, 'pa ram');
            $this->fail('Exception expected when non-sql given');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        $user = $this->getDataGenerator()->create_user();
        list($hascapsql, $params) = totara_core\access::get_has_capability_sql('moodle/site:config', '{something}.contextid', $user->id);
        $this->assertContains('{something}.contextid', $hascapsql);

        list($hascapsql, $params) = totara_core\access::get_has_capability_sql('moodle/site:config', 'some_thing3.xyz_3ed', $user->id);
        $this->assertContains('some_thing3.xyz_3ed', $hascapsql);

        list($hascapsql, $params) = totara_core\access::get_has_capability_sql('moodle/site:config', 'xyz_3ed', $user->id);
        $this->assertContains('xyz_3ed', $hascapsql);
    }

    /**
     * Detect common problems in all db/access.php files
     */
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

            $this->assertIsArray($capabilities);
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

