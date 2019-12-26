<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/totara/hierarchy/lib.php");
require_once("{$CFG->dirroot}/totara/hierarchy/prefix/organisation/lib.php");
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Class organisation_dialog_content_hierarchy_get_framework_test
 */
class totara_hierarchy_organisation_dialog_content_hierarchy_get_framework_testcase extends advanced_testcase {
    /**
     * Create an organisation framework and 2 associate organisations
     * @return stdClass
     */
    public function create_organisations(): stdClass {
        $generator = $this->getDataGenerator();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $generator->get_plugin_generator("totara_hierarchy");
        $framework = $hierarchygenerator->create_framework("organisation", [
            'shortname' => 'Organisation framework 1'
        ]);

        $org1 = $hierarchygenerator->create_org([
            'frameworkid' => $framework->id,
            'fullname' => 'org1',
            'shortname' => 'org1'
        ]);

        $org2 = $hierarchygenerator->create_org([
            'frameworkid' => $framework->id,
            'fullname' => 'org2',
            'shortname' => 'org2'
        ]);

        $framework->hierarchies = array($org1, $org2);
        return $framework;
    }

    /**
     * Create a report with a source of rb_source_dp_certification
     * @return int
     */
    public function create_report(): int {
        global $DB;
        $generator = $this->getDataGenerator();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $generator->get_plugin_generator("totara_reportbuilder");

        $rid = $reportgenerator->create_default_standard_report((object)[
            'fullname' => 'ROL',
            'shortname' => 'ROL',
            'source' => 'dp_certification',
            'contentmode' => 1,
            'accessmode' => 0,
        ]);

        $type = str_replace("rb_", "", rb_current_org_content::class);
        $DB->insert_records('report_builder_settings', [
            (object) [
                'reportid' => $rid,
                'type' => $type,
                'name' => 'enable',
                'value' => '1'
            ],
            (object) [
                'reportid' => $rid,
                'type' => $type,
                'name' => 'recursive',
                'value' => rb_current_org_content::CONTENT_ORG_EQUAL
            ]
        ]);

        return $rid;
    }

    /**
     * Assigning an organisation to the user job assignment
     * @param stdClass $user
     */
    public function assign_organisation_to_user(stdClass $user, stdClass $org): void {
        global $DB, $USER;

        $name = uniqid();
        $time = time();
        $DB->insert_record("job_assignment", (object)[
            'userid' => $user->id,
            'fullname' => $name,
            'shortname' => $name,
            'startdate' => $time,
            'enddate' => $time + (3600 * 60),
            'timecreated' => $time,
            'timemodified' => $time,
            'usermodified' => $USER->id,
            'organisationid' => $org->id,
            'positionassignmentdate' => $time,
            'sortorder' => 100
        ]);
    }

    /**
     * A test suite of checking the organisation dialog search box is applying the content restriction from report builder
     * or not. If the report had contentmode as content restriction enable, then the search dialog content will also
     * apply this content restriction mode on rendering the organisation. Therefore, in the result test, it is expecting
     * one organsiation to be found in dialog, since that organisation was assigned to the viewing user. The viewing user
     * in this test suite is an admin
     *
     * @return void
     */
    public function test_get_organisation_and_framework(): void {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $framework = $this->create_organisations();

        // Getting the first organisation here
        $org = current($framework->hierarchies);

        $this->assign_organisation_to_user($USER, $org);
        $reportid = $this->create_report();

        $dialog = new totara_dialog_content_hierarchy_multi('organisation', $framework->id, false, false, $reportid);

        $dialog->show_treeview_only = false;
        $dialog->load_items(0);

        $dialog->selected_title = 'itemstoadd';
        $dialog->select_title = '';

        // When the content restriction is applied to the report, the Organisation fitler also has this restriction
        // applied too. Since the current user only had one organisation assigned, therefore, the curent user must
        // not able to see the other organisation
        $this->assertCount(1, $dialog->items);

        $this->assertEquals($org, current($dialog->items));
    }
}
