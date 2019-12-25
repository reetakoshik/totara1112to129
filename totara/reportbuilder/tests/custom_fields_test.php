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
class totara_reportbuilder_custom_fields_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_profile_field_defaults() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->assertCount(0, $DB->get_records('user_info_category'));
        $this->assertCount(0, $DB->get_records('user_info_field'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field1 = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => "xx\nyy\nzz", 'defaultdata' => 'yy'));
        $field2 = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => "aa\nbb\ncc", 'defaultdata' => ''));

        $field3 = $generator->create_custom_profile_field(array('datatype' => 'text', 'defaultdata' => 'abc'));
        $field4 = $generator->create_custom_profile_field(array('datatype' => 'text', 'defaultdata' => ''));

        $field5 = $generator->create_custom_profile_field(array('datatype' => 'checkbox', 'defaultdata' => '1'));
        $field6 = $generator->create_custom_profile_field(array('datatype' => 'checkbox', 'defaultdata' => '0'));

        $this->set_profile_field_value($user1, $field1, 'xx');
        $this->set_profile_field_value($user1, $field2, 'bb');
        $this->set_profile_field_value($user1, $field3, 'xyz');
        $this->set_profile_field_value($user1, $field4, 'opk');
        $this->set_profile_field_value($user1, $field5, '1');
        $this->set_profile_field_value($user1, $field6, '1');

        $this->set_profile_field_value($user2, $field1, '');
        $this->set_profile_field_value($user2, $field2, '');
        $this->set_profile_field_value($user2, $field3, '');
        $this->set_profile_field_value($user2, $field4, '');
        $this->set_profile_field_value($user2, $field5, '');
        $this->set_profile_field_value($user2, $field6, '');

        $rid = $this->create_report('user', 'Test user report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field1->id, null, null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field2->id, null, null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field3->id, null, null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field4->id, null, null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field5->id, null, null, null, 0);
        $this->add_column($report, 'user', 'custom_field_'.$field6->id, null, null, null, 0);

        $report = reportbuilder::create($rid);
        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records); // Guest and admin are extra.
        $this->assertSame('xx', $records[$user1->id]->{'user_custom_field_'.$field1->id});
        $this->assertSame('bb', $records[$user1->id]->{'user_custom_field_'.$field2->id});
        $this->assertSame('xyz', $records[$user1->id]->{'user_custom_field_'.$field3->id});
        $this->assertSame('opk', $records[$user1->id]->{'user_custom_field_'.$field4->id});
        $this->assertSame('1', $records[$user1->id]->{'user_custom_field_'.$field5->id});
        $this->assertSame('1', $records[$user1->id]->{'user_custom_field_'.$field6->id});

        $this->assertSame('', $records[$user2->id]->{'user_custom_field_'.$field1->id});
        $this->assertSame('', $records[$user2->id]->{'user_custom_field_'.$field2->id});
        $this->assertSame('', $records[$user2->id]->{'user_custom_field_'.$field3->id});
        $this->assertSame('', $records[$user2->id]->{'user_custom_field_'.$field4->id});
        $this->assertSame('1', $records[$user2->id]->{'user_custom_field_'.$field5->id});
        $this->assertSame('0', $records[$user2->id]->{'user_custom_field_'.$field6->id});

        $this->assertSame('yy', $records[$user3->id]->{'user_custom_field_'.$field1->id});
        $this->assertSame(null, $records[$user3->id]->{'user_custom_field_'.$field2->id});
        $this->assertSame('abc', $records[$user3->id]->{'user_custom_field_'.$field3->id});
        $this->assertSame(null, $records[$user3->id]->{'user_custom_field_'.$field4->id});
        $this->assertSame('1', $records[$user3->id]->{'user_custom_field_'.$field5->id});
        $this->assertSame('0', $records[$user3->id]->{'user_custom_field_'.$field6->id});
    }

    protected function set_profile_field_value($user, $field, $data, $dataformat = 0) {
        global $DB;

        $record = new stdClass();
        $record->fieldid = $field->id;
        $record->userid = $user->id;
        $record->data = $data;
        $record->dataformat = $dataformat;

        $DB->insert_record('user_info_data', $record);
    }
}
