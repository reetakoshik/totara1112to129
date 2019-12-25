<?php // $Id$
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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder
 *
 * Unit tests for totara/reportbuilder/lib.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_base_content.php');
require_once($CFG->dirroot . '/totara/core/lib/scheduler.php');
require_once($CFG->dirroot . '/totara/reportbuilder/email_setting_schedule.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_lib_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;
    /** @var $rb reportbuilder */
    public $rb, $user, $shortname, $filter1, $filter4, $column1, $column4, $savedsearch;

    protected function tearDown() {
        $this->rb = null;
        $this->user = null;
        $this->shortname = null;
        $this->filter1 = null;
        $this->filter4 = null;
        $this->column1 = null;
        $this->column4 = null;
        $this->savedsearch = null;

        parent::tearDown();
    }

    protected function setUp() {
        global $DB;
        parent::setup();
        $this->setAdminUser();

        $user = get_admin();
        $this->user = $user;

        //create all the dummy data to put into the phpunit database tables
        $report = new stdclass();
        $report->fullname = 'Test Report';
        $report->shortname = 'report_test';
        $report->source = 'competency_evidence';
        $report->hidden = 0;
        $report->accessmode = 0;
        $report->contentmode = 0;
        $report->description = '';
        $report->recordsperpage = 40;
        $report->defaultsortcolumn = 'user_fullname';
        $report->defaultsortorder = 4;
        $report->embedded = 0;
        $report->id = $DB->insert_record('report_builder', $report);

        $rbcol1 = new stdClass();
        $rbcol1->reportid = $report->id;
        $rbcol1->type = 'user';
        $rbcol1->value = 'namelink';
        $rbcol1->heading = 'Participant';
        $rbcol1->sortorder = 1;
        $rbcol1->hidden = 0;
        $rbcol1->customheading = 1;
        $rbcol1->id = $DB->insert_record('report_builder_columns', $rbcol1);
        $this->column1 = $rbcol1;

        $rbcol2 = new stdClass();
        $rbcol2->reportid = $report->id;
        $rbcol2->type = 'competency';
        $rbcol2->value = 'competencylink';
        $rbcol2->heading = 'Competency';
        $rbcol2->sortorder = 2;
        $rbcol2->hidden = 0;
        $rbcol2->customheading = 0;
        $rbcol2->id = $DB->insert_record('report_builder_columns', $rbcol2);

        $rbcol3 = new stdClass();
        $rbcol3->reportid = $report->id;
        $rbcol3->type = 'job_assignment';
        $rbcol3->value = 'allorganisationnames';
        $rbcol3->heading = 'Office';
        $rbcol3->sortorder = 3;
        $rbcol3->hidden = 0;
        $rbcol3->customheading = 0;
        $rbcol3->id = $DB->insert_record('report_builder_columns', $rbcol3);

        $rbcol4 = new stdClass();
        $rbcol4->reportid = $report->id;
        $rbcol4->type = 'competency_evidence';
        $rbcol4->value = 'organisation';
        $rbcol4->heading = 'Completion Office';
        $rbcol4->sortorder = 4;
        $rbcol4->hidden = 0;
        $rbcol4->customheading = 0;
        $rbcol4->id = $DB->insert_record('report_builder_columns', $rbcol4);
        $this->column4 = $rbcol4;

        $rbcol5 = new stdClass();
        $rbcol5->reportid = $report->id;
        $rbcol5->type = 'job_assignment';
        $rbcol5->value = 'allpositionnames';
        $rbcol5->heading = 'Position';
        $rbcol5->sortorder = 5;
        $rbcol5->hidden = 0;
        $rbcol5->customheading = 0;
        $rbcol5->id = $DB->insert_record('report_builder_columns', $rbcol5);

        $rbcol6 = new stdClass();
        $rbcol6->reportid = $report->id;
        $rbcol6->type = 'competency_evidence';
        $rbcol6->value = 'position';
        $rbcol6->heading ='Completion Position';
        $rbcol6->sortorder = 6;
        $rbcol6->hidden = 0;
        $rbcol6->customheading = 0;
        $rbcol6->id = $DB->insert_record('report_builder_columns', $rbcol6);

        $rbcol7 = new stdClass();
        $rbcol7->reportid = $report->id;
        $rbcol7->type = 'competency_evidence';
        $rbcol7->value = 'proficiency';
        $rbcol7->heading = 'Proficiency';
        $rbcol7->sortorder = 7;
        $rbcol7->hidden = 0;
        $rbcol7->customheading = 0;
        $rbcol7->id = $DB->insert_record('report_builder_columns', $rbcol7);

        $rbcol8 = new stdClass();
        $rbcol8->reportid = $report->id;
        $rbcol8->type = 'competency_evidence';
        $rbcol8->value = 'timemodified';
        $rbcol8->heading = 'Time Modified';
        $rbcol8->sortorder = 8;
        $rbcol8->hidden = 0;
        $rbcol8->customheading = 0;
        $rbcol8->id = $DB->insert_record('report_builder_columns', $rbcol8);

        $rbfilter1 = new stdClass();
        $rbfilter1->reportid = $report->id;
        $rbfilter1->type = 'user';
        $rbfilter1->value = 'fullname';
        $rbfilter1->advanced = 0;
        $rbfilter1->sortorder = 1;
        $rbfilter1->id = $DB->insert_record('report_builder_filters', $rbfilter1);
        $this->filter1 = $rbfilter1;

        $rbfilter2 = new stdClass();
        $rbfilter2->reportid = $report->id;
        $rbfilter2->type = 'job_assignment';
        $rbfilter2->value = 'allorganisations';
        $rbfilter2->advanced = 0;
        $rbfilter2->sortorder = 2;
        $rbfilter2->id = $DB->insert_record('report_builder_filters', $rbfilter2);

        $rbfilter3 = new stdClass();
        $rbfilter3->reportid = $report->id;
        $rbfilter3->type = 'competency_evidence';
        $rbfilter3->value = 'organisationid';
        $rbfilter3->advanced = 0;
        $rbfilter3->sortorder = 3;
        $rbfilter3->id = $DB->insert_record('report_builder_filters', $rbfilter3);

        $rbfilter4 = new stdClass();
        $rbfilter4->reportid = $report->id;
        $rbfilter4->type = 'job_assignment';
        $rbfilter4->value = 'allpositions';
        $rbfilter4->advanced = 0;
        $rbfilter4->sortorder = 4;
        $rbfilter4->id = $DB->insert_record('report_builder_filters', $rbfilter4);
        $this->filter4 = $rbfilter4;

        $rbfilter5 = new stdClass();
        $rbfilter5->reportid = $report->id;
        $rbfilter5->type = 'competency_evidence';
        $rbfilter5->value = 'positionid';
        $rbfilter5->advanced = 0;
        $rbfilter5->sortorder = 5;
        $rbfilter5->id = $DB->insert_record('report_builder_filters', $rbfilter5);

        $rbfilter6 = new stdClass();
        $rbfilter6->reportid = $report->id;
        $rbfilter6->type = 'competency';
        $rbfilter6->value = 'fullname';
        $rbfilter6->advanced = 0;
        $rbfilter6->sortorder = 6;
        $rbfilter6->id = $DB->insert_record('report_builder_filters', $rbfilter6);

        $rbfilter7 = new stdClass();
        $rbfilter7->reportid = $report->id;
        $rbfilter7->type = 'competency_evidence';
        $rbfilter7->value = 'timemodified';
        $rbfilter7->advanced = 0;
        $rbfilter7->sortorder = 7;
        $rbfilter7->id = $DB->insert_record('report_builder_filters', $rbfilter7);

        $rbfilter8 = new stdClass();
        $rbfilter8->reportid = $report->id;
        $rbfilter8->type = 'competency_evidence';
        $rbfilter8->value = 'proficiencyid';
        $rbfilter8->advanced = 0;
        $rbfilter8->sortorder = 8;
        $rbfilter8->id = $DB->insert_record('report_builder_filters', $rbfilter8);

        $rbsettings1 = new stdClass();
        $rbsettings1->reportid = $report->id;
        $rbsettings1->type = 'role_access';
        $rbsettings1->name = 'activeroles';
        $rbsettings1->value = '1|2';
        $rbsettings1->id = $DB->insert_record('report_builder_settings', $rbsettings1);

        $rbsettings2 = new stdClass();
        $rbsettings2->reportid = $report->id;
        $rbsettings2->type = 'role_access';
        $rbsettings2->name = 'enable';
        $rbsettings2->value = 1;
        $rbsettings2->id = $DB->insert_record('report_builder_settings', $rbsettings2);

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->id;
        $rbsaved->userid = $user->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = 'a:1:{s:13:"user-fullname";a:1:{i:0;a:2:{s:8:"operator";i:0;s:5:"value";s:1:"a";}}}';
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);
        $this->savedsearch = $rbsaved;

        $roleassignment = new stdClass();
        $roleassignment->roleid = 1;
        $roleassignment->contextid = 1;
        $roleassignment->userid = 2;
        $roleassignment->hidden = 0;
        $roleassignment->timestart = 0;
        $roleassignment->timeend = 0;
        $roleassignment->timemodified = 0;
        $roleassignment->modifierid = $user->id;
        $roleassignment->enrol = 'manual';
        $roleassignment->sortorder = 0;
        $roleassignment->id = $DB->insert_record('role_assignments', $roleassignment);

        $userinfofield = new stdClass();
        $userinfofield->shortname = 'datejoined';
        $userinfofield->name = 'Date Joined';
        $userinfofield->datatype = 'text';
        $userinfofield->description = '';
        $userinfofield->categoryid = 1;
        $userinfofield->sortorder = 1;
        $userinfofield->required = 0;
        $userinfofield->locked = 0;
        $userinfofield->visible = 1;
        $userinfofield->forceunique = 0;
        $userinfofield->signup = 0;
        $userinfofield->defaultdata = '';
        $userinfofield->param1 = 30;
        $userinfofield->param2 = 2048;
        $userinfofield->param3 = 0;
        $userinfofield->param4 = '';
        $userinfofield->param5 = '';
        $userinfofield->id = $DB->insert_record('user_info_field', $userinfofield);

        $postypeinfofield = new stdClass();
        $postypeinfofield->shortname = 'checktest';
        $postypeinfofield->typeid = 1;
        $postypeinfofield->datatype = 'checkbox';
        $postypeinfofield->description = '';
        $postypeinfofield->sortorder = 1;
        $postypeinfofield->hidden = 0;
        $postypeinfofield->locked = 0;
        $postypeinfofield->required = 0;
        $postypeinfofield->forceunique = 0;
        $postypeinfofield->defaultdata = 0;
        $postypeinfofield->param1 = null;
        $postypeinfofield->param2 = null;
        $postypeinfofield->param3 = null;
        $postypeinfofield->param4 = null;
        $postypeinfofield->param5 = null;
        $postypeinfofield->fullname = 'Checkbox test';
        $postypeinfofield->id = $DB->insert_record('pos_type_info_field', $postypeinfofield);

        $orgtypeinfofield = new stdClass();
        $orgtypeinfofield->shortname = 'checktest';
        $orgtypeinfofield->typeid = 1;
        $orgtypeinfofield->datatype = 'checkbox';
        $orgtypeinfofield->description = '';
        $orgtypeinfofield->sortorder = 1;
        $orgtypeinfofield->hidden = 0;
        $orgtypeinfofield->locked = 0;
        $orgtypeinfofield->required = 0;
        $orgtypeinfofield->forceunique = 0;
        $orgtypeinfofield->defaultdata = 0;
        $orgtypeinfofield->param1 = null;
        $orgtypeinfofield->param2 = null;
        $orgtypeinfofield->param3 = null;
        $orgtypeinfofield->param4 = null;
        $orgtypeinfofield->param5 = null;
        $orgtypeinfofield->fullname = 'Checkbox test';
        $orgtypeinfofield->id = $DB->insert_record('org_type_info_field', $orgtypeinfofield);

        $comptypeinfofield = new stdClass();
        $comptypeinfofield->shortname = 'checktest';
        $comptypeinfofield->typeid = 1;
        $comptypeinfofield->datatype = 'checkbox';
        $comptypeinfofield->description = '';
        $comptypeinfofield->sortorder = 1;
        $comptypeinfofield->hidden = 0;
        $comptypeinfofield->locked = 0;
        $comptypeinfofield->required = 0;
        $comptypeinfofield->forceunique = 0;
        $comptypeinfofield->defaultdata = 0;
        $comptypeinfofield->param1 = null;
        $comptypeinfofield->param2 = null;
        $comptypeinfofield->param3 = null;
        $comptypeinfofield->param4 = null;
        $comptypeinfofield->param5 = null;
        $comptypeinfofield->fullname = 'Checkbox test';
        $comptypeinfofield->id = $DB->insert_record('comp_type_info_field', $comptypeinfofield);

        $comp1 = new stdClass();
        $comp1->fullname = 'Competency 1';
        $comp1->shortname =  'Comp 1';
        $comp1->description = 'Competency Description 1';
        $comp1->idnumber = 'C1';
        $comp1->frameworkid = 1;
        $comp1->path = '/1';
        $comp1->depthlevel = 1;
        $comp1->parentid = 0;
        $comp1->sortthread = '01';
        $comp1->visible = 1;
        $comp1->aggregationmethod = 1;
        $comp1->proficiencyexpected = 1;
        $comp1->evidencecount = 0;
        $comp1->timecreated = 1265963591;
        $comp1->timemodified = 1265963591;
        $comp1->usermodified = $user->id;
        $comp1->id = $DB->insert_record('comp', $comp1);

        $comp2 = new stdClass();
        $comp2->fullname = 'Competency 2';
        $comp2->shortname = 'Comp 2';
        $comp2->description = 'Competency Description 2';
        $comp2->idnumber = 'C2';
        $comp2->frameworkid = 1;
        $comp2->path = '/1/2';
        $comp2->depthlevel = 2;
        $comp2->parentid = 1;
        $comp2->sortthread = '01.01';
        $comp2->visible = 1;
        $comp2->aggregationmethod = 1;
        $comp2->proficiencyexpected = 1;
        $comp2->evidencecount = 0;
        $comp2->timecreated = 1265963591;
        $comp2->timemodified = 1265963591;
        $comp2->usermodified = $user->id;
        $comp2->id = $DB->insert_record('comp', $comp2);

        $comp3 = new stdClass();
        $comp3->fullname = 'F2 Competency 1';
        $comp3->shortname = 'F2 Comp 1';
        $comp3->description = 'F2 Competency Description 1';
        $comp3->idnumber = 'F2 C1';
        $comp3->frameworkid = 2;
        $comp3->path = '/3';
        $comp3->depthlevel = 1;
        $comp3->parentid = 0;
        $comp3->sortthread = '01';
        $comp3->visible = 1;
        $comp3->aggregationmethod = 1;
        $comp3->proficiencyexpected = 1;
        $comp3->evidencecount = 0;
        $comp3->timecreated = 1265963591;
        $comp3->timemodified = 1265963591;
        $comp3->usermodified = $user->id;
        $comp3->id = $DB->insert_record('comp', $comp3);

        $comp4 = new stdClass();
        $comp4->fullname = 'Competency 3';
        $comp4->shortname = 'Comp 3';
        $comp4->description = 'Competency Description 3';
        $comp4->idnumber = 'C3';
        $comp4->frameworkid = 1;
        $comp4->path = '/1/4';
        $comp4->depthlevel = 2;
        $comp4->parentid = 1;
        $comp4->sortthread = '01.02';
        $comp4->visible = 1;
        $comp4->aggregationmethod = 1;
        $comp4->proficiencyexpected = 1;
        $comp4->evidencecount = 0;
        $comp4->timecreated = 1265963591;
        $comp4->timemodified = 1265963591;
        $comp4->usermodified = $user->id;
        $comp4->id = $DB->insert_record('comp', $comp4);

        $comp5 = new stdClass();
        $comp5->fullname = 'Competency 4';
        $comp5->shortname = 'Comp 4';
        $comp5->description = 'Competency Description 4';
        $comp5->idnumber = 'C4';
        $comp5->frameworkid = 1;
        $comp5->path = '/5';
        $comp5->depthlevel = 1;
        $comp5->parentid = 0;
        $comp5->sortthread = '02';
        $comp5->visible = 1;
        $comp5->aggregationmethod = 1;
        $comp5->proficiencyexpected = 1;
        $comp5->evidencecount = 0;
        $comp5->timecreated = 1265963591;
        $comp5->timemodified = 1265963591;
        $comp5->usermodified = $user->id;
        $comp5->id = $DB->insert_record('comp', $comp5);

        $org = new stdClass();
        $org->fullname = 'Distric Office';
        $org->shortname = 'DO';
        $org->description = '';
        $org->idnumber = '';
        $org->frameworkid = 1;
        $org->path = '/1';
        $org->depthlevel = 1;
        $org->parentid = 0;
        $org->sortthread = '01';
        $org->visible = 1;
        $org->timecreated = 0;
        $org->timemodified = 0;
        $org->usermodified = $user->id;
        $org->id = $DB->insert_record('org', $org);

        $pos = new stdClass();
        $pos->fullname = 'Data Analyst';
        $pos->shortname = 'Data Analyst';
        $pos->idnumber = '';
        $pos->description = '';
        $pos->frameworkid = 1;
        $pos->path = '/1';
        $pos->depthlevel = 1;
        $pos->parentid = 0;
        $pos->sortthread = '01';
        $pos->visible = 1;
        $pos->timevalidfrom = 0;
        $pos->timevalidto = 0;
        $pos->timecreated = 0;
        $pos->timemodified = 0;
        $pos->usermodified = $user->id;
        $pos->id = $DB->insert_record('pos', $pos);

        $compscalevalue1 = new stdClass();
        $compscalevalue1->name = 'Competent';
        $compscalevalue1->idnumber = '';
        $compscalevalue1->description = '';
        $compscalevalue1->scaleid = 1;
        $compscalevalue1->numericscore = '';
        $compscalevalue1->sortorder = 1;
        $compscalevalue1->timemodified = 0;
        $compscalevalue1->usermodified = $user->id;
        $compscalevalue1->proficient = 1;
        $compscalevalue1->id = $DB->insert_record('comp_scale_values', $compscalevalue1);

        $compscalevalue2 = new stdClass();
        $compscalevalue2->name = 'Partially Competent';
        $compscalevalue2->idnumber = '';
        $compscalevalue2->description = '';
        $compscalevalue2->scaleid = 1;
        $compscalevalue2->numericscore = '';
        $compscalevalue2->sortorder = 2;
        $compscalevalue2->timemodified = 0;
        $compscalevalue2->usermodified = $user->id;
        $compscalevalue2->proficient = 0;
        $compscalevalue2->id = $DB->insert_record('comp_scale_values', $compscalevalue2);

        $compscalevalue3 = new stdClass();
        $compscalevalue3->name = 'Not Competent';
        $compscalevalue3->idnumber = '';
        $compscalevalue3->description = '';
        $compscalevalue3->scaleid = 1;
        $compscalevalue3->numericscore = '';
        $compscalevalue3->sortorder = 3;
        $compscalevalue3->timemodified = 0;
        $compscalevalue3->usermodified = $user->id;
        $compscalevalue3->proficient = 0;
        $compscalevalue3->id = $DB->insert_record('comp_scale_values', $compscalevalue3);

        $comprecord = new stdClass();
        $comprecord->userid = $user->id;
        $comprecord->competencyid = 1;
        $comprecord->positionid = 1;
        $comprecord->organisationid = 1;
        $comprecord->assessorid = 1;
        $comprecord->assessorname = 'Assessor';
        $comprecord->assessmenttype = '';
        $comprecord->proficiency = 1;
        $comprecord->timeproficient = 1100775600;
        $comprecord->timecreated = 1100775600;
        $comprecord->timemodified = 1100775600;
        $comprecord->reaggregate = 0;
        $comprecord->manual = 1;
        $comprecord->id = $DB->insert_record('comp_record', $comprecord);

        \totara_job\job_assignment::create_default($user->id, array('organisationid' => 1, 'positionid' => 1));

        core_tag_tag::create_if_missing(core_tag_collection::get_default(), ['test'], true);

        $this->shortname = 'plan_competencies';

        // db version of report
        $this->rb = reportbuilder::create($report->id);
        $this->resetAfterTest(true);
    }

    public function test_reportbuilder_initialize_db_instance() {
        $this->resetAfterTest(true);

        $rb = reportbuilder::create($this->rb->_id);
        // should create report builder object with the correct properties
        $this->assertEquals('Test Report', $rb->fullname);
        $this->assertEquals('report_test', $rb->shortname);
        $this->assertEquals('competency_evidence', $rb->source);
        $this->assertEquals(0, $rb->hidden);
    }

    public function test_reportbuilder_initialize_embedded_instance() {
        $this->resetAfterTest(true);

        $rb = reportbuilder::create_embedded($this->shortname);
        // should create embedded report builder object with the correct properties
        $this->assertEquals('Record of Learning: Competencies', $rb->fullname);
        $this->assertEquals('plan_competencies', $rb->shortname);
        $this->assertEquals('dp_competency', $rb->source);
        $this->assertEquals(1, $rb->hidden);
    }

    public function test_reportbuilder_old_constructor() {
        $this->resetAfterTest(true);

        // Test generic report.
        $rb = new reportbuilder($this->rb->_id);
        $this->assertDebuggingCalled('From Totara 12, report constructor must not be called directly, use reportbuilder::create() instead.');
        $this->assertEquals('Test Report', $rb->fullname);
        $this->assertEquals('report_test', $rb->shortname);
        $this->assertEquals('competency_evidence', $rb->source);
        $this->assertEquals(0, $rb->hidden);

        // Test embedded report.
        try {
            $rb = new reportbuilder(null, $this->shortname);
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertDebuggingCalled('From Totara 12, report constructor must not be called directly, use reportbuilder::create() instead.');
        }
    }

    public function test_get_caching_problems() {
        set_config('enablereportcaching', 0);
        $rb = reportbuilder::create_embedded($this->shortname);
        $problems = $rb->get_caching_problems();
        $this->assertCount(1, $problems);
        $this->assertContains('Report caching is disabled.', $problems[0]);

        set_config('enablereportcaching', 1);
        $rb = reportbuilder::create_embedded($this->shortname);
        $problems = $rb->get_caching_problems();
        $this->assertCount(0, $problems);
    }

    function test_reportbuilder_restore_saved_search() {
        global $SESSION, $USER, $DB;
        $config = (new rb_config())->set_sid($this->savedsearch->id);
        $rb = reportbuilder::create($this->rb->_id, $config);

        // ensure that saved search belongs to current user
        $DB->set_field('report_builder_saved', 'userid', $USER->id, array('id' => $rb->_id));

        // should be able to restore a saved search
        $this->assertTrue((bool)$rb->restore_saved_search());
        // the correct SESSION var should now be set
        // the SESSION var should be set to the value specified by the saved search
        $this->assertEquals(array('user-fullname' => array(0 => array('operator' => 0, 'value' => 'a'))),
                $SESSION->reportbuilder[$rb->get_uniqueid()]);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_filters() {
        $rb = $this->rb;
        $filters = $rb->get_filters();

        // should return the current filters for this report
        $this->assertTrue((bool)is_array($filters));
        $this->assertEquals(8, count($filters));
        $this->assertEquals('user', current($filters)->type);
        $this->assertEquals('fullname', current($filters)->value);
        $this->assertEquals('0', current($filters)->advanced);
        $this->assertEquals('User\'s Fullname contains "content"',
                current($filters)->get_label(array('operator' => 0, 'value' => 'content')));
        $this->assertEquals('User\'s Fullname doesn\'t contain "nocontent"',
                current($filters)->get_label(array('operator' => 1, 'value' => 'nocontent')));
        $this->assertEquals('User\'s Fullname is equal to "fullname"',
                current($filters)->get_label(array('operator' => 2, 'value' => 'fullname')));
        $this->assertEquals('User\'s Fullname starts with "start"',
                current($filters)->get_label(array('operator' => 3, 'value' => 'start')));
        $this->assertEquals('User\'s Fullname ends with "end"',
                current($filters)->get_label(array('operator' => 4, 'value' => 'end')));
        $this->assertEquals('User\'s Fullname is empty',
                current($filters)->get_label(array('operator' => 5, 'value' => '')));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_columns() {
        global $DB;

        $rb = $this->rb;
        $columns = $rb->get_columns();
        // should return the current columns for this report
        $this->assertTrue((bool)is_array($columns));
        $this->assertEquals(8, count($columns));
        $this->assertEquals('user', current($columns)->type);
        $this->assertEquals('namelink', current($columns)->value);
        $this->assertEquals('Participant', current($columns)->heading);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_create_embedded_record() {
        global $DB;

        $newrb = reportbuilder::create_embedded($this->shortname);
        // should create a db record for the embedded report
        $this->assertTrue((bool)$record = $DB->get_records('report_builder', array('shortname' => $this->shortname)));
        // there should be db records in the columns table
        $this->assertTrue((bool)$DB->get_records('report_builder_columns', array('reportid' => $newrb->_id)));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_create_shortname() {
        $verylongstring = 'It is a long established fact that a reader will be distracted by the readable content of a page ' .
            'when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution ' .
            'of letters, as opposed to using \'Content here, content here\', making it look like readable English. ';

        $shortname1 = reportbuilder::create_shortname('name');
        $shortname2 = reportbuilder::create_shortname('My Report with special chars\'"%$*[]}~');
        $shortname3 = reportbuilder::create_shortname('Space here');
        $shortname4 = reportbuilder::create_shortname($verylongstring);

        // Should prepend 'report_' to name.
        $this->assertEquals('report_name', $shortname1);

        // Special chars should be stripped.
        $this->assertEquals('report_my_report_with_special_chars', $shortname2);

        // Spaces should be replaced with underscores and upper case moved to lower case.
        $this->assertEquals('report_space_here', $shortname3);

        // Confirm that the function trims the shortname to 255 symbols.
        $this->assertLessThanOrEqual(255, strlen($shortname4));

        // Confirm existing report short name which we will collide with.
        $this->assertEquals('report_test', $this->rb->shortname);

        $existingname = reportbuilder::create_shortname('test');

        // Should append random hash at the end of the string, thus should be different from what we have in the db.
        $this->assertNotEquals('report_test', $existingname);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_report_url() {
        global $CFG;
        $rb = $this->rb;
        // a normal report should return the report.php url
        $this->assertEquals('/totara/reportbuilder/report.php?id=' . $rb->_id, substr($rb->report_url(), strlen($CFG->wwwroot)));
        $rb2 = reportbuilder::create_embedded($this->shortname);
        // an embedded report should return the embedded url (this page)
        $this->assertEquals($CFG->wwwroot . '/totara/plan/record/competencies.php', $rb2->report_url());

        $this->resetAfterTest(true);
    }


    // not tested as difficult to do in a useful way
    // get_current_url() not tested
    // leaving get_current_admin_options() until after changes to capabilities
    function test_reportbuilder_get_current_params() {
        $userid = $this->user->id;
        $config = (new rb_config())->set_embeddata(array('userid' => $userid));
        $rb = reportbuilder::create_embedded($this->shortname, $config);
        $paramoption = new stdClass();
        $paramoption->name = 'userid';
        $paramoption->field = 'base.userid';
        $paramoption->joins = '';
        $paramoption->type = 'int';
        $param = new rb_param('userid',array($paramoption));
        $param->value = $userid;
        // should return the expected embedded param
        $this->assertEquals(array($param), $rb->get_current_params());

        $this->resetAfterTest(true);
    }


    // display_search() and get_sql_filter() not tested as they print output directly to screen
    function test_reportbuilder_is_capable() {
        global $USER, $DB;

        $rb = $this->rb;
        $reportid = $rb->_id;
        $userid = $this->user->id;

        // should return true if accessmode is zero
        $this->assertTrue((bool)reportbuilder::is_capable($reportid));
        $DB->set_field('report_builder', 'accessmode', REPORT_BUILDER_CONTENT_MODE_ANY, array('id' => $reportid));
        // should return true if accessmode is 1 and admin an allowed role
        $this->assertTrue((bool)reportbuilder::is_capable($reportid, $userid));
        // should return false if access mode is 1 and admin not an allowed role
        $DB->delete_records('report_builder_settings', array('reportid' => $reportid));
        $this->assertFalse((bool)reportbuilder::is_capable($reportid));
        $todb = new stdClass();
        $todb->reportid = $reportid;
        $todb->type = 'role_access';
        $todb->name = 'activeroles';
        $todb->value = 1;
        $DB->insert_record('report_builder_settings',$todb);
        $todb = new stdClass();
        $todb->reportid = $reportid;
        $todb->type = 'role_access';
        $todb->name = 'enable';
        $todb->value = '1';
        $DB->insert_record('report_builder_settings', $todb);
        // should return true if accessmode is 1 and admin is only allowed role
        $this->assertTrue((bool)reportbuilder::is_capable($reportid, $userid));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_param_restrictions() {
        $config = (new rb_config())->set_embeddata(array('userid' => $this->user->id));
        $rb = reportbuilder::create_embedded($this->shortname, $config);
        // should return the correct SQL fragment if a parameter restriction is set
        $restrictions = $rb->get_param_restrictions();
        $this->assertRegExp('(base.userid\s+=\s+:[a-z0-9]+)', $restrictions[0]);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_content_restrictions() {
        global $DB;

        $rb = $this->rb;
        $reportid = $rb->_id;

        // should return ( 1=1 ) if content mode = 0
        $restrictions = $rb->get_content_restrictions();
        $this->assertEquals('( 1=1 )', $restrictions[0]);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ANY, array('id' => $reportid));
        $rb = reportbuilder::create($reportid);
        // should return (1=0) if content mode = 1 but no restrictions set
        // using 1=0 instead of FALSE for MSSQL support
        $restrictions = $rb->get_content_restrictions();
        $this->assertEquals('(1=0)', $restrictions[0]);
        $todb = new stdClass();
        $todb->reportid = $reportid;
        $todb->type = 'date_content';
        $todb->name = 'enable';
        $todb->value = 1;
        $DB->insert_record('report_builder_settings', $todb);
        $todb->name = 'when';
        $todb->value = 'future';
        $DB->insert_record('report_builder_settings', $todb);
        $todb->type = 'user_content';
        $todb->name = 'enable';
        $todb->value = 1;
        $DB->insert_record('report_builder_settings', $todb);
        $todb->name = 'who';
        $todb->value = rb_user_content::USER_OWN;
        $DB->insert_record('report_builder_settings', $todb);
        $rb = reportbuilder::create($reportid);
        $restrictions = $rb->get_content_restrictions();
        // should return the appropriate SQL snippet to OR the restrictions if content mode = 1
        $this->assertRegExp('/\(\s\(auser\.id\s+=\s+:[a-z0-9_]+\)\s+OR\s+\(base\.timemodified\s+>\s+[0-9]+\s+AND\s+base\.timemodified\s+!=\s+0\s+\)\)/', $restrictions[0]);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL, array('id' => $reportid));
        $rb = reportbuilder::create($reportid);
        $restrictions = $rb->get_content_restrictions();
        // should return the appropriate SQL snippet to AND the restrictions if content mode = 2
        $this->assertRegExp('/\(\s\(auser\.id\s+=\s+:[a-z0-9_]+\)\s+AND\s+\(base\.timemodified\s+>\s+[0-9]+\s+AND\s+base\.timemodified\s+!=\s+0\s+\)\)/', $restrictions[0]);

        // Test we can actually display this report with these restrictions.
        $rb->display_table(true);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_restriction_descriptions() {
        global $DB;

        $rb = $this->rb;
        $reportid = $rb->_id;
        // should return empty array if content mode = 0
        $this->assertEquals(array(), $rb->get_restriction_descriptions('content'));
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ANY, array('id' => $reportid));
        $rb = reportbuilder::create($reportid);
        // should return an array with empty string if content mode = 1 but no restrictions set
        $this->assertEquals(array(''), $rb->get_restriction_descriptions('content'));
        $todb = new stdClass();
        $todb->reportid = $reportid;
        $todb->type = 'date_content';
        $todb->name = 'enable';
        $todb->value = $reportid;
        $DB->insert_record('report_builder_settings', $todb);
        $todb->name = 'when';
        $todb->value = 'future';
        $DB->insert_record('report_builder_settings', $todb);
        $todb->type = 'user_content';
        $todb->name = 'enable';
        $todb->value = $reportid;
        $DB->insert_record('report_builder_settings', $todb);
        $todb->name = 'who';
        $todb->value = rb_user_content::USER_OWN;
        $DB->insert_record('report_builder_settings', $todb);
        $rb = reportbuilder::create($reportid);
        // should return the appropriate text description if content mode = 1
        $this->assertRegExp('/The User is ".*" or The completion date occurred after .*/', current($rb->get_restriction_descriptions('content')));
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL, array('id' => $reportid));
        $rb = reportbuilder::create($reportid);
        // should return the appropriate array of text descriptions if content mode = 2
        $restrictions = $rb->get_restriction_descriptions('content');
        $firstrestriction = current($restrictions);
        $secondrestriction = next($restrictions);
        $this->assertRegExp('/^The User is ".*"$/', $firstrestriction);
        $this->assertRegExp('/^The completion date occurred after/', $secondrestriction);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_column_fields() {
        $rb = $this->rb;
        $columns = $rb->get_column_fields();
        // should return an array
        $this->assertTrue((bool)is_array($columns));
        // the array should contain the correct number of columns
        $this->assertEquals(17, count($columns));
        // the strings should have the correct format
        // can't check exactly because different dbs use different concat format
        $this->assertRegExp('/auser\.firstname/', current($columns));
        $this->assertRegExp('/auser\.lastname/', current($columns));
        $this->assertRegExp('/user_namelink/', current($columns));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_joins() {
        $rb = $this->rb;
        $obj1 = new stdClass();
        $obj1->joins = array('auser','competency');
        $obj2 = new stdClass();
        $obj2->joins = 'completion_position';
        $joins = $rb->get_joins($obj1, 'test');
        // should return an array
        $this->assertTrue((bool)is_array($joins));
        // the array should contain the correct number of columns
        $this->assertEquals(2, count($joins));
        $userjoin = new rb_join(
            'auser',
            'LEFT',
            '{user}',
            'auser.id = base.userid',
            1,
            'base'
        );
        // the strings should have the correct format
        $this->assertEquals($userjoin, current($joins));
        // should also work with string instead of array
        $joins2 = $rb->get_joins($obj2, 'test');
        $this->assertTrue((bool)is_array($joins2));
        // the array should contain the correct number of joins
        $this->assertEquals(1, count($joins2));
        $posjoin = new rb_join(
            'completion_position',
            'LEFT',
            '{pos}',
            'completion_position.id = base.positionid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        // the strings should have the correct format
        $this->assertEquals($posjoin, current($joins2));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_content_joins() {
        $rb = $this->rb;
        // should return an empty array if content mode = 0
        $this->assertEquals(array(), $rb->get_content_joins());
        // TODO test other options
        // can't do with competency evidence as no joins required

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_column_joins() {
        $rb = $this->rb;
        $columns = $rb->get_column_joins();
        // should return an array
        $this->assertTrue((bool)is_array($columns));
        // the array should contain the correct number of columns
        $this->assertEquals(5, count($columns));
        $userjoin = new rb_join(
            'auser',
            'LEFT',
            '{user}',
            'auser.id = base.userid',
            1,
            'base'
        );
        // the strings should have the correct format
        $this->assertEquals($userjoin, current($columns));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_filter_joins() {
        global $SESSION;
        $rb = $this->rb;
        // set a filter session var
        $SESSION->reportbuilder[$rb->get_uniqueid()] = array('user-fullname' => 'unused', 'competency-fullname' => 'unused');
        $joins = $rb->get_filter_joins();
        // should return an array
        $this->assertTrue((bool)is_array($joins));
        // the array should contain the correct number of joins
        $this->assertEquals(2, count($joins));

        $userjoin = new rb_join(
            'auser',
            'LEFT',
            '{user}',
            'auser.id = base.userid',
            1,
            'base'
        );
        // the strings should have the correct format
        $this->assertEquals($userjoin, current($joins));
        unset($SESSION->reportbuilder[$rb->get_uniqueid()]);

        $this->resetAfterTest(true);
    }

    /**
     * Test that internally grouped report instances recognized correctly
     */
    public function test_is_internally_grouped() {
        $rb = $this->rb;

        // Check internally non grouped report without user columns aggregation.
        $this->assertFalse($rb->grouped);
        $this->assertFalse($rb->is_internally_grouped());

        // Check internally non grouped report with user columns aggregation.
        $this->add_column($rb, 'competency', 'path', null, 'groupconcat', '', 0);

        $report = reportbuilder::create($this->rb->_id);
        $this->assertTrue($report->grouped);
        $this->assertFalse($report->is_internally_grouped());

        // Check internally grouped report without user columns aggregation.
        // Create report.
        $rid = $this->create_report('certification_overview', 'Certification overview');

        $report = reportbuilder::create($rid);
        $this->add_column($report, 'user', 'namelinkicon', null, null, '', 0);
        $this->add_column($report, 'user', 'username', null, null, '', 0);
        $this->add_column($report, 'certif_completion', 'progress', null, null, '', 0);

        // Get report.
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->assertFalse($report->grouped);
        $this->assertFalse($report->is_internally_grouped());

        // Check grouped report with user columns aggregation.
        $this->add_column($report, 'certif_completion', 'renewalstatus', null, 'groupconcat', '', 0);
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->grouped);
        $this->assertFalse($report->is_internally_grouped());
    }

    /*
    function test_reportbuilder_sort_join() {
        $rb = $this->rb;
        // should return the correct values for valid joins
        $this->assertEquals(-1, $rb->sort_join('user','position_assignment'));
        $this->assertEquals(1, $rb->sort_join('position_assignment','user'));
        $this->assertEquals(0, $rb->sort_join('user','user'));
        // should throw errors if invalid keys provided
        $this->expectError('Missing array key in sort_join(). Add \'junk\' to order array.');
        $this->assertEquals(-1, $rb->sort_join('user', 'junk'));
        $this->expectError('Missing array key in sort_join(). Add \'junk\' to order array.');
        $this->assertEquals(1, $rb->sort_join('junk', 'user'));
        $this->expectError('Missing array keys in sort_join(). Add \'junk\' and \'junk2\' to order array.');
        $this->assertEquals(0, $rb->sort_join('junk', 'junk2'));
    }
     */

    function test_reportbuilder_build_query() {
        global $SESSION;
        $filtername = 'filtering_test_report';
        // create a complex set of filtering criteria
        $SESSION->$filtername = array(
            'user-fullname' => array(
                array(
                    'operator' => 0,
                    'value' => 'John',
                )
            ),
            'user-organisationpath' => array(
                array(
                    'operator' => 1,
                    'value' => '21',
                    'recursive' => 1,
                )
            ),
            'competency-fullname' => array(
                array(
                    'operator' => 0,
                    'value' => 'fire',
                )
            ),
            'competency_evidence-timemodified' => array(
                array(
                    'after' => 0,
                    'before' => 1271764800,
                )
            ),
            'competency_evidence-proficiencyid' => array(
                array(
                    'operator' => 1,
                    'value' => '3',
                )
            ),
        );
        $rb = $this->rb;
        $sql_count_filtered = $rb->build_query(true, true);
        $sql_count_unfiltered = $rb->build_query(true, false);
        $sql_query_filtered = $rb->build_query(false, true);
        $sql_query_unfiltered = $rb->build_query(false, false);
        // if counting records, the SQL should include the string "count(*)"
        $this->assertRegExp('/count\(\*\)/i', $sql_count_filtered[0]);
        $this->assertRegExp('/count\(\*\)/i', $sql_count_unfiltered[0]);
        // if not counting records, the SQL should not include the string "count(*)"
        $this->assertNotRegExp('/count\(\*\)/i', $sql_query_filtered[0]);
        $this->assertNotRegExp('/count\(\*\)/i', $sql_query_unfiltered[0]);
        // if not filtered, the SQL should include the string "where (1=1) " with no other clauses
        $this->assertRegExp('/where \(\s+1=1\s+\)\s*/i', $sql_count_unfiltered[0]);
        $this->assertRegExp('/where \(\s+1=1\s+\)\s*/i', $sql_query_unfiltered[0]);
        // hard to do further testing as no actual data or tables exist

        // delete complex query from session
        unset($SESSION->$filtername);

        $this->resetAfterTest(true);
    }

    // can't test the following functions as data and tables don't exist
    // get_full_count()
    // get_filtered_count()
    // export_data()
    // display_table()
    // fetch_data()
    // add_admin_columns()


    function test_reportbuilder_check_sort_keys() {
        global $SESSION;
        // set a bad sortorder key
        $SESSION->flextable[$this->rb->get_uniqueid('rb')]['sortby']['bad_key'] = 4;
        $before = count($SESSION->flextable[$this->rb->get_uniqueid('rb')]['sortby']);
        $rb = $this->rb;
        // run the function
        $rb->check_sort_keys();
        $after = count($SESSION->flextable[$this->rb->get_uniqueid('rb')]['sortby']);
        // the bad sort key should have been deleted
        $this->assertEquals(1, $before - $after);

        $this->resetAfterTest(true);
    }

    public function test_get_report_sort() {
        global $SESSION, $DB;
        $this->resetAfterTest();

        unset($SESSION->flextable);
        $rb = reportbuilder::create($this->rb->_id);
        $this->assertSame(' ORDER BY base.id', $rb->get_report_sort());
        $this->assertSame(' ORDER BY base.id', $rb->get_report_sort(false));

        unset($SESSION->flextable);
        $DB->set_field('report_builder', 'defaultsortcolumn', 'competency_evidence_position', array('id' => $this->rb->_id));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $this->rb->_id));
        $rb = reportbuilder::create($this->rb->_id);
        $this->assertSame(' ORDER BY competency_evidence_position DESC, base.id', $rb->get_report_sort());
        $this->assertSame(' ORDER BY competency_evidence_position DESC, base.id', $rb->get_report_sort(false));

        $SESSION->flextable[$this->rb->get_uniqueid('rb')] = array(
            'collapse' => array(),
            'sortby'   => array('competency_evidence_position' => SORT_ASC),
            'i_first'  => '',
            'i_last'   => '',
            'textsort' => array(),
        );
        $rb = reportbuilder::create($this->rb->_id);
        $this->assertSame(' ORDER BY competency_evidence_position ASC, base.id', $rb->get_report_sort());
        $this->assertSame(' ORDER BY competency_evidence_position DESC, base.id', $rb->get_report_sort(false));
    }

    // skipping tests for the following as they just print HTML
    // export_select()
    // view_button()
    // save_button()
    // saved_menu()
    // edit_button()

    function test_reportbuilder_get_content_options() {
        $rb = $this->rb;
        $contentoptions = $rb->get_content_options();
        // Should return an array of content options.
        $this->assertTrue((bool)is_array($contentoptions));
        // Should have the right amount of options in the appropriate format.
        $this->assertCount(6, $contentoptions);
        $this->assertTrue(in_array('user', $contentoptions));
        $this->assertTrue(in_array('current_pos', $contentoptions));
        $this->assertTrue(in_array('current_org', $contentoptions));
        $this->assertTrue(in_array('completed_org', $contentoptions));
        $this->assertTrue(in_array('date', $contentoptions));
        $this->assertTrue(in_array('audience', $contentoptions));

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_filters_select() {
        $rb = $this->rb;
        $options = $rb->get_filters_select();
        // should return an array
        $this->assertTrue((bool)is_array($options));
        // the strings should have the correct format
        $this->assertEquals("User's Fullname", $options['User']['user-fullname']);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_get_columns_select() {
        $rb = $this->rb;
        $options = $rb->get_columns_select();
        // should return an array
        $this->assertTrue((bool)is_array($options));
        // the strings should have the correct format
        $this->assertEquals("User's Fullname", $options['User']['user-fullname']->name);
        $this->assertFalse($options['User']['user-fullname']->attributes['deprecated']);
        $this->assertFalse($options['User']['user-fullname']->attributes['issubquery']);

        $this->assertEquals("User's Position Name(s)", $options['All User\'s Job Assignments']['job_assignment-allpositionnames']->name);
        $this->assertFalse($options['All User\'s Job Assignments']['job_assignment-allpositionnames']->attributes['deprecated']);
        $this->assertTrue($options['All User\'s Job Assignments']['job_assignment-allpositionnames']->attributes['issubquery']);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_delete_column() {
        $rb = $this->rb;
        $before = count($rb->columns);
        $rb->delete_column(999);
        $afterfail = count($rb->columns);
        // should not delete column if cid doesn't match
        $this->assertEquals($before, $afterfail);
        // should return true if successful
        $this->assertTrue((bool)$rb->delete_column($this->column4->id));
        $after = count($rb->columns);
        // should be one less column after successful delete operation
        $this->assertEquals($before - 1, $after);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_delete_filter() {
        $rb = $this->rb;
        $before = count($rb->filters);
        $rb->delete_filter(999);
        $afterfail = count($rb->filters);
        // should not delete filter if fid doesn't match
        $this->assertEquals($before, $afterfail);
        // should return true if successful
        $this->assertTrue((bool)$rb->delete_filter($this->filter4->id));
        $after = count($rb->filters);
        // should be one less filter after successful delete operation
        $this->assertEquals($before - 1, $after);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_move_column() {
        $rb = $this->rb;
        reset($rb->columns);
        $firstbefore = current($rb->columns);
        $secondbefore = next($rb->columns);
        $thirdbefore = next($rb->columns);
        // should not be able to move first column up
        $this->assertFalse((bool)$rb->move_column($this->column1->id, 'up'));
        reset($rb->columns);
        $firstafter = current($rb->columns);
        $secondafter = next($rb->columns);
        $thirdafter = next($rb->columns);
        // columns should not change if trying to do a bad column move
        $this->assertEquals($firstbefore, $firstafter);
        $this->assertEquals($secondbefore, $secondafter);
        // should be able to move first column down
        $this->assertTrue((bool)$rb->move_column($this->column1->id, 'down'));
        reset($rb->columns);
        $firstafter = current($rb->columns);
        $secondafter = next($rb->columns);
        $thirdafter = next($rb->columns);
        // columns should change if move is valid
        $this->assertNotEquals($firstbefore, $firstafter);
        // moved columns should have swapped
        $this->assertEquals($firstbefore, $secondafter);
        $this->assertEquals($secondbefore, $firstafter);
        // unmoved columns should stay the same
        $this->assertEquals($thirdbefore, $thirdafter);

        $this->resetAfterTest(true);
    }

    function test_reportbuilder_move_filter() {
        $rb = $this->rb;
        reset($rb->filters);
        $firstbefore = current($rb->filters);
        $secondbefore = next($rb->filters);
        $thirdbefore = next($rb->filters);
        // should not be able to move first filter up
        $this->assertFalse((bool)$rb->move_filter($this->filter1->id, 'up'));
        reset($rb->filters);
        $firstafter = current($rb->filters);
        $secondafter = next($rb->filters);
        $thirdafter = next($rb->filters);
        // filters should not change if trying to do a bad filter move
        $this->assertEquals($firstbefore, $firstafter);
        $this->assertEquals($secondbefore, $secondafter);
        // should be able to move first filter down
        $this->assertTrue((bool)$rb->move_filter($this->filter1->id, 'down'));
        reset($rb->filters);
        $firstafter = current($rb->filters);
        $secondafter = next($rb->filters);
        $thirdafter = next($rb->filters);
        // filters should change if move is valid
        // For some weird reason the following assert sometimes fails with "Undefined offset: 1" ??? Let's silence it for now.
        @$this->assertNotEquals($firstbefore, $firstafter);
        // moved filters should have swapped
        $this->assertEquals($firstbefore, $secondafter);
        $this->assertEquals($secondbefore, $firstafter);
        // unmoved filters should stay the same
        $this->assertEquals($thirdbefore, $thirdafter);

        $this->resetAfterTest(true);
    }

    public function test_reportbuilder_export_schduled_report() {
        $this->resetAfterTest(true);

        $admin = get_admin();
        $this->setAdminUser();

        $sched = new stdClass();
        $sched->id = 1;
        $sched->reportid = $this->rb->_id;
        $sched->format = 'excel';
        $sched->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $sched->savedsearchid = 0;
        $sched->userid = $admin->id;

        $filename = reportbuilder_export_schduled_report($sched, $this->rb, 'tabexport_excel\writer');
        $this->assertFileExists($filename);
        unlink($filename);
        unset($sched);

        $sched = new stdClass();
        $sched->id = 2;
        $sched->reportid = $this->rb->_id;
        $sched->format = 'csv';
        $sched->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $sched->savedsearchid = 0;
        $sched->userid = $admin->id;

        $filename = reportbuilder_export_schduled_report($sched, $this->rb, 'tabexport_csv\writer');
        $this->assertFileExists($filename);
        unlink($filename);
        unset($sched);

        $sched = new stdClass();
        $sched->id = 3;
        $sched->reportid = $this->rb->_id;
        $sched->format = 'ods';
        $sched->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $sched->savedsearchid = 0;
        $sched->userid = $admin->id;

        $filename = reportbuilder_export_schduled_report($sched, $this->rb, 'tabexport_ods\writer');
        $this->assertFileExists($filename);
        unlink($filename);
        unset($sched);

        $sched = new stdClass();
        $sched->id = 3;
        $sched->reportid = $this->rb->_id;
        $sched->format = 'pdflandscape';
        $sched->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $sched->savedsearchid = 0;
        $sched->userid = $admin->id;

        $filename = reportbuilder_export_schduled_report($sched, $this->rb, 'tabexport_pdflandscape\writer');
        $this->assertFileExists($filename);
        unlink($filename);
        unset($sched);

        $sched = new stdClass();
        $sched->id = 3;
        $sched->reportid = $this->rb->_id;
        $sched->format = 'pdfportrait';
        $sched->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $sched->savedsearchid = 0;
        $sched->userid = $admin->id;

        $filename = reportbuilder_export_schduled_report($sched, $this->rb, 'tabexport_pdfportrait\writer');
        $this->assertFileExists($filename);
        unlink($filename);
        unset($sched);
    }

    public function test_get_search_columns() {
        global $DB;
        // Add two reports.
        $report2 = new stdclass();
        $report2->fullname = 'Courses';
        $report2->shortname = 'mycourses';
        $report2->source = 'courses';
        $report2->hidden = 1;
        $report2->embedded = 0;
        $report2->id = $DB->insert_record('report_builder', $report2);

        $report3 = new stdclass();
        $report3->fullname = 'Courses2';
        $report3->shortname = 'mycourses2';
        $report3->source = 'courses';
        $report3->hidden = 1;
        $report3->embedded = 0;
        $report3->id = $DB->insert_record('report_builder', $report3);

        // Add search columns to two reports.
        $rbsearchcolsdata = array(
                        array('id' => 100, 'reportid' => $this->rb->_id, 'type' => 'course', 'value' => 'fullname',
                              'heading' => 'A', 'sortorder' => 1),
                        array('id' => 101, 'reportid' => $this->rb->_id, 'type' => 'course', 'value' => 'summary',
                              'heading' => 'B', 'sortorder' => 2),
                        array('id' => 102, 'reportid' => $report2->id, 'type' => 'course', 'value' => 'fullname',
                              'heading' => 'C', 'sortorder' => 1));

        $this->loadDataSet($this->createArrayDataSet(array(
            'report_builder_search_cols' => $rbsearchcolsdata)));

        // Test result for reports with/without search columns.
        $report1 = reportbuilder::create($this->rb->_id);
        $cols1 = $report1->get_search_columns();
        $this->assertCount(2, $cols1);
        $this->assertArrayHasKey(100, $cols1);
        $this->assertArrayHasKey(101, $cols1);

        $report3 = reportbuilder::create($report3->id);
        $cols3 = $report3->get_search_columns();
        $this->assertEmpty($cols3);
    }

    public function test_delete_search_column() {
        global $DB;
        // Add two reports.
        $rb2data = array(array('id' => 2, 'fullname' => 'Courses', 'shortname' => 'mycourses',
                         'source' => 'courses', 'hidden' => 1, 'embedded' => 0));

        $rbsearchcolsdata = array(
                        array('id' => 100, 'reportid' => 2, 'type' => 'course', 'value' => 'coursetypeicon',
                              'heading' => 'A', 'sortorder' => 1),
                        array('id' => 101, 'reportid' => 2, 'type' => 'course', 'value' => 'courselink',
                              'heading' => 'B', 'sortorder' => 2));

        // Add search columns to two reports.
        $this->loadDataSet($this->createArrayDataSet(array(
            'report_builder' => $rb2data,
            'report_builder_search_cols' => $rbsearchcolsdata)));

        // Test result for reports with/without search columns.
        $report2 = reportbuilder::create(2);
        $report2->delete_search_column(100);
        $cols2 = $report2->get_search_columns();
        $this->assertCount(1, $cols2);
        $this->assertArrayHasKey(101, $cols2);
    }

    public function test_get_search_columns_select() {
        $report1 = reportbuilder::create($this->rb->_id);
        $cols1 = $report1->get_search_columns_select();
        // Current test report has at least three groups. Check some items inside aswell.
        $this->assertGreaterThanOrEqual(3, count($cols1));
        $compevidstr = get_string('type_competency_evidence', 'rb_source_competency_evidence');
        $compstr = get_string('type_competency', 'rb_source_dp_competency');
        $userstr = get_string('type_user', 'totara_reportbuilder');
        $this->assertArrayHasKey($compevidstr, $cols1);
        $this->assertArrayHasKey($compstr, $cols1);
        $this->assertArrayHasKey($userstr, $cols1);

        $this->assertArrayHasKey('competency_evidence-organisation', $cols1[$compevidstr]);
        $this->assertArrayHasKey('competency-fullname', $cols1[$compstr]);
        $this->assertArrayHasKey('user-fullname', $cols1[$userstr]);
    }

    /**
     * Also test get_sidebar_filters
     */
    public function test_get_standard_filters() {
        global $DB;
        // Add reports.
        $rb2data = array(array('id' => 59, 'fullname' => 'Courses', 'shortname' => 'mycourses',
                         'source' => 'courses', 'hidden' => 1, 'embedded' => 0),
                         array('id' => 3, 'fullname' => 'Courses2', 'shortname' => 'mycourses2',
                         'source' => 'courses', 'hidden' => 1, 'embedded' => 0));
        $rbfiltersdata = array(
            array('id' => 171, 'reportid' => 59, 'type' => 'course', 'value' => 'coursetype',
                  'sortorder' => 1, 'advanced' => 0, 'region' => rb_filter_type::RB_FILTER_REGION_SIDEBAR),
            array('id' => 172, 'reportid' => 59, 'type' => 'course', 'value' => 'mods',
                  'sortorder' => 2, 'advanced' => 1, 'region' => rb_filter_type::RB_FILTER_REGION_SIDEBAR),
            array('id' => 173, 'reportid' => 59, 'type' => 'course', 'value' => 'startdate',
                  'sortorder' => 3, 'advanced' => 0, 'region' => rb_filter_type::RB_FILTER_REGION_STANDARD),
            array('id' => 174, 'reportid' => 59, 'type' => 'course', 'value' => 'name_and_summary',
                  'sortorder' => 4, 'advanced' => 1, 'region' => rb_filter_type::RB_FILTER_REGION_STANDARD)
            );
        // Add filters to report.
        $this->loadDataSet($this->createArrayDataSet(array(
            'report_builder' => $rb2data,
            'report_builder_filters' => $rbfiltersdata)));

        // Report 59 has two sidebar filters.
        $report59 = reportbuilder::create(59);
        $side59 = $report59->get_sidebar_filters();
        $this->assertCount(2, $side59);
        $this->assertArrayHasKey('course-coursetype', $side59);
        $this->assertArrayHasKey('course-mods', $side59);

        // Report 59 has two standard filters.
        $std59 = $report59->get_standard_filters();
        $this->assertCount(2, $std59);
        $this->assertArrayHasKey('course-startdate', $std59);
        $this->assertArrayHasKey('course-name_and_summary', $std59);

        // Report 3 doesn't have filters.
        $report3 = reportbuilder::create(3);
        $side3 = $report3->get_sidebar_filters();
        $std3 = $report3->get_standard_filters();
        $this->assertEmpty($side3);
        $this->assertEmpty($std3);
    }

    public function test_get_all_filter_joins() {
        $report = reportbuilder::create($this->rb->_id);
        $joins = $report->get_all_filter_joins();

        $this->assertNotEmpty($joins);
        $this->assertContainsOnlyInstancesOf('rb_join', $joins);
    }

    public function test_get_filters_select() {
        $report = reportbuilder::create($this->rb->_id);
        $filters = $report->get_filters_select();

        $compevidstr = get_string('type_competency_evidence', 'rb_source_competency_evidence');
        $compstr = get_string('type_competency', 'rb_source_dp_competency');
        $userstr = get_string('type_user', 'totara_reportbuilder');
        $this->assertArrayHasKey($compevidstr, $filters);
        $this->assertArrayHasKey($compstr, $filters);
        $this->assertArrayHasKey($userstr, $filters);

        $this->assertArrayHasKey('competency_evidence-timemodified', $filters[$compevidstr]);
        $this->assertArrayHasKey('competency-fullname', $filters[$compstr]);
        $this->assertArrayHasKey('user-fullname', $filters[$userstr]);
    }

    public function test_get_all_filters_select() {
        $report1 = reportbuilder::create($this->rb->_id);
        $filters = $report1->get_all_filters_select();

        $this->assertArrayHasKey('allstandardfilters', $filters);
        $this->assertArrayHasKey('unusedstandardfilters', $filters);
        $this->assertArrayHasKey('allsidebarfilters', $filters);
        $this->assertArrayHasKey('unusedsidebarfilters', $filters);
        $this->assertArrayHasKey('allsearchcolumns', $filters);
        $this->assertArrayHasKey('unusedsearchcolumns', $filters);

        // Check couple filters that should be in every category.
        $userstr = get_string('type_user', 'totara_reportbuilder');
        $compevidstr = get_string('type_competency_evidence', 'rb_source_competency_evidence');
        foreach ($filters as $key => $filter) {
            if (strpos($key, 'unused') === false) {
                $this->assertArrayHasKey($compevidstr, $filter);
                $this->assertGreaterThan(0, $filter[$compevidstr]);
            }
            // Check only rare-used filter. If it dissappear choose another filter for test.
            $this->assertArrayHasKey($userstr, $filter);
            $this->assertGreaterThan(0, $filter[$userstr]);
        }

    }

    public function test_reportbuilder_delete_report() {
        global $DB;

        $rb = $this->rb;

        // Make sure the delete SQL does not throw any exceptions.
        $this->assertTrue($DB->record_exists('report_builder', array('id' => $rb->_id)));
        reportbuilder_delete_report($rb->_id);
        $this->assertFalse($DB->record_exists('report_builder', array('id' => $rb->_id)));
    }

    /**
     * Test that sql concatention used in file names are working correctly
     */
    public function test_fullname_join_sql() {
        global $DB, $CFG;
        $user = $this->getDataGenerator()->create_user(array('username' => 'test', 'firstname'=>'first', 'lastname'=>'last'));

        // Force set middlename field to null, because generator will add some randome string there.
        $DB->execute('UPDATE {user} SET middlename = NULL WHERE id = ?', array($user->id));

        // Set fullname setting.
        $origfullnamedisplay = $CFG->fullnamedisplay;
        $CFG->fullnamedisplay = 'firstname middlename lastname';

        // Create report.
        $rid = $this->create_report('user', 'Test user report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'namelinkicon', null, null, '', 0);
        $this->add_column($report, 'user', 'username', null, null, '', 0);

        // Get report.
        $report = reportbuilder::create($rid, $config);
        list($sql, $params, $cache) = $report->build_query(false, false, false);
        $records = $DB->get_recordset_sql($sql, $params);

        // Assert.
        $found = false;
        foreach ($records as $record) {
            if ($record->id == $user->id) {
                $found = true;
                $this->assertEquals('first  last', $record->user_namelinkicon);
                break;
            }
        }
        $this->assertTrue($found);

        // Revert CFG changes.
        $CFG->fullnamedisplay = $origfullnamedisplay;
    }

    /**
     * Test reportbuilder_get_all_scheduled_reports_without_recipients
     */
    public function test_reportbuilder_get_all_scheduled_reports_without_recipients() {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user(array('username' => 'test', 'firstname'=>'first', 'lastname'=>'last'));

        $todb = new stdClass();
        $todb->reportid = $this->rb->_id;
        $todb->savedsearchid = $this->savedsearch->id;
        $todb->userid = $user->id;
        $todb->format = 'csv';
        $todb->exporttofilesystem = 0;
        $todb->frequency = 1;
        $todb->schedule = 0;
        $todb->nextreport = 0;
        $todb->usermodified = $user->id;
        $todb->lastmodified = time();
        $newid = $DB->insert_record('report_builder_schedule', $todb);

        // Create scheduled report by the user.
        $scheduleemail = new email_setting_schedule($newid);

        // Check when no recipients.
        $reportschedules = reportbuilder_get_all_scheduled_reports_without_recipients();
        $this->assertCount(1, $reportschedules);
        $this->assertEquals($user->id, $reportschedules[$newid]->userid);

        $this->setAdminUser();

        // Check when audience recipient.
        $scheduleemail->set_email_settings(array(1), array(), array());
        $this->assertEmpty(reportbuilder_get_all_scheduled_reports_without_recipients());

        // Check when external email recipient.
        $scheduleemail->set_email_settings(array(), array(1), array());
        $this->assertEmpty(reportbuilder_get_all_scheduled_reports_without_recipients());

        // Check when system user recipient.
        $scheduleemail->set_email_settings(array(), array(), array(1));
        $this->assertEmpty(reportbuilder_get_all_scheduled_reports_without_recipients());

    }

    public function test_get_fetchmethod() {
        global $DB;

        $this->resetAfterTest(true);

        $default = reportbuilder::FETCHMETHOD_STANDARD_RECORDSET;
        if ($DB->recommends_counted_recordset()) {
            $default = reportbuilder::FETCHMETHOD_COUNTED_RECORDSET;
        }
        $plugin = 'totara_reportbuidler';
        $setting = 'defaultfetchmethod';

        $method = new ReflectionMethod(reportbuilder::class, 'get_fetch_method');
        $method->setAccessible(true);

        $rid = $this->create_report('user', 'Test user report 1');
        $config = new rb_config();
        $report = reportbuilder::create($rid, $config);

        self::assertEmpty(get_config($plugin, $setting));
        self::assertSame($default, $method->invoke($report));

        set_config($setting, reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, $plugin);
        $report = reportbuilder::create($rid, $config);
        self::assertSame($default, $method->invoke($report));

        set_config($setting, reportbuilder::FETCHMETHOD_COUNTED_RECORDSET, $plugin);
        $report = reportbuilder::create($rid, $config);
        self::assertSame(reportbuilder::FETCHMETHOD_COUNTED_RECORDSET, $method->invoke($report));

        set_config($setting, reportbuilder::FETCHMETHOD_STANDARD_RECORDSET, $plugin);
        $report = reportbuilder::create($rid, $config);
        self::assertSame(reportbuilder::FETCHMETHOD_STANDARD_RECORDSET, $method->invoke($report));

        set_config($setting, -1, $plugin);
        $report = reportbuilder::create($rid, $config);
        self::assertSame($default, $method->invoke($report));

        set_config($setting, 3, $plugin);
        $report = reportbuilder::create($rid, $config);
        self::assertSame($default, $method->invoke($report));

        set_config($setting, 'trust', $plugin);
        $report = reportbuilder::create($rid, $config);
        self::assertSame($default, $method->invoke($report));
    }

    public function test_get_default_fetch_method() {
        $plugin = 'totara_reportbuidler';
        $setting = 'defaultfetchmethod';

        self::assertEmpty(get_config($plugin, $setting));
        self::assertSame(reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, reportbuilder::get_default_fetch_method());

        set_config($setting, reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, $plugin);
        self::assertSame(reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, reportbuilder::get_default_fetch_method());

        set_config($setting, reportbuilder::FETCHMETHOD_COUNTED_RECORDSET, $plugin);
        self::assertSame(reportbuilder::FETCHMETHOD_COUNTED_RECORDSET, reportbuilder::get_default_fetch_method());

        set_config($setting, reportbuilder::FETCHMETHOD_STANDARD_RECORDSET, $plugin);
        self::assertSame(reportbuilder::FETCHMETHOD_STANDARD_RECORDSET, reportbuilder::get_default_fetch_method());

        set_config($setting, -1, $plugin);
        self::assertSame(reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, reportbuilder::get_default_fetch_method());

        set_config($setting, 3, $plugin);
        self::assertSame(reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, reportbuilder::get_default_fetch_method());

        set_config($setting, 'trust', $plugin);
        self::assertSame(reportbuilder::FETCHMETHOD_DATABASE_RECOMMENDATION, reportbuilder::get_default_fetch_method());

        unset_config($setting, $plugin);
    }
}
