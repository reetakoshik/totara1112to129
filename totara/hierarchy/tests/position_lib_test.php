<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package totara_hierarchy
 *
 * PhpUnit tests for hierarchy/prefix/position/lib.php
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_hierarchy_position_lib_testcase totara/hierarchy/tests/position_lib_test.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');


class totara_hierarchy_position_lib_testcase extends advanced_testcase {

    public function test_get_user_positions() {
        $this->resetAfterTest();

        $posgenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posfwk = $posgenerator->create_framework('position');
        $pos1 = $posgenerator->create_hierarchy($posfwk->id, 'position');
        $pos2 = $posgenerator->create_hierarchy($posfwk->id, 'position');
        $pos3 = $posgenerator->create_hierarchy($posfwk->id, 'position');
        $pos4 = $posgenerator->create_hierarchy($posfwk->id, 'position');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Note that the order here is specific - sortorder is automatically set and determines result order.
        $user1ja1 = \totara_job\job_assignment::create_default($user1->id, array('positionid' => $pos1->id));
        $user1ja2 = \totara_job\job_assignment::create_default($user1->id, array('positionid' => $pos3->id));
        $user2ja1 = \totara_job\job_assignment::create_default($user2->id, array('positionid' => $pos4->id));
        $user2ja2 = \totara_job\job_assignment::create_default($user2->id, array('positionid' => $pos1->id));

        $position = new position();

        $user1positions = $position->get_user_positions($user1);
        $this->assertEquals(array($user1ja1->id, $user1ja2->id), array_keys($user1positions));
        unset($user1positions[$user1ja1->id]->jobassignmentid);
        unset($user1positions[$user1ja2->id]->jobassignmentid);
        $this->assertEquals(array($user1ja1->id => $pos1, $user1ja2->id => $pos3), $user1positions);

        $user2positions = $position->get_user_positions($user2);
        $this->assertEquals(array($user2ja1->id, $user2ja2->id), array_keys($user2positions));
        unset($user2positions[$user2ja1->id]->jobassignmentid);
        unset($user2positions[$user2ja2->id]->jobassignmentid);
        $this->assertEquals(array($user2ja1->id => $pos4, $user2ja2->id => $pos1), $user2positions);
    }
}
