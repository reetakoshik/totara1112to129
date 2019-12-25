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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_display_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Ensure all columns have 'displayfunc' set.
     */
    public function test_all_columns_have_displayfunc() {
        $sourcelist = reportbuilder::get_source_list(true);
        foreach ($sourcelist as $sourcename => $title) {

            $src = reportbuilder::get_source_object($sourcename, true); // Caching here is completely fine.

            foreach ($src->columnoptions as $column) {
                // Check columns have a displayfunc defined.
                // Note: We are ignoring columns that use generators.
                $missingdisplayfunc = $column->selectable !== false && empty($column->displayfunc) && empty($column->columngenerator);
                $this->assertFalse($missingdisplayfunc, "displayfunc not defined for $column->type, $column->value in $sourcename rb source");
            }
        }
    }

    /**
     * Ensure all column 'displayfunc' option uses a display class.
     * All display functions 'rb_display_*' should now be deprecated.
     */
    public function test_display_display_class_exists() {
        $sourcelist = reportbuilder::get_source_list(true);
        foreach ($sourcelist as $sourcename => $title) {

            $src = reportbuilder::get_source_object($sourcename, true); // Caching here is completely fine.

            foreach ($src->columnoptions as $column) {
                if (!empty($column->displayfunc)) {
                    $hasdisplayclass = false;
                    foreach ($src->get_used_components() as $component) {
                        $classname = "\\$component\\rb\\display\\$column->displayfunc";
                        if (class_exists($classname)) {
                            $hasdisplayclass = true;
                        }
                    }

                    $this->assertTrue($hasdisplayclass, "Display class " . $classname . " not found for '" . $title . "' report");
                }
            }
        }
    }

    public function test_aggregation() {
        global $DB, $CFG, $OUTPUT, $PAGE;
        require_once($CFG->libdir . '/excellib.class.php');
        require_once($CFG->libdir . '/odslib.class.php');

        $syscontext = context_system::instance();

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to view all reports.

        $user = $this->getDataGenerator()->create_user();
        $user->firstaccess  = strtotime('2013-01-10 10:00:00 UTC');
        $user->timemodified = strtotime('2013-01-10 10:00:00 UTC');
        $user->lastlogin    = 0;
        $user->currentlogin = strtotime('2013-01-10 10:00:00 UTC'); // This is the lastlogin in reports.
        $user->timecreated  = strtotime('2013-01-07 10:00:00 UTC');
        $user->firstname  = 'řízek';
        $DB->update_record('user', $user);

        $usercontext = context_user::instance($user->id);

        // Let's create some user fields, there is no suitable API here, let's do it the raw way.

        $field = new stdClass();
        $field->name = 'Some text';
        $field->shortname = 'sometext';
        $field->datatype = 'textarea';
        $field->description = 'some description';
        $field->descriptionformat = FORMAT_HTML;
        $field->defaultdata = '';
        $field->defaultdataformat = FORMAT_HTML;
        $field->id = $DB->insert_record('user_info_field', $field);

        $uf = new stdClass();
        $uf->userid = $user->id;
        $uf->fieldid = $field->id;
        $uf->data = 'Some html <strong>text</strong><script></script>';
        $uf->dataformat = FORMAT_HTML;
        $DB->insert_record('user_info_data', $uf);

        $rid = $this->create_report('user', 'Test user report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstaccess', 'month', null, null, 0);
        $this->add_column($report, 'user', 'timemodified', null, null, null, 0);
        $this->add_column($report, 'user', 'lastlogin', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'timecreated', 'weekday', null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field->id, null, null, null, 0);

        $report = reportbuilder::create($rid);

        // Let's hack the column options in memory only, hopefully this will continue working in the future...
        $report->columns['user-firstaccess']->displayfunc = 'month';
        $report->columns['user-timemodified']->displayfunc = 'nice_date';
        $report->columns['user-lastlogin']->displayfunc = 'nice_datetime';
        $report->columns['user-firstname']->displayfunc = 'ucfirst';
        $report->columns['user-timecreated']->displayfunc = 'weekday';
        $report->columns['user-custom_field_'.$field->id]->displayfunc = 'userfield_textarea';

        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);
        $row = $records[$user->id];

        $this->assertInstanceOf('stdClass', $row);
        $processed = $report->src->process_data_row($row, 'html', $report);

        $this->assertSame((int)$row->user_id, $processed[0]);
        $this->assertSame('January', $processed[1]);
        $this->assertSame('10 Jan 2013', $processed[2]);
        $this->assertStringStartsWith('10 Jan 2013 at ', $processed[3]);
        $this->assertSame('Řízek', $processed[4]);
        $this->assertSame('Monday', $processed[5]);
        $this->assertSame('Some html <strong>text</strong>', $processed[6]);

        $processed = $report->src->process_data_row($row, 'pdf', $report);

        $this->assertSame((int)$row->user_id, $processed[0]);
        $this->assertSame('January', $processed[1]);
        $this->assertSame('10 Jan 2013', $processed[2]);
        $this->assertStringStartsWith('10 Jan 2013 at ', $processed[3]);
        $this->assertSame('Řízek', $processed[4]);
        $this->assertSame('Monday', $processed[5]);
        $this->assertSame('Some html TEXT', $processed[6]);

        $processed = $report->src->process_data_row($row, 'excel', $report);

        $this->assertSame((int)$row->user_id, $processed[0]);
        $this->assertSame('January', $processed[1]);
        $this->assertSame('date', $processed[2][0]);
        $this->assertSame('1357812000', $processed[2][1]);
        $this->assertInstanceOf('MoodleExcelFormat', $processed[2][2]);
        $this->assertSame('date', $processed[3][0]);
        $this->assertSame('1357812000', $processed[3][1]);
        $this->assertInstanceOf('MoodleExcelFormat', $processed[3][2]);
        $this->assertSame('Řízek', $processed[4]);
        $this->assertSame('Monday', $processed[5]);
        $this->assertSame('Some html TEXT', $processed[6]);

        $processed = $report->src->process_data_row($row, 'ods', $report);

        $this->assertSame((int)$row->user_id, $processed[0]);
        $this->assertSame('January', $processed[1]);
        $this->assertSame('date', $processed[2][0]);
        $this->assertSame('1357812000', $processed[2][1]);
        $this->assertInstanceOf('MoodleODSFormat', $processed[2][2]);
        $this->assertSame('date', $processed[3][0]);
        $this->assertSame('1357812000', $processed[3][1]);
        $this->assertInstanceOf('MoodleODSFormat', $processed[3][2]);
        $this->assertSame('Řízek', $processed[4]);
        $this->assertSame('Monday', $processed[5]);
        $this->assertSame('Some html TEXT', $processed[6]);

        // Now try the custom fields in course.

        $course = $this->getDataGenerator()->create_course(array('summary' => 'Some summary', 'summaryformat' => FORMAT_MOODLE));

        $s = 1;
        $filefield = new stdClass();
        $filefield->fullname = "File field";
        $filefield->shortname = "filefield";
        $filefield->datatype = 'file';
        $filefield->sortorder = $s++;
        $filefield->hidden = 0;
        $filefield->locked = 0;
        $filefield->required = 0;
        $filefield->forceunique = 0;
        $filefield->id = $DB->insert_record('course_info_field', $filefield);

        $f = new stdClass();
        $f->fieldid = $filefield->id;
        $f->courseid = $course->id;
        $f->data = '';
        $f->id = $DB->insert_record('course_info_data', $f);
        $f->data = $f->id;
        $DB->update_record('course_info_data', $f);

        $fs = get_file_storage();
        $file = array('contextid' => $syscontext->id, 'component' => 'totara_customfield', 'filearea' => 'course_filemgr',
                        'itemid' => $f->data, 'filepath' => '/', 'filename' => 'readme.txt');
        $fileurl = 'https://www.example.com/moodle/pluginfile.php/'.$syscontext->id.'/totara_customfield/course_filemgr/'.
                        $f->data.'/readme.txt';
        $fs->create_file_from_string($file, 'hi!');

        $multiselect = new stdClass();
        $multiselect->fullname = "Multiselect field";
        $multiselect->shortname = "multiselectfield";
        $multiselect->datatype = 'multiselect';
        $multiselect->sortorder = $s++;
        $multiselect->hidden = 0;
        $multiselect->locked = 0;
        $multiselect->required = 0;
        $multiselect->forceunique = 0;
        $multiselect->param1 = '[{"option":"volba1","icon":"business-modelling","default":"0","delete":0},';
        $multiselect->param1 .= '{"option":"volba2","icon":"developing-strengths-into-talents","default":"0","delete":0}]';
        $multiselect->id = $DB->insert_record('course_info_field', $multiselect);

        $mf = new stdClass();
        $mf->fieldid = $multiselect->id;
        $mf->courseid = $course->id;
        $mf->data = '{"9efde54a5d26d0f4c0d91aa6607c56b4":{"option":"volba1","icon":"business-modelling","default":1,"delete":0}}';
        $mf->id = $DB->insert_record('course_info_data', $mf);

        $dp = new stdClass();
        $dp->dataid = $mf->id;
        $dp->value = '9efde54a5d26d0f4c0d91aa6607c56b4';
        $DB->insert_record('course_info_data_param', $dp);

        $areafield = new stdClass();
        $areafield->fullname = "Area field";
        $areafield->shortname = "areafield";
        $areafield->datatype = 'textarea';
        $areafield->sortorder = $s++;
        $areafield->hidden = 0;
        $areafield->locked = 0;
        $areafield->required = 0;
        $areafield->forceunique = 0;
        $areafield->param1 = 30;
        $areafield->param1 = 10;
        $areafield->id = $DB->insert_record('course_info_field', $areafield);

        $f = new stdClass();
        $f->fieldid = $areafield->id;
        $f->courseid = $course->id;
        $f->data = 'Some html <strong>text</strong><script></script>';;
        $f->id = $DB->insert_record('course_info_data', $f);

        $rid = $this->create_report('courses', 'Test courses report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        $this->add_column($report, 'course', 'summary', null, null, null, 0);
        $this->add_column($report, 'course', 'custom_field_'.$filefield->id, null, null, null, 0);
        $this->add_column($report, 'course', 'custom_field_'.$multiselect->id.'_text', null, null, null, 0);
        $this->add_column($report, 'course', 'custom_field_'.$multiselect->id.'_icon', null, null, null, 0);
        $this->add_column($report, 'course', 'custom_field_'.$areafield->id, null, null, null, 0);

        $report = reportbuilder::create($rid);

        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
        $row = reset($records);

        $processed = $report->src->process_data_row($row, 'html', $report);

        $this->assertSame('<div class="text_to_html">Some summary</div>', $processed[0]);

        $theme = $PAGE->theme->name;
        $fileicon = $OUTPUT->flex_icon('core|f/text', array('alt' => get_string('file')));
        $fileiconlink = html_writer::link($fileurl, $fileicon . 'readme.txt', array('class' => 'icon'));

        // The markup is pretty fluid, there is no way to guess the exact chars, sorry.
        $this->assertContains($fileurl, $processed[1]);
        $this->assertContains($fileicon, $processed[1]);

        $this->assertSame('volba1', $processed[2]);
        $this->assertSame('<img src="https://www.example.com/moodle/theme/image.php/_s/' . $theme . '/totara_core/1/courseicons/business-modelling" id="icon_preview" class="course_icon" alt="volba1" title="volba1" />', $processed[3]);
        $this->assertSame('Some html <strong>text</strong><script></script>', $processed[4]);

        $processed = $report->src->process_data_row($row, 'pdf', $report);

        $this->assertSame('Some summary', $processed[0]);
        $this->assertSame('readme.txt', $processed[1]);
        $this->assertSame('volba1', $processed[2]);
        $this->assertSame('volba1', $processed[3]);
        $this->assertSame('Some html TEXT', $processed[4]);
    }

    public function test_completion_percent() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        set_config('enablecompletion', 1);

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to view all reports.

        $user1 = $this->getDataGenerator()->create_user(array('institution' => 'a'));
        $user2 = $this->getDataGenerator()->create_user(array('institution' => 'a'));

        $course = $this->getDataGenerator()->create_course(
            array('enablecompletion' => COMPLETION_ENABLED, 'completionstartonenrol' => 1));
        $page = $this->getDataGenerator()->create_module('page',
            array('course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC , 'completionview' => COMPLETION_VIEW_REQUIRED));
        $cm = get_coursemodule_from_instance('page', $page->id);

        $this->getDataGenerator()->get_plugin_generator('core_completion')->set_activity_completion($course->id, array($page));

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $user1->id);

        $this->assertTrue($completion->is_course_complete($user1->id));
        $this->assertFalse($completion->is_course_complete($user2->id));

        $rid = $this->create_report('course_completion', 'Test courses report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        $this->add_column($report, 'user', 'institution', null, null, null, 0);
        $this->add_column($report, 'course_completion', 'iscomplete', null, 'percent', null, 0);

        $report = reportbuilder::create($rid);

        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
        $row = reset($records);

        $this->assertSame('a', $row->user_institution);
        $this->assertEquals(50, $row->course_completion_iscomplete, '', 0.0001);

        $processed = $report->src->process_data_row($row, 'html', $report);

        $this->assertSame('a', $processed[0]);
        $this->assertSame('50.0%', $processed[1]);
    }

    function test_avg() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        set_config('enablecompletion', 1);

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to view all reports.

        $user1 = $this->getDataGenerator()->create_user(array('institution' => 'a'));
        $user2 = $this->getDataGenerator()->create_user(array('institution' => 'a'));

        $course = $this->getDataGenerator()->create_course(
            array('enablecompletion' => COMPLETION_ENABLED, 'completionstartonenrol' => 1));
        $page = $this->getDataGenerator()->create_module('page',
            array('course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC , 'completionview' => COMPLETION_VIEW_REQUIRED));
        $cm = get_coursemodule_from_instance('page', $page->id);

        $this->getDataGenerator()->get_plugin_generator('core_completion')->set_activity_completion($course->id, array($page));

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $user1->id);

        $this->assertTrue($completion->is_course_complete($user1->id));
        $this->assertFalse($completion->is_course_complete($user2->id));

        $rid = $this->create_report('course_completion', 'Test courses report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        $this->add_column($report, 'user', 'institution', null, null, null, 0);
        $this->add_column($report, 'course_completion', 'iscomplete', null, 'avg', null, 0);

        $report = reportbuilder::create($rid);

        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
        $row = reset($records);

        $this->assertSame('a', $row->user_institution);
        $this->assertEquals(0.50000000000000000000, $row->course_completion_iscomplete, '', 0.0001);

        $processed = $report->src->process_data_row($row, 'html', $report);

        $this->assertSame('a', $processed[0]);
        $this->assertSame('0.50', $processed[1]);
    }

    function test_yes_or_no() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function with NULL as value.
        $message = \totara_reportbuilder\rb\display\yes_or_no::display(NULL, $format, $row, $column, $report);
        $this->assertEquals('', $message, 'Failing that NUll value matches empty string in yes_or_no display function');

        // Testing display function with 1 as value.
        $message = \totara_reportbuilder\rb\display\yes_or_no::display(1, $format, $row, $column, $report);
        $this->assertEquals('Yes', $message, 'Failing that 1 value matches "Yes" in yes_or_no display function');

        // Testing display function with 0 as value.
        $message = \totara_reportbuilder\rb\display\yes_or_no::display(0, $format, $row, $column, $report);
        $this->assertEquals('No', $message, 'Failing that 0 value matches "No" in yes_or_no display function');

        // Testing display function with "1" string as value.
        $message = \totara_reportbuilder\rb\display\yes_or_no::display("1", $format, $row, $column, $report);
        $this->assertEquals('Yes', $message, 'Failing that "1" value matches "Yes" in yes_or_no display function');

        // Testing display function with "0" string as value.
        $message = \totara_reportbuilder\rb\display\yes_or_no::display("0", $format, $row, $column, $report);
        $this->assertEquals('No', $message, 'Failing that "0" value matches "No" in yes_or_no display function');
    }

    function test_nice_time() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\nice_time::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('11:25', $display);

        $display = \totara_reportbuilder\rb\display\nice_time::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('11:25', $display);

        $display = \totara_reportbuilder\rb\display\nice_time::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);
    }

    function test_nice_datetime_seconds() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\nice_datetime_seconds::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 Dec 2017 at 11:25:15', $display);

        $display = \totara_reportbuilder\rb\display\nice_datetime_seconds::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 Dec 2017 at 11:25:15', $display);

        $display = \totara_reportbuilder\rb\display\nice_datetime_seconds::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);
    }

    function test_nice_datetime_in_timezone() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();
        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = '	Australia/Perth';

        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Unknown Timezone', $display);
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Unknown Timezone', $display);
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $row->$extrafieldrow = 'Australia/Perth';
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    function test_delimitedlist_date_in_timezone() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();
        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = '	Australia/Perth';

        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Unknown Timezone', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Unknown Timezone', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $row->$extrafieldrow = 'Australia/Perth';
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_date_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    function test_delimitedlist_datetime_in_timezone() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();
        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = '	Australia/Perth';

        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Unknown Timezone', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Unknown Timezone', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $row->$extrafieldrow = 'Australia/Perth';
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\delimitedlist_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    function test_nice_two_datetime_in_timezone() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        //
        // Two dates.
        //

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true, 'finishdate' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'finishdate');
        $row->$extrafieldrow = 1514345115 + 86400;

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = '	Australia/Perth';

        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 11:25 AM Australia/Perth', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland to 28 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 4:25 PM Pacific/Auckland', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Australia/Perth';
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 11:25 AM Australia/Perth', $display);

        // Reset.
        $CFG->forcetimezone = '99';

        //
        // Start date only.
        //

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true, 'finishdate' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'finishdate');
        $row->$extrafieldrow = null;

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = '	Australia/Perth';

        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 4:25 PM Pacific/Auckland', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Australia/Perth';
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 11:25 AM Australia/Perth', $display);
        $display = \totara_reportbuilder\rb\display\nice_two_datetime_in_timezone::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    function test_round2() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\round2::display(2.02, $format, $row, $column, $report);
        $this->assertEquals('2.02', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(2.22, $format, $row, $column, $report);
        $this->assertEquals('2.22', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(2.2, $format, $row, $column, $report);
        $this->assertEquals('2.20', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(0.22, $format, $row, $column, $report);
        $this->assertEquals('0.22', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(0.2, $format, $row, $column, $report);
        $this->assertEquals('0.20', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(0.0, $format, $row, $column, $report);
        $this->assertEquals('0.00', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(0, $format, $row, $column, $report);
        $this->assertEquals('0.00', $display);

        $display = \totara_reportbuilder\rb\display\round2::display('', $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(null, $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $display = \totara_reportbuilder\rb\display\round2::display('blah', $format, $row, $column, $report);
        $this->assertEquals('0.00', $display);

        $display = \totara_reportbuilder\rb\display\round2::display(0x02, $format, $row, $column, $report);
        $this->assertEquals('2.00', $display);
    }

    function test_percent() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\percent::display(2.02, $format, $row, $column, $report);
        $this->assertEquals('2.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(2.22, $format, $row, $column, $report);
        $this->assertEquals('2.2%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(2.2, $format, $row, $column, $report);
        $this->assertEquals('2.2%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(0.22, $format, $row, $column, $report);
        $this->assertEquals('0.2%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(0.2, $format, $row, $column, $report);
        $this->assertEquals('0.2%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(0.00, $format, $row, $column, $report);
        $this->assertEquals('0.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(0, $format, $row, $column, $report);
        $this->assertEquals('0.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(2, $format, $row, $column, $report);
        $this->assertEquals('2.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(99, $format, $row, $column, $report);
        $this->assertEquals('99.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(99.9, $format, $row, $column, $report);
        $this->assertEquals('99.9%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(99.99, $format, $row, $column, $report);
        $this->assertEquals('100.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(100.01, $format, $row, $column, $report);
        $this->assertEquals('100.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(100.1, $format, $row, $column, $report);
        $this->assertEquals('100.1%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(100.19, $format, $row, $column, $report);
        $this->assertEquals('100.2%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display('', $format, $row, $column, $report);
        $this->assertEquals('0.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(null, $format, $row, $column, $report);
        $this->assertEquals('-', $display);

        $display = \totara_reportbuilder\rb\display\percent::display('blah', $format, $row, $column, $report);
        $this->assertEquals('0.0%', $display);

        $display = \totara_reportbuilder\rb\display\percent::display(0x02, $format, $row, $column, $report);
        $this->assertEquals('2.0%', $display);
    }

    function test_delimitedlist_multi_to_newline() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $d = $report->src->get_uniquedelimiter();
        $object = json_encode([['option' => 'one'], ['option' => 'two']]);

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\delimitedlist_multi_to_newline::display($object, $format, $row, $column, $report);
        $this->assertEquals('one, two', $display);

        $display = \totara_reportbuilder\rb\display\delimitedlist_multi_to_newline::display($object.$d.$object, $format, $row, $column, $report);
        $this->assertEquals("one, two\none, two", $display);

        $display = \totara_reportbuilder\rb\display\delimitedlist_multi_to_newline::display($object.$d.$object.$d.$object, $format, $row, $column, $report);
        $this->assertEquals("one, two\none, two\none, two", $display);

    }

    function test_delimitedlist_url_to_newline() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $d = $report->src->get_uniquedelimiter();
        $object = json_encode(['text' => 'One', 'url' => '#1']);

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\delimitedlist_url_to_newline::display($object, $format, $row, $column, $report);
        $this->assertEquals("<a href=\"#1\">One</a>", $display);

        $display = \totara_reportbuilder\rb\display\delimitedlist_url_to_newline::display($object.$d.$object, $format, $row, $column, $report);
        $this->assertEquals("<a href=\"#1\">One</a>\n<a href=\"#1\">One</a>", $display);

        $display = \totara_reportbuilder\rb\display\delimitedlist_url_to_newline::display($object.$d.$object.$d.$object, $format, $row, $column, $report);
        $this->assertEquals("<a href=\"#1\">One</a>\n<a href=\"#1\">One</a>\n<a href=\"#1\">One</a>", $display);
    }

    function test_orderedlist_to_newline() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $d = $report->src->get_uniquedelimiter();

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one{$d}two{$d}three", $format, $row, $column, $report);
        $this->assertEquals("one\ntwo\nthree", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one{$d}{$d}three", $format, $row, $column, $report);
        $this->assertEquals("one\n-\nthree", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one", $format, $row, $column, $report);
        $this->assertEquals("one", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one,two,three", $format, $row, $column, $report);
        $this->assertEquals("one,two,three", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one{$d} {$d}three", $format, $row, $column, $report);
        $this->assertEquals("one\n-\nthree", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one{$d}    {$d}three", $format, $row, $column, $report);
        $this->assertEquals("one\n-\nthree", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one{$d}0{$d}three", $format, $row, $column, $report);
        $this->assertEquals("one\n-\nthree", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display("one{$d}0000{$d}three", $format, $row, $column, $report);
        $this->assertEquals("one\n0000\nthree", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display(",", $format, $row, $column, $report);
        $this->assertEquals(",", $display);

        $display = \totara_reportbuilder\rb\display\orderedlist_to_newline::display(",{$d},,", $format, $row, $column, $report);
        $this->assertEquals(",\n,,", $display);
    }

    function test_list_to_newline() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function.
        $display = \totara_reportbuilder\rb\display\list_to_newline::display("one, two, three", $format, $row, $column, $report);
        $this->assertEquals("one\ntwo\nthree", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display("one, , three", $format, $row, $column, $report);
        $this->assertEquals("one\n-\nthree", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display("one", $format, $row, $column, $report);
        $this->assertEquals("one", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display("one\ntwo", $format, $row, $column, $report);
        $this->assertEquals("one\ntwo", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display("one,two,three", $format, $row, $column, $report);
        $this->assertEquals("one,two,three", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display("one, 0, three", $format, $row, $column, $report);
        $this->assertEquals("one\n-\nthree", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display(",", $format, $row, $column, $report);
        $this->assertEquals(",", $display);

        $display = \totara_reportbuilder\rb\display\list_to_newline::display(",, ,,", $format, $row, $column, $report);
        $this->assertEquals(",\n,,", $display);

    }

}
