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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_hierarchy
 */
defined('MOODLE_INTERNAL') || die();

use hierarchy_competency\userdata\competency_progress;

use totara_userdata\userdata\target_user;


/**
 * Unit tests for totara/hierarchy/prefix/competency/classes/userdata/competency_progress.php.
 *
 * @group totara_userdata
 */
class totara_hierarchy_userdata_competency_progress_testcase extends advanced_testcase {
    /**
     * @var string competency name.
     */
    private $competencyname = "my test competency#1";

    /**
     * @var string competency type.
     */
    private $competencytype = null;


    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass() {
        global $CFG;

        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/evidenceitem/type/abstract.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/evidenceitem/type/coursecompletion.php');
    }


    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        parent::setUp();
        // This is done here because COMPETENCY_EVIDENCE_TYPE_COURSE_COMPLETION is only defined when abstract.php
        // is included and that is only done in setUpBeforeClass(). Initializing $competencytype at the declaration
        // point makes the test will fail when it runs directly on the command line.
        $this->competencytype = COMPETENCY_EVIDENCE_TYPE_COURSE_COMPLETION;
    }

    protected function tearDown() {
        $this->competencytype = null;

        parent::tearDown();
    }

    /**
     * Generates test competencies, normal users and the user to be purged.
     *
     * @param int $noofusers no of "normal" learners to generate. These are the
     *        learners that are NOT going to be "purged".
     *
     * @return totara_userdata\userdata\target_user the user to be purged.
     */
    private function generate($noofusers) {
        $generator = $this->getDataGenerator();
        $hierarchies = $generator->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchies->create_comp_frame(['scale' => 1]);

        $frameworkid = ['frameworkid' => $framework->id, 'shortname'=>$this->competencyname];
        $competency = $hierarchies->create_comp($frameworkid);

        $competencyevidence = [
            'competencyid' => $competency->id,
            'itemtype' => COMPETENCY_EVIDENCE_TYPE_ACTIVITY_COMPLETION,
            'iteminstance' => 1,
            'usermodified' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
            'linktype' => 1
        ];

        $evidencetype = new competency_evidence_type_coursecompletion($competencyevidence, false);
        $evidencetypeid = $evidencetype->insert();

        for ($i = 0; $i < $noofusers; $i++) {
            $user  = $generator->create_user();
            $values = [
                'userid' => $user->id,
                'competencyid' => $competency->id,
                'itemid' => $evidencetypeid,
                'status' => 1,
                'proficiencymeasured' => 1,
                'timecreated' => time()
            ];

            $evidence = new comp_criteria_record($values, false);
            $evidence->save();
        }

        // The purged user's competency criteria is different.
        $competencyevidence['itemtype'] = $this->competencytype;
        $evidencetype = new competency_evidence_type_coursecompletion($competencyevidence, false);
        $evidencetypeid = $evidencetype->insert();

        $purgeduser = $generator->create_user();
        $values = [
            'userid' => $purgeduser->id,
            'competencyid' => $competency->id,
            'itemid' => $evidencetypeid,
            'status' => 1,
            'proficiencymeasured' => 1,
            'timecreated' => time()
        ];
        $evidence = new comp_criteria_record($values, false);
        $evidence->save();

        return new target_user($purgeduser);
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(competency_progress::is_countable());
        $this->assertTrue(competency_progress::is_exportable());
        $this->assertTrue(competency_progress::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(competency_progress::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(competency_progress::is_purgeable(target_user::STATUS_DELETED));
    }


    /**
     * Test operations.
     */
    public function test_purge_count_export() {
        global $DB;

        $this->resetAfterTest();

        $noofusers = 10;
        $targetuser = $this->generate($noofusers);
        $context = context_system::instance();

        $count = competency_progress::execute_count($targetuser, $context);
        $this->assertSame(1, $count, "wrong count before purge");
        $this->assertSame($noofusers + 1, $DB->count_records("comp_criteria_record"), "wrong record count in comp_criteria_record table");

        $exported = competency_progress::execute_export($targetuser, $context);
        $this->assertCount(0, $exported->files, "wrong exported files count");
        $this->assertCount(1, $exported->data, "wrong exported data count");

        foreach ($exported->data as $data) {
            $this->assertSame($this->competencyname, $data['competency'], "wrong export competency name");
            $this->assertSame($this->competencytype, $data['criteria'], "wrong export competency criteria");
        }

        competency_progress::execute_purge($targetuser, $context);

        $count = competency_progress::execute_count($targetuser, $context);
        $this->assertSame(0, $count, "wrong count after purge");
        $this->assertSame($noofusers, $DB->count_records("comp_criteria_record"), "wrong record count in comp_criteria_record table");

        $filter = ['userid' => $targetuser->get_user_record()->id];
        $this->assertSame(0, $DB->count_records("comp_criteria_record", $filter), "purged user still exists in comp_criteria_record table");

        $exported = competency_progress::execute_export($targetuser, $context);
        $this->assertCount(0, $exported->files, "wrong exported file count");
        $this->assertCount(0, $exported->data, "wrong exported data count after purge");
    }
}
