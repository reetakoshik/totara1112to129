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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_plan
 */

use totara_plan\userdata\plan;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;

global $CFG;

defined('MOODLE_INTERNAL') || die();

/**
 * Testing plan userdata item
 *
 * @group totara_plan
 * @group totara_userdata
 */
class totara_plan_userdata_plan_test extends advanced_testcase {

    /**
     * Setup data used for purge, export and count functions
     *
     * @return stdClass Data for tests
     */
    private function setupdata() {
        global $DB;

        $data = new stdClass();

        $datagenerator = $this->getDataGenerator();
        // Setup data
        // 2 users, one with 2 plans and one with 1 plan with some some common content
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        $data->competencyframework = $hierarchygenerator->create_framework('competency');
        $data->competency1 = $hierarchygenerator->create_hierarchy($data->competencyframework->id, 'competency');
        $data->competency2 = $hierarchygenerator->create_hierarchy($data->competencyframework->id, 'competency');
        $data->competency3 = $hierarchygenerator->create_hierarchy($data->competencyframework->id, 'competency');
        $data->competency4 = $hierarchygenerator->create_hierarchy($data->competencyframework->id, 'competency');

        $data->plan1record = $plangenerator->create_learning_plan(array('userid' => $data->user1->id));
        $data->plan1 = new development_plan($data->plan1record->id);
        $data->plan2record = $plangenerator->create_learning_plan(array('userid' => $data->user2->id));
        $data->plan2 = new development_plan($data->plan2record->id);
        $data->plan3record = $plangenerator->create_learning_plan(array('userid' => $data->user1->id));
        $data->plan3 = new development_plan($data->plan3record->id);

        $data->course1 = $datagenerator->create_course();
        $data->course2 = $datagenerator->create_course();
        $data->course3 = $datagenerator->create_course();
        $data->course4 = $datagenerator->create_course();

        $this->setAdminUser();

        // Setup plan 1
        $plangenerator->add_learning_plan_course($data->plan1->id, $data->course1->id);
        $data->course1->assignid[$data->plan1->id] = $DB->get_field('dp_plan_course_assign', 'id', ['planid' => $data->plan1->id, 'courseid' => $data->course1->id]);
        $plangenerator->add_learning_plan_course($data->plan1->id, $data->course2->id);
        $data->course2->assignid[$data->plan1->id] = $DB->get_field('dp_plan_course_assign', 'id', ['planid' => $data->plan1->id, 'courseid' => $data->course2->id]);

        $plangenerator->add_learning_plan_competency($data->plan1->id, $data->competency1->id);
        $data->competency1->assignid[$data->plan1->id] = $DB->get_field('dp_plan_competency_assign', 'id', ['planid' => $data->plan1->id, 'competencyid' => $data->competency1->id]);
        $plangenerator->add_learning_plan_competency($data->plan1->id, $data->competency2->id);
        $data->competency2->assignid[$data->plan1->id] = $DB->get_field('dp_plan_competency_assign', 'id', ['planid' => $data->plan1->id, 'competencyid' => $data->competency2->id]);
        $plangenerator->add_learning_plan_competency($data->plan1->id, $data->competency3->id);
        $data->competency3->assignid[$data->plan1->id] = $DB->get_field('dp_plan_competency_assign', 'id', ['planid' => $data->plan1->id, 'competencyid' => $data->competency3->id]);

        $data->objective1 = $plangenerator->create_learning_plan_objective($data->plan1->id, $data->user1->id, null);

        // Add links between items
        // Course 1 -> Competency 1
        // Competency 2 -> Competency 3
        $link1_data = new \stdClass();
        $link1_data->component1 = 'competency';
        $link1_data->itemid1 = $data->competency1->assignid[$data->plan1->id];
        $link1_data->component2 = 'course';
        $link1_data->itemid2 = $data->course1->assignid[$data->plan1->id];

        $link1_data->id = $DB->insert_record('dp_plan_component_relation', $link1_data);
        $data->link1 = $link1_data;

        $link2_data = new \stdClass();
        $link2_data->component1 = 'competency';
        $link2_data->itemid1 = $data->competency2->assignid[$data->plan1->id];
        $link2_data->component2 = 'competency';
        $link2_data->itemid2 = $data->competency3->assignid[$data->plan1->id];

        $link2_data->id = $DB->insert_record('dp_plan_component_relation', $link2_data);
        $data->link2 = $link2_data;

        // Add linked evidence
        $evidence_data1 = new stdClass();
        $evidence_data1->userid = $data->user1->id;
        $data->evidenceitem1 = $plangenerator->create_evidence($evidence_data1);

        $evidencelink1_data = new \stdClass();
        $evidencelink1_data->evidenceid = $data->evidenceitem1->id;
        $evidencelink1_data->planid = $data->plan1->id;
        $evidencelink1_data->component = 'course';
        $evidencelink1_data->itemid = $data->course1->assignid[$data->plan1->id];
        $evidencelink1_data->id = $DB->insert_record('dp_plan_evidence_relation', $evidencelink1_data);
        $data->evidencelink1 = $evidencelink1_data;

        // Add some files to plan 1
        $contextsystem = \context_system::instance();
        $fs = get_file_storage();

        $data->file1 = (object)[
            'contextid' => $contextsystem->id,
            'component' => 'totara_plan',
            'filearea' => 'dp_plan',
            'itemid' => $data->plan1->id,
            'filepath' => '/',
            'filename' => 'testfile.txt'
        ];
        $data->file1_info = $fs->create_file_from_string($data->file1, 'testfile');

        $data->file2 = (object)[
            'contextid' => $contextsystem->id,
            'component' => 'totara_plan',
            'filearea' => 'dp_plan_objective',
            'itemid' => $data->objective1->id,
            'filepath' => '/',
            'filename' => 'testfile.txt'
        ];
        $data->file2_info = $fs->create_file_from_string($data->file2, 'testfile');

        // Add some comments
        $comment_generator = $this->getDataGenerator()->get_plugin_generator('core_comment');
        $data->plan1_comment = $comment_generator->add_comment('totara_plan', 'plan_overview', $data->plan1->id, $contextsystem, 'Test plan 1 comment');
        $data->course1_comment = $comment_generator->add_comment('totara_plan', 'plan_course_item', $data->course1->assignid[$data->plan1->id], $contextsystem, 'Test course 1 comment');
        $data->competency1_comment = $comment_generator->add_comment('totara_plan', 'plan_competency_item', $data->competency1->assignid[$data->plan1->id], $contextsystem, 'Test competency 1 comment');
        $data->objective1_comment = $comment_generator->add_comment('totara_plan', 'plan_objective_item', $data->objective1->id, $contextsystem, 'Test objective 1 comment');

        // Setup plan 2
        $plangenerator->add_learning_plan_course($data->plan2->id, $data->course2->id);
        $data->course2->assignid[$data->plan2->id] = $DB->get_field('dp_plan_course_assign', 'id', ['planid' => $data->plan2->id, 'courseid' => $data->course2->id]);
        $plangenerator->add_learning_plan_course($data->plan2->id, $data->course3->id);
        $plangenerator->add_learning_plan_course($data->plan2->id, $data->course4->id);

        $plangenerator->add_learning_plan_competency($data->plan2->id, $data->competency3->id);

        $data->objective2 = $plangenerator->create_learning_plan_objective($data->plan2->id, $data->user2->id, null);

        // Add some files to plan 2
        $data->file3 = (object)[
            'contextid' => $contextsystem->id,
            'component' => 'totara_plan',
            'filearea' => 'dp_plan',
            'itemid' => $data->plan2->id,
            'filepath' => '/',
            'filename' => 'testfile.txt'
        ];
        $fs->create_file_from_string($data->file3, 'testfile');

        $data->file4 = (object)[
            'contextid' => $contextsystem->id,
            'component' => 'totara_plan',
            'filearea' => 'dp_plan_objective',
            'itemid' => $data->objective2->id,
            'filepath' => '/',
            'filename' => 'testfile.txt'
        ];
        $fs->create_file_from_string($data->file4, 'testfile');

        // Add a comment
        $data->course2_comment = $comment_generator->add_comment('totara_plan', 'plan_course_item', $data->course2->assignid[$data->plan2->id], $contextsystem, 'Test course 1 comment');



        // Setup plan 3 (with minimal content)
        $plangenerator->add_learning_plan_course($data->plan3->id, $data->course1->id);

        return $data;
    }

    /**
     * Test function is_purgeable
     */
    public function test_is_purgeable() {
        $this->assertTrue(plan::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(plan::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(plan::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test purging function
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest();

        // Setup data.
        $data = $this->setupdata();

        $target_user1 = new target_user($data->user1);

        // Check the number of records before purging
        $this->assertEquals(2, $DB->count_records('dp_plan', ['userid' => $data->user1->id]));
        $this->assertEquals(1, $DB->count_records('dp_plan', ['userid' => $data->user2->id]));

        // Check files in plans 1 and 2 all exist.
        $fs = get_file_storage();
        $this->assertTrue(
            $fs->file_exists(
                $data->file1->contextid,
                $data->file1->component,
                $data->file1->filearea,
                $data->file1->itemid,
                $data->file1->filepath,
                $data->file1->filename
            )
        );

        $this->assertTrue(
            $fs->file_exists(
                $data->file2->contextid,
                $data->file2->component,
                $data->file2->filearea,
                $data->file2->itemid,
                $data->file2->filepath,
                $data->file2->filename
            )
        );

        $this->assertTrue(
            $fs->file_exists(
                $data->file3->contextid,
                $data->file3->component,
                $data->file3->filearea,
                $data->file3->itemid,
                $data->file3->filepath,
                $data->file3->filename
            )
        );

        $this->assertTrue(
            $fs->file_exists(
                $data->file4->contextid,
                $data->file4->component,
                $data->file4->filearea,
                $data->file4->itemid,
                $data->file4->filepath,
                $data->file4->filename
            )
        );

        // Check that there are 5 comments in the table
        // 4 for plan 1 and 1 for plan 2
        $this->assertEquals(5, $DB->count_records('comments'));

        // Purge
        $result = plan::purge($target_user1, \context_system::instance());

        // Ensure purge removed correct data
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals(0, $DB->count_records('dp_plan', ['userid' => $data->user1->id]));
        $this->assertEquals(1, $DB->count_records('dp_plan', ['userid' => $data->user2->id]));

        // Check we only deleted file for plan 1.
        $fs = get_file_storage();
        $this->assertFalse(
            $fs->file_exists(
                $data->file1->contextid,
                $data->file1->component,
                $data->file1->filearea,
                $data->file1->itemid,
                $data->file1->filepath,
                $data->file1->filename
            )
        );

        $this->assertFalse(
            $fs->file_exists(
                $data->file2->contextid,
                $data->file2->component,
                $data->file2->filearea,
                $data->file2->itemid,
                $data->file2->filepath,
                $data->file2->filename
            )
        );

        $this->assertTrue(
            $fs->file_exists(
                $data->file3->contextid,
                $data->file3->component,
                $data->file3->filearea,
                $data->file3->itemid,
                $data->file3->filepath,
                $data->file3->filename
            )
        );

        $this->assertTrue(
            $fs->file_exists(
                $data->file4->contextid,
                $data->file4->component,
                $data->file4->filearea,
                $data->file4->itemid,
                $data->file4->filepath,
                $data->file4->filename
            )
        );

        // Check to see if comments have been purged.
        $this->assertEquals(1, $DB->count_records('comments'));
    }

    /**
     * Test function is_exportable
     */
    public function test_is_exportable() {
        $this->assertTrue(plan::is_exportable());
    }

    /**
     * Test the export function
     */
    public function test_export() {
        $this->resetAfterTest();

        $data = $this->setupdata();

        $target_user = new target_user($data->user1);

        $export = plan::execute_export($target_user, \context_system::instance());

        // Test export contains what we expect
        $this->assertCount(2, $export->data);
        $this->assertCount(2, $export->files);

        // Check items for plan 1.
        $this->assertEquals('Test Learning Plan 1', $export->data[0]->name);
        $this->assertCount(2, $export->data[0]->componentinfo['course']->items);
        $this->assertCount(3, $export->data[0]->componentinfo['competency']->items);
        $this->assertCount(1, $export->data[0]->componentinfo['objective']->items);
        $this->assertCount(0, $export->data[0]->componentinfo['program']->items);

        // Check items for plan 3.
        $this->assertEquals('Test Learning Plan 3', $export->data[1]->name);
        $this->assertCount(1, $export->data[1]->componentinfo['course']->items);
        $this->assertCount(0, $export->data[1]->componentinfo['competency']->items);
        $this->assertCount(0, $export->data[1]->componentinfo['objective']->items);
        $this->assertCount(0, $export->data[1]->componentinfo['program']->items);

        $plan1expected = new stdClass();
        $plan1expected->name = $data->plan1->name;
        $plan1expected->description = $data->plan1->description;
        $plan1expected->startdate = $data->plan1->startdate;
        $plan1expected->enddate = $data->plan1->enddate;;
        $plan1expected->status = $data->plan1->status;
        $plan1expected->timecompleted = null;

        $course1 = new stdClass();
        $course1->name = 'Test course 1';
        $course1->id = $data->course1->assignid[$data->plan1->id];
        $linkedexpected1 = new stdClass();
        $linkedexpected1->component1 = 'competency';
        $linkedexpected1->itemid1 = $data->competency1->assignid[$data->plan1->id];
        $linkedexpected1->component2 = 'course';
        $linkedexpected1->itemid2 = $data->course1->assignid[$data->plan1->id];
        $linkedexpected1->mandatory = null;
        $course1->linkeditems[] = $linkedexpected1;
        $course1->linkedevidence[] = $data->evidencelink1;
        $course1->duedate = null;
        $commentexpected1 = new stdClass();
        $commentexpected1->content = 'Test course 1 comment';
        $commentexpected1->commenter = 'Admin User';
        $course1->comments[] = $commentexpected1;
        $course2 = new stdClass();
        $course2->name = 'Test course 2';
        $course2->id = $data->course2->assignid[$data->plan1->id];
        $course2->linkeditems = array();
        $course2->linkedevidence = array();
        $course2->duedate = null;
        $courseitems[] = $course1;
        $courseitems[] = $course2;
        $coursecomponent = new stdClass();
        $coursecomponent->component = 'course';
        $coursecomponent->items = $courseitems;
        $this->assertEquals($coursecomponent, $export->data[0]->componentinfo['course']);

        $competency1 = new stdClass();
        $competency1->name = 'Test Competency 1';
        $competency1->id = $data->competency1->assignid[$data->plan1->id];
        $competency1->proficiencyid = null;
        $competency1->status = null;
        $competency1->sort = null;
        $competency1->linkeditems[] = $linkedexpected1;
        $competency1->linkedevidence = array();
        $competency1->priority = null;
        $commentexpected2 = new stdClass();
        $commentexpected2->content = 'Test competency 1 comment';
        $commentexpected2->commenter = 'Admin User';
        $competency1->comments[] = $commentexpected2;
        $competencyitems[] = $competency1;
        $competency2 = new stdClass();
        $competency2->name = 'Test Competency 2';
        $competency2->id = $data->competency2->assignid[$data->plan1->id];
        $competency2->proficiencyid = null;
        $competency2->status = null;
        $competency2->sort = null;
        $linkedexpected2 = new stdClass();
        $linkedexpected2->component1 = 'competency';
        $linkedexpected2->itemid1 = $data->competency2->assignid[$data->plan1->id];
        $linkedexpected2->component2 = 'competency';
        $linkedexpected2->itemid2 = $data->competency3->assignid[$data->plan1->id];
        $linkedexpected2->mandatory = null;
        $competency2->linkeditems[] = $linkedexpected2;
        $competency2->linkedevidence = array();
        $competency2->priority = null;
        $competencyitems[] = $competency2;
        $competency3 = new stdClass();
        $competency3->name = 'Test Competency 3';
        $competency3->id = $data->competency3->assignid[$data->plan1->id];
        $competency3->proficiencyid = null;
        $competency3->status = null;
        $competency3->sort = null;
        $competency3->linkeditems[] = $linkedexpected2;
        $competency3->linkedevidence = array();
        $competency3->priority = null;
        $competencyitems[] = $competency3;
        $competencycomponent = new stdClass();
        $competencycomponent->component = 'competency';
        $competencycomponent->items = $competencyitems;
        $this->assertEquals($competencycomponent, $export->data[0]->componentinfo['competency']);

        $objective1 = new stdClass();
        $objective1->name = 'Test Objective 1';
        $objective1->id = $data->objective1->id;
        $objective1->description = '<p>Test Objective 1 description</p>';
        $objective1->progress = '3';
        $objective1->achieved = '0';
        $objective1->linkeditems = array();
        $objective1->linkedevidence = array();
        $objective1->priority = 'Low';
        $commentexpected3 = new stdClass();
        $commentexpected3->content = 'Test objective 1 comment';
        $commentexpected3->commenter = 'Admin User';
        $objective1->comments[] = $commentexpected3;
        $file_struct = [
            'fileid' => $data->file2_info->get_id(),
            'filename' => $data->file2_info->get_filename(),
            'contenthash' => $data->file2_info->get_contenthash()
        ];
        $objective1->files[] = $file_struct;
        $objectiveitems[] = $objective1;
        $objectivecomponent = new stdClass();
        $objectivecomponent->component = 'objective';
        $objectivecomponent->items = $objectiveitems;
        $this->assertEquals($objectivecomponent, $export->data[0]->componentinfo['objective']);
    }

    /**
     * Test function is_countable
     */
    public function test_is_countable() {
        $this->assertTrue(plan::is_countable());
    }

    /**
     * Test the count function
     */
    public function test_count() {
        $this->resetAfterTest();

        $data = $this->setupdata();

        $context = \context_system::instance();
        $target_user1 = new target_user($data->user1);
        $target_user2 = new target_user($data->user2);

        $count = plan::execute_count($target_user1, $context);
        $this->assertEquals(2, $count);

        $count = plan::execute_count($target_user2, $context);
        $this->assertEquals(1, $count);
    }
}
