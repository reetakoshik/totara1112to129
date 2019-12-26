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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_appraisal
 */

use core\event\base;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/appraisal/tests/appraisal_testcase.php');

class appraisal_event_test extends appraisal_testcase {

    public function test_appraisal_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create an appraisal to throw a creation event.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();
        $appraisalid = $appraisal->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\appraisal_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal');
        $this->assertEquals($eventdata['objectid'], $appraisalid);

        // Update the appraisal description to throw an updated event.
        $appraisal->description = 'Test Description';
        $appraisal->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\appraisal_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal');
        $this->assertEquals($eventdata['objectid'], $appraisalid);

        // Delete the appraisal to throw a deleted event.
        $appraisal->delete();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\appraisal_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal');
        $this->assertEquals($eventdata['objectid'], $appraisalid);

        $sink->close();
    }

    public function test_stage_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create an appraisal to add the stage to.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $sink->clear();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();
        $stageid = $stage->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_stage created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\stage_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage');
        $this->assertEquals($eventdata['objectid'], $stageid);
        $this->assertEquals($eventdata['other']['appraisalid'], $appraisal->id);

        // Update the stage description to throw an updated event.
        $stage->description = 'Test Description';
        $stage->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_stage deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\stage_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage');
        $this->assertEquals($eventdata['objectid'], $stageid);
        $this->assertEquals($eventdata['other']['appraisalid'], $appraisal->id);

        // Delete the stage to throw a deleted event.
        $stage->delete();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_stage updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\stage_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage');
        $this->assertEquals($eventdata['objectid'], $stageid);
        $this->assertEquals($eventdata['other']['appraisalid'], $appraisal->id);

        $sink->close();
    }

    public function test_page_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create an appraisal to add the stage to.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();

        $sink->clear();

        $page = new appraisal_page(0);
        $page->appraisalstageid = $stage->id;
        $page->name = 'Test Page';
        $page->save();
        $pageid = $page->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_page created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\page_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage_page');
        $this->assertEquals($eventdata['objectid'], $pageid);
        $this->assertEquals($eventdata['other']['stageid'], $stage->id);

        // Update the page name to throw an updated event.
        $page->name = 'Test Page (changed)';
        $page->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_page updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\page_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage_page');
        $this->assertEquals($eventdata['objectid'], $pageid);
        $this->assertEquals($eventdata['other']['stageid'], $stage->id);

        // Delete the stage to throw a deleted event.
        appraisal_page::delete($page->id);

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_page deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\page_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage_page');
        $this->assertEquals($eventdata['objectid'], $pageid);
        $this->assertEquals($eventdata['other']['stageid'], $stage->id);

        $sink->close();
    }

    public function test_question_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Set up an appraisal, stage, and page for the question.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();

        $page = new appraisal_page(0);
        $page->appraisalstageid = $stage->id;
        $page->name = 'Test Page';
        $page->save();

        $sink->clear();

        $question = new appraisal_question(0);
        $question->appraisalstagepageid = $page->id;
        $question->datatype = 'longtext';
        $question->name = 'Test Question';
        $question->save();
        $questionid = $question->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_question created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\question_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_quest_field');
        $this->assertEquals($eventdata['objectid'], $questionid);
        $this->assertEquals($eventdata['other']['pageid'], $page->id);

        $question->name = 'Test Question (changed)';
        $question->save();

        $events = $sink->get_events();
        $sink->clear();

        // Check the event data meets appraisal_question updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\question_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_quest_field');
        $this->assertEquals($eventdata['objectid'], $questionid);
        $this->assertEquals($eventdata['other']['pageid'], $page->id);

        appraisal_question::delete($question->id);

        $events = $sink->get_events();
        $sink->clear();

        // Check the event data meets appraisal_question deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'totara_appraisal');
        $this->assertEquals($eventdata['eventname'], '\totara_appraisal\event\question_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_quest_field');
        $this->assertEquals($eventdata['objectid'], $questionid);
        $this->assertEquals($eventdata['other']['pageid'], $page->id);
    }

    /**
     * Test the legacy data for old add_to_log calls, events to test:
     *      - appraisal_updated
     *      - stage_updated
     *      - page_updated
     */
    public function test_legacy_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Set up an appraisal, stage, and page to edit.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();

        $page = new appraisal_page(0);
        $page->appraisalstageid = $stage->id;
        $page->name = 'Test Page';
        $page->save();

        $sink->clear();

        // Test the legacy data for the appraisal_updated event.
        $appraisal->name = 'Test Appraisal (changed)';
        $appraisal->save();

        $events = $sink->get_events();
        $sink->clear();

        $oldurl = new moodle_url('/totara/appraisal/general.php', array('id' => $appraisal->id));
        $olddata = array(SITEID, 'appraisal', 'update appraisal', $oldurl, 'General Settings: Appraisal ID=' . $appraisal->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);

        // Test the legacy data for the stage_updated event.
        $stage->name = 'Test Stage (changed)';
        $stage->save();

        $events = $sink->get_events();
        $sink->clear();

        $params = array('appraisalid' => $appraisal->id, 'action' => 'stageedit', 'id' => $stage->id);
        $oldurl = new moodle_url('/totara/appraisal/stage.php', $params);
        $olddata = array(SITEID, 'appraisal', 'update stage', $oldurl, 'General Settings: Appraisal ID=' . $appraisal->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);

        // Test the legacy data for the page_updated event.
        $page->name = 'Test Page (changed)';
        $page->save();

        $events = $sink->get_events();
        $sink->clear();

        $params = array('appraisalstageid' => $stage->id, 'id' => $page->id);
        $oldurl = new moodle_url('/totara/appraisal/ajax/page.php', $params);
        $olddata = array(SITEID, 'appraisal', 'update page', $oldurl, 'General Settings: Page ID=' . $page->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);
    }

    public function test_appraisal_stage_completed_event() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        $now = time();

        // Set up an appraisal, stages.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage1 = new appraisal_stage(0);
        $stage1->appraisalid = $appraisal->get()->id;
        $stage1->name = 'Test Stage 1';
        $stage1->timedue = $now + 1000; // Earlier.
        $stage1->save();

        $stage2 = new appraisal_stage(0);
        $stage2->appraisalid = $appraisal->get()->id;
        $stage2->name = 'Test Stage 2';
        $stage2->timedue = $now + 2000; // Later.
        $stage2->save();

        // Not last stage.
        $sink->clear();
        $stage1->complete_for_user(123); // Hey, random subjectid seems to work, cool!
        $events = $sink->get_events();

        $event = null;
        foreach ($events as $event) {
            if ($event->eventname == '\totara_appraisal\event\appraisal_stage_completed') {
                break;
            }
        }

        $this->assertNotEmpty($event);

        $expectedevent = \totara_appraisal\event\appraisal_stage_completed::create(
            array(
                'objectid' => $appraisal->get()->id,
                'context' => context_system::instance(),
                'relateduserid' => 123,
                'other' => array(
                    'stageid' => $stage1->get()->id,
                    'complete_for_user' => false,
                )
            )
        );
        $expectedevent->trigger();

        $expectedevent = $this->sync_timecreated($expectedevent, $event, $now);

        $this->assertEquals($expectedevent, $event);

        // Last stage.
        $sink->clear();
        $stage2->complete_for_user(234); // Hey, random subjectid seems to work, cool!
        $events = $sink->get_events();

        $event = null;
        foreach ($events as $event) {
            if ($event->eventname == '\totara_appraisal\event\appraisal_stage_completed') {
                break;
            }
        }

        $this->assertNotEmpty($event);

        $expectedevent = \totara_appraisal\event\appraisal_stage_completed::create(
            array(
                'objectid' => $appraisal->get()->id,
                'context' => context_system::instance(),
                'relateduserid' => 234,
                'other' => array(
                    'stageid' => $stage2->get()->id,
                    'complete_for_user' => true,
                )
            )
        );
        $expectedevent->trigger();

        $expectedevent = $this->sync_timecreated($expectedevent, $event, $now);

        $this->assertEquals($expectedevent, $event);
    }

    public function test_appraisal_role_stage_completed_event() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        $now = time();

        // Set up an appraisal.
        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage1', 'timedue' => $now + 1000, 'pages' => array(
                array('name' => 'Page1', 'questions' => array(
                    array('name' => 'Text1', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1
                    ))
                ))
            )),
            array('name' => 'Stage2', 'timedue' => $now + 2000, 'pages' => array(
                array('name' => 'Page2', 'questions' => array(
                    array('name' => 'Text2', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1
                    ))
                ))
            )),
        ));
        list($appraisal, $users) = $this->prepare_appraisal_with_users($def);

        /** @var $appraisal appraisal */
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $learner = $users[0];
        $manager = get_admin();
        $managerroleassignment =
            appraisal_role_assignment::get_role($appraisal->get()->id, $learner->id, $manager->id, appraisal::ROLE_MANAGER);

        $map = $this->map($appraisal);
        $stage1 = new appraisal_stage($map['stages']['Stage1']);

        // Not last stage.
        $sink->clear();
        /** @var $managerroleassignment appraisal_role_assignment */
        $stage1->complete_for_role($managerroleassignment);
        $events = $sink->get_events();

        $event = null;
        foreach ($events as $event) {
            if ($event->eventname == '\totara_appraisal\event\appraisal_role_stage_completed') {
                break;
            }
        }

        $this->assertNotEmpty($event);

        $expectedevent = \totara_appraisal\event\appraisal_role_stage_completed::create(
            array(
                'objectid' => $appraisal->get()->id,
                'context' => context_system::instance(),
                'relateduserid' => $learner->id,
                'other' => array(
                    'stageid' => $stage1->get()->id,
                    'role' => appraisal::ROLE_MANAGER,
                )
            )
        );
        $expectedevent->trigger();

        $expectedevent = $this->sync_timecreated($expectedevent, $event, $now);

        $this->assertEquals($expectedevent, $event);
    }

    public function test_appraisal_role_page_saved_event() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        $now = time();

        // Set up an appraisal.
        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage1', 'timedue' => $now + 1000, 'pages' => array(
                array('name' => 'Page1', 'questions' => array(
                    array('name' => 'Text1', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1
                    ))
                ))
            )),
            array('name' => 'Stage2', 'timedue' => $now + 2000, 'pages' => array(
                array('name' => 'Page2', 'questions' => array(
                    array('name' => 'Text2', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1
                    ))
                ))
            )),
        ));
        list($appraisal, $users) = $this->prepare_appraisal_with_users($def);

        /** @var $appraisal appraisal */
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $learner = $users[0];
        $manager = get_admin();
        $managerroleassignment =
            appraisal_role_assignment::get_role($appraisal->get()->id, $learner->id, $manager->id, appraisal::ROLE_MANAGER);

        $map = $this->map($appraisal);
        $page1 = new appraisal_page($map['pages']['Page1']);

        // Not last stage.
        $sink->clear();
        $answers = new stdClass();
        $answers->pageid = $page1->get()->id;
        $answers->submitaction = 'next';
        /** @var $managerroleassignment appraisal_role_assignment */
        $appraisal->save_answers($answers, $managerroleassignment);
        $events = $sink->get_events();

        $event = null;
        foreach ($events as $event) {
            if ($event->eventname == '\totara_appraisal\event\appraisal_role_page_completed') {
                break;
            }
        }

        $this->assertNotEmpty($event);

        $expectedevent = \totara_appraisal\event\appraisal_role_page_saved::create(
            array(
                'objectid' => $appraisal->get()->id,
                'context' => context_system::instance(),
                'relateduserid' => $learner->id,
                'other' => array(
                    'pageid' => $page1->get()->id,
                    'role' => appraisal::ROLE_MANAGER,
                )
            )
        );
        $expectedevent->trigger();

        $expectedevent = $this->sync_timecreated($expectedevent, $event, $now);

        $this->assertEquals($expectedevent, $event);
    }

    /**
     * When asserting equality of two events, we have to account for the fact that their timecreated field could differ if the
     * switch to the next second happens between creating the two events (happened in CI run). Make tests more solid by:
     *  - expecting timecreated may differ in one direction when asserting it
     *  - syncing timecreated field in expected data so tests can go on comparing the whole event
     *
     * @param base $expectedevent
     * @param base $event
     * @param int $min_timecreated
     * @return base
     */
    private function sync_timecreated(base $expectedevent, base $event, int $min_timecreated): base {
        $this->assertGreaterThanOrEqual($min_timecreated, $event->timecreated);
        $this->assertGreaterThanOrEqual($event->timecreated, $expectedevent->timecreated);

        // Use reflection to overwrite protected property.
        $timesynced_event_data = array_merge($expectedevent->get_data(), ['timecreated' => $event->timecreated]);
        $reflection = new ReflectionClass($expectedevent);
        $data_prop = $reflection->getProperty('data');
        $data_prop->setAccessible(true);
        $data_prop->setValue($expectedevent, $timesynced_event_data);

        return $expectedevent;
    }
}
