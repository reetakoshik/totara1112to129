<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test our webservice modifications.
 */
class totara_core_webservice_testcase extends advanced_testcase {
    public function test_webservice_function_called() {
        $this->resetAfterTest();
        $function = 'abc';

        $params = array(
            'other' => array(
                'function' => $function
            )
        );
        $event = \core\event\webservice_function_called::create($params);
        $event->set_legacy_logdata(array(SITEID, 'webservice', $function . ' 127.0.0.1' , 0, 2));
        $event->trigger();

        $event2 = \core\event\webservice_function_called::create_from_data($function);
        $event2->set_legacy_logdata(array(SITEID, 'webservice', $function . ' 127.0.0.1' , 0, 2));
        $event2->trigger();

        // List of domain specific fields for equality check. Non domain fields
        // like event IDs and timestamps should not be compared.
        $fields = [
            'eventname',
            'component',
            'action',
            'target',
            'objecttable',
            'objectid',
            'crud',
            'edulevel',
            'contextid',
            'contextlevel',
            'contextinstanceid',
            'userid',
            'courseid',
            'relateduserid',
            'anonymous',
            'other'
        ];

        $data1 = $event->get_data();
        $data2 = $event2->get_data();
        foreach ($fields as $field) {
            $this->assertSame($data1[$field], $data2[$field]);
        }
    }
}
