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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Covers the post_config_visibility_where in the reportbuilder class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_reportbuilder_post_config_visibility_where_testcase totara/reportbuilder/tests/post_config_visibility_where_test.php
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_post_config_visibility_where_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_post_config_visibility_where() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();

        // Create report. We use the user report, because we know it must include the visibility required columns.
        $rid = $this->create_report('program', 'Test program report 1');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Save a copy of all the required columns.
        $allrequiredcolumns = $report->requiredcolumns;

        // Make sure the required columns exist and have the correct "field"s.
        $this->assertArrayHasKey('ctx-id', $allrequiredcolumns);
        $this->assertEquals('ctx.id', $allrequiredcolumns['ctx-id']->field);
        $this->assertArrayHasKey('visibility-id', $allrequiredcolumns);
        $this->assertEquals('base.id', $allrequiredcolumns['visibility-id']->field);
        $this->assertArrayHasKey('visibility-visible', $allrequiredcolumns);
        $this->assertEquals('base.visible', $allrequiredcolumns['visibility-visible']->field);
        $this->assertArrayHasKey('visibility-audiencevisible', $allrequiredcolumns);
        $this->assertEquals('base.audiencevisible', $allrequiredcolumns['visibility-audiencevisible']->field);
        $this->assertArrayHasKey('base-available', $allrequiredcolumns);
        $this->assertEquals('base.available', $allrequiredcolumns['base-available']->field);
        $this->assertArrayHasKey('base-availablefrom', $allrequiredcolumns);
        $this->assertEquals('base.availablefrom', $allrequiredcolumns['base-availablefrom']->field);
        $this->assertArrayHasKey('base-availablefrom', $allrequiredcolumns);
        $this->assertEquals('base.availablefrom', $allrequiredcolumns['base-availablefrom']->field);

        // Call post_config_visibility_where and see that there is no problem.
        // Note that we're not really checking what the result of this function call is - that should be done
        // directly on totara_visibility_where. Just make sure that 'base' and 'available' are part of the result.
        list($wheresql, $params) = $report->post_config_visibility_where('program', 'base', $user->id); // No exception.
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = :tcvwnormalvisible'));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = :tcvwnormalvisiblenone'));
        $this->assertGreaterThan(0, strpos($wheresql, 'hascapabilitycontext.id = ctx.id'));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < :timefrom'));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > :timeuntil'));

        // Check that certifications gives the same result.
        list($wheresql, $params) = $report->post_config_visibility_where('certification', 'base', $user->id);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = :tcvwnormalvisible'));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = :tcvwnormalvisiblenone'));
        $this->assertGreaterThan(0, strpos($wheresql, 'hascapabilitycontext.id = ctx.id'));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < :timefrom'));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > :timeuntil'));

        // Change the ctx-id field and see that there is an exception.
        try {
            $report->requiredcolumns['ctx-id']->field = 'base.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column ctx id or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['ctx-id']->field = 'ctx.id'; // Restore the original value.

        // Remove the ctx-id required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['ctx-id']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column ctx id or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns; // Restore the original array.

        // Change the visibility-id field and see that there is an exception.
        try {
            $report->requiredcolumns['visibility-id']->field = 'ctx.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column visibility id or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['visibility-id']->field = 'base.id';

        // Remove the visibility-id required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['visibility-id']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column visibility id or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Change the visibility-visible field and see that there is an exception.
        try {
            $report->requiredcolumns['visibility-visible']->field = 'ctx.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column visibility visible or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['visibility-visible']->field = 'base.visible';

        // Remove the visibility-visible required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['visibility-visible']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column visibility visible or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Change the visibility-audiencevisible field and see that there is an exception.
        try {
            $report->requiredcolumns['visibility-audiencevisible']->field = 'ctx.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column visibility audiencevisible or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['visibility-audiencevisible']->field = 'base.audiencevisible';

        // Remove the visibility-audiencevisible required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['visibility-audiencevisible']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column visibility audiencevisible or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Change the base-available field and see that there is an exception.
        try {
            $report->requiredcolumns['base-available']->field = 'ctx.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base available or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['base-available']->field = 'base.available';

        // Remove the base-available required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['base-available']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base available or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Change the base-availablefrom field and see that there is an exception.
        try {
            $report->requiredcolumns['base-availablefrom']->field = 'ctx.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base availablefrom or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['base-availablefrom']->field = 'base.availablefrom';

        // Remove the base-availablefrom required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['base-availablefrom']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base availablefrom or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Change the base-availableuntil field and see that there is an exception.
        try {
            $report->requiredcolumns['base-availableuntil']->field = 'ctx.id';
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base availableuntil or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns['base-availableuntil']->field = 'base.availableuntil';

        // Remove the base-availableuntil required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['base-availableuntil']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base availableuntil or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // See that post_config_visibility_where doesn't care about the "available" fields with courses.
        unset($report->requiredcolumns['base-available']);
        unset($report->requiredcolumns['base-availablefrom']);
        unset($report->requiredcolumns['base-availableuntil']);
        list($wheresql, $params) = $report->post_config_visibility_where('course', 'base', $user->id); // No exception.

        // Repeat some of the "available" tests with certifications.

        // Remove the base-available required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['base-available']);
            $report->post_config_visibility_where('certification', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base available or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Remove the base-availablefrom required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['base-availablefrom']);
            $report->post_config_visibility_where('certification', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base availablefrom or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;

        // Remove the base-availableuntil required column and see that there is an exception.
        try {
            unset($report->requiredcolumns['base-availableuntil']);
            $report->post_config_visibility_where('certification', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertContains('Report is missing required column base availableuntil or field is incorrect', $e->getMessage());
        }
        $report->requiredcolumns = $allrequiredcolumns;
    }
}
