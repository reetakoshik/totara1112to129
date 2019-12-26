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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/db/upgradelib.php');

/**
 * Test report builder database upgrades.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_reportbuilder_upgradelib_testcase
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_upgradelib_testcase extends advanced_testcase {

    private $report, $user, $rbcolumn, $contcol, $rbfilter, $contfil, $rbsaved, $contsave;

    public function setUp() {
        global $DB;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->user = $user = get_admin();

        $report = new stdclass();
        $report->fullname = 'Test Report';
        $report->shortname = 'tstrpt';
        $report->source = 'user';
        $report->hidden = 0;
        $report->accessmode = 0;
        $report->contentmode = 0;
        $report->description = '';
        $report->recordsperpage = 40;
        $report->defaultsortcolumn = 'user_fullname';
        $report->defaultsortorder = 4;
        $report->embedded = 0;
        $report->id = $DB->insert_record('report_builder', $report);
        $this->report = $report;

        $rbcolumn = new stdClass();
        $rbcolumn->reportid = $report->id;
        $rbcolumn->type = 'user';
        $rbcolumn->value = 'fullname';
        $rbcolumn->heading = 'Participant';
        $rbcolumn->sortorder = 1;
        $rbcolumn->hidden = 0;
        $rbcolumn->customheading = 1;
        $rbcolumn->id = $DB->insert_record('report_builder_columns', $rbcolumn);
        $this->rbcolumn = $rbcolumn;

        $contcol = new stdClass();
        $contcol->reportid = $report->id;
        $contcol->type = 'user';
        $contcol->value = 'username';
        $contcol->heading = 'Control Column';
        $contcol->sortorder = 1;
        $contcol->hidden = 0;
        $contcol->customheading = 1;
        $contcol->id = $DB->insert_record('report_builder_columns', $contcol);
        $this->contcol = $contcol;

        $rbfilter = new stdClass();
        $rbfilter->reportid = $report->id;
        $rbfilter->type = 'user';
        $rbfilter->value = 'fullname';
        $rbfilter->filtername = 'Participant';
        $rbfilter->advanced = 0;
        $rbfilter->sortorder = 1;
        $rbfilter->id = $DB->insert_record('report_builder_filters', $rbfilter);
        $this->rbfilter = $rbfilter;

        $contfil = new stdClass();
        $contfil->reportid = $report->id;
        $contfil->type = 'user';
        $contfil->value = 'username';
        $contfil->filtername = 'Control Filter';
        $contfil->advanced = 0;
        $contfil->sortorder = 1;
        $contfil->id = $DB->insert_record('report_builder_filters', $contfil);
        $this->contfil = $contfil;

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->id;
        $rbsaved->userid = $user->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = 'a:1:{s:13:"user-fullname";a:2:{s:8:"operator";i:0;s:5:"value";s:1:"a";}}';
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);
        $this->rbsaved = $rbsaved;

        $contsave = new stdClass();
        $contsave->reportid = $report->id;
        $contsave->userid = $user->id;
        $contsave->name = 'Control Saved';
        $contsave->search = 'a:1:{s:13:"user-username";a:2:{s:8:"operator";i:0;s:5:"value";s:1:"a";}}';
        $contsave->ispublic = 1;
        $contsave->id = $DB->insert_record('report_builder_saved', $contsave);
        $this->contsave = $contsave;
    }

    protected function tearDown() {
        $this->report = null;
        $this->user = null;
        $this->rbcolumn = null;
        $this->contcol = null;
        $this->rbfilter = null;
        $this->contfil = null;
        $this->rbsaved = null;
        $this->contsave = null;

        parent::tearDown();
    }

    public function test_upgradelib_migrate_columns() {
        global $DB;

        totara_reportbuilder_migrate_column_names(array('fullname' => 'shortname'), 'user');

        $column = $DB->get_record('report_builder_columns', array('id' => $this->rbcolumn->id));
        $this->assertEquals('shortname', $column->value);
        $this->assertEquals($this->rbcolumn->type, $column->type);
        $this->assertEquals($this->rbcolumn->heading, $column->heading);
        $this->assertEquals($this->rbcolumn->sortorder, $column->sortorder);
        $this->assertEquals($this->rbcolumn->hidden, $column->hidden);

        $control = $DB->get_record('report_builder_columns', array('id' => $this->contcol->id));
        $this->assertEquals($this->contcol->value, $control->value);
        $this->assertEquals($this->contcol->type, $control->type);
        $this->assertEquals($this->contcol->heading, $control->heading);
        $this->assertEquals($this->contcol->sortorder, $control->sortorder);
        $this->assertEquals($this->contcol->hidden, $control->hidden);

        totara_reportbuilder_migrate_column_types(array('fullname' => 'shortname'), 'user', 'manager');

        $column = $DB->get_record('report_builder_columns', array('id' => $this->rbcolumn->id));
        $this->assertEquals('shortname', $column->value);
        $this->assertEquals('manager', $column->type);
        $this->assertEquals($this->rbcolumn->heading, $column->heading);
        $this->assertEquals($this->rbcolumn->sortorder, $column->sortorder);
        $this->assertEquals($this->rbcolumn->hidden, $column->hidden);

        $control = $DB->get_record('report_builder_columns', array('id' => $this->contcol->id));
        $this->assertEquals($this->contcol->value, $control->value);
        $this->assertEquals($this->contcol->type, $control->type);
        $this->assertEquals($this->contcol->heading, $control->heading);
        $this->assertEquals($this->contcol->sortorder, $control->sortorder);
        $this->assertEquals($this->contcol->hidden, $control->hidden);
    }

    public function test_upgradelib_migrate_filters() {
        global $DB;

        totara_reportbuilder_migrate_filter_names(array('fullname' => 'shortname'), 'user');

        $filter = $DB->get_record('report_builder_filters', array('id' => $this->rbfilter->id));
        $this->assertEquals('shortname', $filter->value);
        $this->assertEquals($this->rbfilter->type, $filter->type);
        $this->assertEquals($this->rbfilter->filtername, $filter->filtername);
        $this->assertEquals($this->rbfilter->sortorder, $filter->sortorder);
        $this->assertEquals($this->rbfilter->advanced, $filter->advanced);

        $control = $DB->get_record('report_builder_filters', array('id' => $this->contfil->id));
        $this->assertEquals($this->contfil->value, $control->value);
        $this->assertEquals($this->contfil->type, $control->type);
        $this->assertEquals($this->contfil->filtername, $control->filtername);
        $this->assertEquals($this->contfil->sortorder, $control->sortorder);
        $this->assertEquals($this->contfil->advanced, $control->advanced);

        totara_reportbuilder_migrate_filter_types(array('fullname' => 'shortname'), 'user', 'manager');

        $filter = $DB->get_record('report_builder_filters', array('id' => $this->rbfilter->id));
        $this->assertEquals('shortname', $filter->value);
        $this->assertEquals('manager', $filter->type);
        $this->assertEquals($this->rbfilter->filtername, $filter->filtername);
        $this->assertEquals($this->rbfilter->sortorder, $filter->sortorder);
        $this->assertEquals($this->rbfilter->advanced, $filter->advanced);

        $control = $DB->get_record('report_builder_filters', array('id' => $this->contfil->id));
        $this->assertEquals($this->contfil->value, $control->value);
        $this->assertEquals($this->contfil->type, $control->type);
        $this->assertEquals($this->contfil->filtername, $control->filtername);
        $this->assertEquals($this->contfil->sortorder, $control->sortorder);
        $this->assertEquals($this->contfil->advanced, $control->advanced);
    }

    public function test_upgradelib_migrate_saved_search_filters() {
        global $DB;

        totara_reportbuilder_migrate_saved_search_filters(array('fullname' => 'shortname'), 'user', 'user');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-shortname', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        totara_reportbuilder_migrate_saved_search_filters(array('shortname' => 'middlename'), 'user', 'manager');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('manager-middlename', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }
    }

    public function test_upgradelib_migrate_saved_searches() {
        global $DB;

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        totara_reportbuilder_migrate_saved_searches('*', 'user', 'fullname', 'user', 'shortname');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-shortname', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        totara_reportbuilder_migrate_saved_searches('user', 'user', 'shortname', 'manager', 'middlename');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('manager-middlename', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        // Check if source is incorrect, record is not migrated.
        totara_reportbuilder_migrate_saved_searches('course_completions', 'manager', 'middlename', 'user', 'shortname');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('manager-middlename', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

    }

    public function test_upgradelib_populate_scheduled_reports_usermodified() {
        global $DB;

        $yesterday = \time() - (24 * 60 * 60);
        $template = new \stdClass();
        $template->reportid = $this->report->id;
        $template->userid = 0;
        $template->savedsearchid = 0;
        $template->format = 'csv';
        $template->exporttofilesystem = 0;
        $template->frequency = 4;
        $template->schedule = 1;
        $template->nextreport = $yesterday;
        $template->usermodified = 0;
        $template->lastmodified = $yesterday;

        $users = \array_map(
            function ($i) {
                return $this->getDataGenerator()->create_user()->id;
            },

            \range(0, 20, 1)
        );

        $scheduled = \array_map(
            function ($userid) use ($template) {
                $record = clone $template;
                $record->userid = $userid;

                return $record;
            },

            $users
        );

        $DB->insert_records('report_builder_schedule', $scheduled);
        \totara_reportbuilder_populate_scheduled_reports_usermodified();

        \array_map(
            function (\stdClass $actual) use ($template) {
                $this->assertNotEmpty($actual->id, 'invalid id');
                $this->assertSame((int)$actual->reportid, $template->reportid, 'wrong reportid');
                $this->assertNotEmpty($actual->userid, 'invalid userid');
                $this->assertSame((int)$actual->savedsearchid, $template->savedsearchid, 'wrong savedsearchid');
                $this->assertSame($actual->format, $template->format, 'wrong format');
                $this->assertSame((int)$actual->exporttofilesystem, $template->exporttofilesystem, 'wrong exporttofilesystem');
                $this->assertSame((int)$actual->frequency, $template->frequency, 'wrong frequency');
                $this->assertSame((int)$actual->schedule, $template->schedule, 'wrong schedule');
                $this->assertNotEmpty($actual->usermodified, 'invalid usermodified');
                $this->assertSame($actual->userid, $actual->usermodified, 'wrong usermodified');
                $this->assertSame((int)$actual->lastmodified, $template->lastmodified, 'wrong lastmodified');
            },

            $DB->get_records('report_builder_schedule')
        );
    }
}
