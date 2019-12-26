<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_appraisal
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once(__DIR__ . '/appraisal_testcase.php');
require_once($CFG->dirroot.'/totara/question/tests/question_testcase.php');

/**
 * To run:
 * vendor/bin/phpunit --verbose totara_appraisal_rb_source_appraisal_detail_testcase
 *
 * @group totara_reportbuilder
 */
class totara_appraisal_rb_source_appraisal_detail_testcase extends appraisal_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * @var array Mapping for data type name to display class name
     */
    public $datatypetodisplay = array(
        'fileupload'        => 'appraisal_fileupload',
        'longtext'          => 'appraisal_longtext',
        'multichoicemulti'  => 'appraisal_multichoice_multi',
        'multichoicesingle' => 'appraisal_multichoice_single'
    );

    public function test_report() {
        global $DB, $SESSION;

        $this->resetAfterTest();
        $this->setAdminUser();

        $users = array();
        $users[] = $this->getDataGenerator()->create_user();

        list($appraisal) = $this->prepare_appraisal_with_users(array(), $users);

        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        /** @var appraisal $appraisal */
        $appraisal->activate();
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $users[0]->id, $users[0]->id, appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');

        $rid = $this->create_report('appraisal_detail', 'Test report');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'appraisal', 'name', null, null, null, 0);
        $this->add_column($report, 'rolelearner', 'answers', null, null, null, 0);

        // Mock report parameter.
        $_GET['appraisalid'] = $appraisal->get()->id;
        $report = reportbuilder::create($rid);
        unset($_GET['appraisalid']);

        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);

        $this->assertCount(1, $records);
        $record = reset($records);
        $this->assertSame($appraisal->get()->name, $record->appraisal_name);

        $record = (array)$record;
        $this->assertCount(3, $record);
        $this->assertArrayHasKey('id', $record);
        $this->assertArrayHasKey('appraisal_name', $record);

        $this->add_column($report, 'appraisal', 'timestarted', null, 'minimum', null, 0);

        $report = reportbuilder::create($rid);
        list($sql, $params, $cache) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        $this->assertFalse($report->src->cacheable);
        $this->enable_caching($report->_id);

        $report = reportbuilder::create($rid);
        list($sql, $params, $cache) = $report->build_query();
        $this->assertSame(array(), $cache);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
    }

    public function test_display_multichoice_name_cache() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $users = array();
        $users[] = $this->getDataGenerator()->create_user();

        // Create appraisal 1.
        $def = array('name' => 'Appraisal 1', 'stages' => array(
            array('name' => 'Stage 1', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page 1', 'questions' => array(
                    array('name' => 'Text 1', 'type' => 'text', 'roles' => array(appraisal::ROLE_LEARNER => 1))
                ))
            ))
        ));
        list($appraisal1) = $this->prepare_appraisal_with_users($def, $users);

        $stages = appraisal_stage::get_stages($appraisal1->id);
        $stage1 = reset($stages);
        $pages = appraisal_page::get_list($stage1->id);
        $page1 = reset($pages);

        // Add a multichoice single-answer question.
        $def = array('name' => 'MultiSingle 1', 'type' => 'multichoicesingle', 'roles' => array(appraisal::ROLE_LEARNER => 7));
        $storage = appraisal_question::build($def, $page1->id);
        $storage->answerfield = 'appraisalroleassignmentid';
        $storage->prefix = 'appraisal';
        $questman = new question_manager();
        $question = $questman->create_element($storage, 'multichoicemulti');
        $fromform = new stdClass();
        $fromform->listtype = array('list' => multichoice::DISPLAY_MENU);
        $fromform->selectchoices = 0;
        $fromform->saveoptions = 1;
        $fromform->saveoptionsname = 'Three options';
        $fromform->choice = array(
            array('option' => 'Option1', 'default' => 1),
            array('option' => 'Option2', 'default' => 0),
            array('option' => 'Option3', 'default' => 1)
        );
        $question->define_set($fromform);

        // Add a multichoice multi-answer question.
        $def = array('name' => 'MultiMulti 1', 'type' => 'multichoicemulti', 'roles' => array(appraisal::ROLE_LEARNER => 7));
        $storage = appraisal_question::build($def, $page1->id);
        $storage->answerfield = 'appraisalroleassignmentid';
        $storage->prefix = 'appraisal';
        $questman = new question_manager();
        $question = $questman->create_element($storage, 'multichoicemulti');
        $fromform = new stdClass();
        $fromform->listtype = array('list' => multichoice::DISPLAY_MENU);
        $fromform->selectchoices = 0;
        $fromform->saveoptions = 1;
        $fromform->saveoptionsname = 'Three options';
        $fromform->choice = array(
            array('option' => 'Option4', 'default' => 1),
            array('option' => 'Option5', 'default' => 0),
            array('option' => 'Option6', 'default' => 1)
        );
        $question->define_set($fromform);

        /* @var appraisal $appraisal1 */
        list($errors, $warnings) = $appraisal1->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        // Activate the appraisal.
        $appraisal1->activate();

        // Create report 1.
        $rid = $this->create_report('appraisal_detail', 'Test report 1');

        $config = (new rb_config())->set_nocache(true);
        $report1 = reportbuilder::create($rid, $config);
        $this->add_column($report1, 'appraisal', 'name', null, null, null, 0);
        $this->add_column($report1, 'rolelearner', 'answers', null, null, null, 0);

        // Create appraisal 2.
        $def = array('name' => 'Appraisal 2', 'stages' => array(
            array('name' => 'Stage 2', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page 2', 'questions' => array(
                    array('name' => 'Text 2', 'type' => 'text', 'roles' => array(appraisal::ROLE_LEARNER => 1))
                ))
            ))
        ));
        list($appraisal2) = $this->prepare_appraisal_with_users($def, $users);

        $stages = appraisal_stage::get_stages($appraisal2->id);
        $stage2 = reset($stages);
        $pages = appraisal_page::get_list($stage2->id);
        $page2 = reset($pages);

        // Add a multichoice single-answer question.
        $def = array('name' => 'MultiSingle 2', 'type' => 'multichoicesingle', 'roles' => array(appraisal::ROLE_LEARNER => 7));
        $storage = appraisal_question::build($def, $page2->id);
        $storage->answerfield = 'appraisalroleassignmentid';
        $storage->prefix = 'appraisal';
        $questman = new question_manager();
        $question = $questman->create_element($storage, 'multichoicemulti');
        $fromform = new stdClass();
        $fromform->listtype = array('list' => multichoice::DISPLAY_MENU);
        $fromform->selectchoices = 0;
        $fromform->saveoptions = 1;
        $fromform->saveoptionsname = 'Three options';
        $fromform->choice = array(
            array('option' => 'OptionA', 'default' => 1),
            array('option' => 'OptionB', 'default' => 0),
            array('option' => 'OptionC', 'default' => 1)
        );
        $question->define_set($fromform);

        // Add a multichoice multi-answer question.
        $def = array('name' => 'MultiMulti 2', 'type' => 'multichoicemulti', 'roles' => array(appraisal::ROLE_LEARNER => 7));
        $storage = appraisal_question::build($def, $page2->id);
        $storage->answerfield = 'appraisalroleassignmentid';
        $storage->prefix = 'appraisal';
        $questman = new question_manager();
        $question = $questman->create_element($storage, 'multichoicemulti');
        $fromform = new stdClass();
        $fromform->listtype = array('list' => multichoice::DISPLAY_MENU);
        $fromform->selectchoices = 0;
        $fromform->saveoptions = 1;
        $fromform->saveoptionsname = 'Three options';
        $fromform->choice = array(
            array('option' => 'OptionD', 'default' => 1),
            array('option' => 'OptionE', 'default' => 0),
            array('option' => 'OptionF', 'default' => 1)
        );
        $question->define_set($fromform);

        /* @var appraisal $appraisal2 */
        list($errors, $warnings) = $appraisal2->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        // Activate the appraisal.
        $appraisal2->activate();

        // Create report 2.
        $rid = $this->create_report('appraisal_detail', 'Test report 2');

        $config = (new rb_config())->set_nocache(true);
        $report2 = reportbuilder::create($rid, $config);
        $this->add_column($report2, 'appraisal', 'name', null, null, null, 0);
        $this->add_column($report2, 'rolelearner', 'answers', null, null, null, 0);

        // Start the testing.
        $allscalevalues = $DB->get_records('appraisal_scale_value');
        $relevantscalesql = "SELECT id, param1
                               FROM {appraisal_quest_field}
                              WHERE appraisalstagepageid = :pageid
                                AND datatype in ('multichoicesingle', 'multichoicemulti')";
        $allquestionfields = $DB->get_records('appraisal_quest_field');

        // Check appraisal/report 2.

        // Mock report parameter.
        $_GET['appraisalid'] = $appraisal2->get()->id;
        $report2 = reportbuilder::create($rid);
        unset($_GET['appraisalid']);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $params = array('pageid' => $page2->id);
        $relevantscales = $DB->get_records_sql($relevantscalesql, $params);
        $checkcount = 0;
        foreach ($allscalevalues as $scalevalue) {
            $datatype = false;
            foreach ($allquestionfields as $questionfield) {
                if ($questionfield->param1 == $scalevalue->appraisalscaleid && array_key_exists($questionfield->id, $relevantscales)) {
                    $datatype = $questionfield->datatype;
                    break;
                }
            }
            if ($datatype) {
                $displayclass = '\totara_appraisal\rb\display\\' . $this->datatypetodisplay[$datatype];
                $display = $displayclass::display($scalevalue->id, $format, $row, $column, $report2);
                $this->assertEquals($scalevalue->name, $display);
                $checkcount++;
            } else {
                try {
                    $unfoundname = \totara_appraisal\rb\display\appraisal_multichoice_single::display($scalevalue->id, $format, $row, $column, $report2);
                    $this->assertTrue(false, "Shouldn't reach this code, exception not triggered!");
                } catch (exception $e) {
                    $this->assertEquals(PHPUnit\Framework\Error\Notice::class, get_class($e), $e->getMessage());
                    $this->assertEquals('Undefined offset: ' . $scalevalue->id, $e->getMessage());
                }
                try {
                    $unfoundname = \totara_appraisal\rb\display\appraisal_multichoice_multi::display($scalevalue->id, $format, $row, $column, $report2);
                    $this->assertTrue(false, "Shouldn't reach this code, exception not triggered!");
                } catch (exception $e) {
                    $this->assertEquals(PHPUnit\Framework\Error\Notice::class, get_class($e), $e->getMessage());
                    $this->assertEquals('Undefined offset: ' . $scalevalue->id, $e->getMessage());
                }
            }
        }
        $this->assertEquals(6, $checkcount);

        // Check appraisal/report 1.

        // Mock report parameter.
        $_GET['appraisalid'] = $appraisal1->get()->id;
        $report1 = reportbuilder::create($rid);
        unset($_GET['appraisalid']);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $params = array('pageid' => $page1->id);
        $relevantscales = $DB->get_records_sql($relevantscalesql, $params);
        $checkcount = 0;
        foreach ($allscalevalues as $scalevalue) {
            $datatype = false;
            foreach ($allquestionfields as $questionfield) {
                if ($questionfield->param1 == $scalevalue->appraisalscaleid && array_key_exists($questionfield->id, $relevantscales)) {
                    $datatype = $questionfield->datatype;
                    break;
                }
            }
            if ($datatype) {
                $displayclass = '\totara_appraisal\rb\display\\' . $this->datatypetodisplay[$datatype];
                $display = $displayclass::display($scalevalue->id, $format, $row, $column, $report1);
                $this->assertEquals($scalevalue->name, $display);
                $checkcount++;
            } else {
                try {
                    $unfoundname = \totara_appraisal\rb\display\appraisal_multichoice_single::display($scalevalue->id, $format, $row, $column, $report1);
                    $this->assertTrue(false, "Shouldn't reach this code, exception not triggered!");
                } catch (exception $e) {
                    $this->assertEquals(PHPUnit\Framework\Error\Notice::class, get_class($e), $e->getMessage());
                    $this->assertEquals('Undefined offset: ' . $scalevalue->id, $e->getMessage());
                }
                try {
                    $unfoundname = \totara_appraisal\rb\display\appraisal_multichoice_multi::display($scalevalue->id, $format, $row, $column, $report1);
                    $this->assertTrue(false, "Shouldn't reach this code, exception not triggered!");
                } catch (exception $e) {
                    $this->assertEquals(PHPUnit\Framework\Error\Notice::class, get_class($e), $e->getMessage());
                    $this->assertEquals('Undefined offset: ' . $scalevalue->id, $e->getMessage());
                }
            }
        }
        $this->assertEquals(6, $checkcount);

        // Check that the first appraisal/report (#2) is still working, with multiple values in multichoicemulti.

        // Mock report parameter. We need to recreate the report object due to static vars.
        $_GET['appraisalid'] = $appraisal2->get()->id;
        $report2 = reportbuilder::create($rid);
        unset($_GET['appraisalid']);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $params = array('pageid' => $page2->id);
        $relevantscales = $DB->get_records_sql($relevantscalesql, $params);
        $checkcount = 0;
        $multinames = array();
        $multiids = array();
        foreach ($allscalevalues as $scalevalue) {
            $datatype = false;
            foreach ($allquestionfields as $questionfield) {
                if ($questionfield->param1 == $scalevalue->appraisalscaleid && array_key_exists($questionfield->id, $relevantscales)) {
                    $datatype = $questionfield->datatype;
                    break;
                }
            }
            if ($datatype == 'multichoicesingle') {
                $displayclass = '\totara_appraisal\rb\display\\' . $this->datatypetodisplay[$datatype];
                $display = $displayclass::display($scalevalue->id, $format, $row, $column, $report2);
                $this->assertEquals($scalevalue->name, $display);
                $checkcount++;
            } else if ($datatype == 'multichoicemulti') {
                $multinames[] = $scalevalue->name;
                $multiids[] = $scalevalue->id;
                $checkcount++;
            } else {
                try {
                    $unfoundname = \totara_appraisal\rb\display\appraisal_multichoice_single::display($scalevalue->id, $format, $row, $column, $report2);
                    $this->assertTrue(false, "Shouldn't reach this code, exception not triggered!");
                } catch (exception $e) {
                    $this->assertEquals(PHPUnit\Framework\Error\Notice::class, get_class($e), $e->getMessage());
                    $this->assertEquals('Undefined offset: ' . $scalevalue->id, $e->getMessage());
                }
                try {
                    $unfoundname = \totara_appraisal\rb\display\appraisal_multichoice_multi::display($scalevalue->id, $format, $row, $column, $report2);
                    $this->assertTrue(false, "Shouldn't reach this code, exception not triggered!");
                } catch (exception $e) {
                    $this->assertEquals(PHPUnit\Framework\Error\Notice::class, get_class($e), $e->getMessage());
                    $this->assertEquals('Undefined offset: ' . $scalevalue->id, $e->getMessage());
                }
            }
        }
        $this->assertCount(3, $multiids);
        $this->assertEquals(6, $checkcount);
        $display = \totara_appraisal\rb\display\appraisal_multichoice_multi::display(implode(',', $multiids), $format, $row, $column, $report2);
        $this->assertEquals(implode(', ', $multinames), $display);

        // Make sure that empty answers work ok.
        $display = \totara_appraisal\rb\display\appraisal_multichoice_single::display('', $format, $row, $column, $report2);
        $this->assertEmpty($display);

        $display = \totara_appraisal\rb\display\appraisal_multichoice_multi::display('', $format, $row, $column, $report2);
        $this->assertEmpty($display);
    }
}
