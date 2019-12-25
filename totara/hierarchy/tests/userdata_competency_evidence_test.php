<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package totara_hierarchy
 */

use hierarchy_competency\userdata\competency_evidence;
use totara_hierarchy\task\update_competencies_task;
use totara_userdata\userdata\target_user;

/**
 * Tests the {@see competency_evidence} class
 *
 * @group totara_userdata
 */
class totara_hierarchy_userdata_competency_evidence_testcase extends advanced_testcase {

    /**
     * Sets up the data for the tests.
     */
    private function get_setup_data() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/evidence/lib.php');
        $this->resetAfterTest();
        $data = new class(){
            /** @var target_user */
            public $activeuser, $deleteduser;
            /** @var array */
            public $activeevidenceids, $deletedevidenceids;
            /** @var context_system */
            public $systemcontext;
        };
        $data->systemcontext = context_system::instance();
        $activeuser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $data->activeuser = new target_user($activeuser);
        $data->deleteduser = new target_user($deleteduser);

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $framework = $hierarchygenerator->create_comp_frame(['scale' => 1]);
        $compentencydata = $hierarchygenerator->create_comp(['frameworkid' => $framework->id]);
        $competency = hierarchy::load_hierarchy('competency');

        $evidencedata = new stdClass();
        $data->activeevidenceids[] = hierarchy_add_competency_evidence(
            $compentencydata->id,
            $data->activeuser->id,
            $DB->get_field('comp_scale_values', 'id', ['proficient' => 0, 'scaleid' => 1], IGNORE_MULTIPLE),
            $competency,
            $evidencedata
        );
        $data->deletedevidenceids[] = hierarchy_add_competency_evidence(
            $compentencydata->id,
            $data->deleteduser->id,
            $DB->get_field('comp_scale_values', 'id', ['proficient' => 0, 'scaleid' => 1], IGNORE_MULTIPLE),
            $competency,
            $evidencedata
        );

        ob_start();
        (new update_competencies_task())->execute();
        ob_end_clean();

        hierarchy_add_competency_evidence(
            $compentencydata->id,
            $data->activeuser->id,
            $DB->get_field('comp_scale_values', 'id', ['proficient' => 1, 'scaleid' => 1]),
            $competency,
            $evidencedata
        );
        hierarchy_add_competency_evidence(
            $compentencydata->id,
            $data->deleteduser->id,
            $DB->get_field('comp_scale_values', 'id', ['proficient' => 1, 'scaleid' => 1]),
            $competency,
            $evidencedata
        );

        ob_start();
        (new update_competencies_task())->execute();
        ob_end_clean();

        $activeusercount = competency_evidence::execute_count($data->activeuser, $data->systemcontext);
        $this->assertEquals(1, $activeusercount);

        $deletedusercount = competency_evidence::execute_count($data->deleteduser, $data->systemcontext);
        $this->assertEquals(1, $deletedusercount);

        return $data;
    }

    /**
     * Tests the purging removes the user data from the database
     */
    public function test_purge_removes_database_entries() {
        global $DB;
        $data = $this->get_setup_data();

        $this->assertEquals(
            competency_evidence::RESULT_STATUS_SUCCESS,
            competency_evidence::execute_purge($data->activeuser, $data->systemcontext)
        );

        $this->assertFalse($DB->record_exists('comp_record_history', ['userid' => $data->activeuser->id,]));
        $this->assertFalse($DB->record_exists('comp_record', ['userid' => $data->activeuser->id]));
        $this->assertFalse($DB->record_exists('block_totara_stats', [
            'userid' => $data->activeuser->id,
            'eventtype' => STATS_EVENT_COMP_ACHIEVED
        ]));
    }

    /**
     * Makes sure that purging one user does not effect another user.
     */
    public function test_purge_doesnt_remove_other_data() {
        global $DB;
        $data = $this->get_setup_data();

        $recordcountbefore = $DB->count_records('comp_record', ['userid' => $data->deleteduser->id]);
        $historycountbefore = $DB->count_records('comp_record_history', ['userid' => $data->deleteduser->id]);
        $statscountbefore = $DB->count_records('block_totara_stats', ['userid' => $data->deleteduser->id, 'eventtype' => STATS_EVENT_COMP_ACHIEVED]);

        $result = competency_evidence::execute_purge($data->activeuser, $data->systemcontext);
        $this->assertEquals(competency_evidence::RESULT_STATUS_SUCCESS, $result);

        $recordcountafter = $DB->count_records('comp_record', ['userid' => $data->deleteduser->id]);
        $this->assertEquals($recordcountbefore , $recordcountafter);

        $historycountafter = $DB->count_records('comp_record_history', ['userid' => $data->deleteduser->id]);
        $this->assertEquals($historycountbefore , $historycountafter);

        $statscountafter = $DB->count_records('block_totara_stats', ['userid' => $data->deleteduser->id, 'eventtype' => STATS_EVENT_COMP_ACHIEVED]);
        $this->assertEquals($statscountbefore, $statscountafter);
    }

    /**
     * Checks that after a purge the count is zero
     */
    public function test_purge_makes_count_zero() {
        $data = $this->get_setup_data();

        $currentcount = competency_evidence::execute_count($data->activeuser, $data->systemcontext);
        $this->assertGreaterThan(0, $currentcount);

        $result = competency_evidence::execute_purge($data->activeuser, $data->systemcontext);
        $this->assertEquals(competency_evidence::RESULT_STATUS_SUCCESS, $result);

        $currentcount = competency_evidence::execute_count($data->activeuser, $data->systemcontext);
        $this->assertEquals(0, $currentcount);
    }

    /**
     * Checks that the count is correct for deleted and active users
     */
    public function test_count_gets_expected_amount() {
        $data = $this->get_setup_data();

        $currentcount = competency_evidence::execute_count($data->activeuser, $data->systemcontext);
        $this->assertEquals(count($data->activeevidenceids), $currentcount);

        $currentcount = competency_evidence::execute_count($data->deleteduser, $data->systemcontext);
        $this->assertEquals(count($data->deletedevidenceids), $currentcount);
    }

    /**
     * Makes sure that the exported data matches the data in the database
     */
    public function test_export_matches_database_entries() {
        global $DB;
        $data = $this->get_setup_data();

        $export = competency_evidence::execute_export($data->activeuser, $data->systemcontext);
        $datawithouthistory = array_map(function ($record) {
            $newrecord = clone($record);
            unset($newrecord->history);
            return $newrecord;
        }, $export->data);
        $this->assertEquals($DB->get_records('comp_record', ['userid' => $data->activeuser->id]), $datawithouthistory);

        foreach ($export->data as $record) {
            $history = $DB->get_records('comp_record_history', [
                'userid' => $data->activeuser->id,
                'competencyid' => $record->competencyid
            ]);
            $this->assertEquals($history, $record->history);
        }
    }
}