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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for facetoface testcases.
 *
 * @package mod_facetoface
 */
abstract class mod_facetoface_facetoface_testcase extends advanced_testcase {

    public function assert_count_customfield_data(string $cftype, array $signupids, $expectedcountdata, $expectedcountparams) {
        global $DB;

        if (empty($signupids)) {
            $this->fail('Bad test data: Passed in empty array for signupids.');
        }

        // Count *_info_data records.
        list($sqlin, $paramin) = $DB->get_in_or_equal($signupids);
        $sqldata = "SELECT id FROM {facetoface_{$cftype}_info_data} WHERE facetoface{$cftype}id ";
        $cfdata = $DB->get_records_sql($sqldata . $sqlin, $paramin);
        $this->assertCount($expectedcountdata, $cfdata);

        if (empty($cfdata)) {
            if ($expectedcountparams !== 0) {
                $this->fail('Bad assertion. Cannot have customfield data params without customfield data.');
            }
            return;
        }

        // Count *_info_data_param records.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($cfdata));
        $sqlparams = "SELECT id FROM {facetoface_{$cftype}_info_data_param} WHERE dataid ";
        $cfparams = $DB->get_records_sql($sqlparams . $sqlin, $paramin);
        $this->assertCount($expectedcountparams, $cfparams);
    }

    public function assert_count_signups_status($sessionid, $expectedcount) {
        global $DB;

        $this->assertEquals($expectedcount, $DB->count_records_select(
            'facetoface_signups_status',
            "signupid IN (SELECT id FROM {facetoface_signups} WHERE sessionid = :sessionid)",
            array('sessionid' => $sessionid)));
    }
}