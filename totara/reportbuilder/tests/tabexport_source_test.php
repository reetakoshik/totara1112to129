<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
class totara_reportbuilder_tabexport_source_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_source() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to view all reports.

        $admin = get_admin();
        $guest = guest_user();
        $user = $this->getDataGenerator()->create_user();

        $expected = array();
        $expected[] = array('User ID', 'User First Name', 'User Last Name');
        $expected[] = array((int)$user->id, $user->firstname, $user->lastname);
        $expected[] = array((int)$admin->id, $admin->firstname, $admin->lastname);
        $expected[] = array((int)$guest->id, $guest->firstname, $guest->lastname);

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);
        // Sort the columns in predictable way - PostgreSQL may return random order otherwise.
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $report->_id));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $report->_id));

        $report = reportbuilder::create($rid);

        $source = new \totara_reportbuilder\tabexport_source($report);
        $source->set_format('html');

        $this->assertSame($report->fullname, $source->get_fullname());
        $this->assertSame('html', $source->get_format());
        $this->assertNull($source->get_svg_graph(100, 100));
        $this->assertSame(array(), $source->get_extra_information());

        $rows = array();
        $rows[] = $source->get_headings();

        foreach ($source as $row) {
            $rows[] = $row;
        }
        $source->close();

        $this->assertSame($expected, $rows);

        // Test cache info is printed.

        set_config('enablereportcaching', 1);
        $DB->execute('UPDATE {report_builder} SET cache = 1 WHERE id = ?', array($rid));
        reportbuilder_schedule_cache($rid, array('initschedule' => 1));
        $result = reportbuilder_generate_cache($rid);
        $this->assertTrue($result);

        $report = reportbuilder::create($rid);

        $source = new \totara_reportbuilder\tabexport_source($report);
        $source->set_format('html');

        $this->assertSame($report->fullname, $source->get_fullname());
        $this->assertSame('html', $source->get_format());
        $this->assertNull($source->get_svg_graph(100, 100));
        $extras = $source->get_extra_information();
        $this->assertCount(1, $extras);
        $this->assertStringStartsWith('Report data last updated:', $extras[0]);

        $rows = array();
        $rows[] = $source->get_headings();

        foreach ($source as $row) {
            $rows[] = $row;
        }
        $source->close();

        $this->assertSame($expected, $rows);
    }
}
