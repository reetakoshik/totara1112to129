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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Sort an array in a random order
 *
 * @param array $tosort Array of values to sort
 * @return array Array sorted in a random order
 */
function sort_random_order($tosort) {
    $sorted = array();

    $cnt = count($tosort);
    while ($cnt > 1) {
        $idx = rand(1, $cnt - 1) - 1;
        $sorted[] = $tosort[$idx];
        array_splice($tosort, $idx, 1);
        $cnt -= 1;
    }
    $sorted[] = $tosort[0];

    return $sorted;
}


/**
 * Tests the progressinfo_aggregate_some::aggregate method
 */
class progressinfo_aggregate_some_testcase extends advanced_testcase {

    /**
     * Test sort_random_order returns an array with all the element
     */
    public function test_sort_random_order() {
        $arr = array(1, 2, 3, 4, 5);
        $arr2 = sort_random_order($arr);

        $this->assertEquals(count($arr), count($arr2));
        foreach ($arr as $elem) {
            $this->assertTrue(in_array($elem, $arr2));
        }

        $arr = array(
            array('score' => 0,
                  'points' => 10),
            array('score' => 0.5,
                  'points' => 5),
            array('score' => 0.75,
                  'points' => 20),
            array('score' => 0.15,
                  'points' => 50),
            array('score' => 1,
                  'points' => 40)
            );
        $arr2 = sort_random_order($arr);

        $this->assertEquals(count($arr), count($arr2));
        foreach ($arr as $elem) {
            $this->assertTrue(in_array($elem, $arr2));
        }
    }


    /**
     * Tests prog_courseset_aggregate_some if required courses and required points are set to 0
     */
    public function test_nothing_to_achieve() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data(
            \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
            0, 0,
            array('requiredcourses' => 0,
                  'requiredpoints' => 0,
                  'totalcourses' => 0,
                  'totalpoints' => 0),
           '\totara_program\progress\progressinfo_aggregate_some');

        // Add some course infos without scores or customdata
        for ($i = 0; $i <= 4; $i++) {
            $progressinfo->add_criteria('course'.$i,
                \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
                1, $i * .25,
                array('coursepoints' => $i));
        }

        $verifyresult = array('weight' => 1, 'score' => 1);
        $result = \totara_program\progress\progressinfo_aggregate_some::aggregate($progressinfo);
        $this->assertEquals($verifyresult, $result);

    }

    /**
     * Tests prog_courseset_aggregate_some with min courses completed only
     */
    public function test_aggregate_mincourses() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data(
            \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
            0, 0,
            array('requiredcourses' => 3,
                  'requiredpoints' => 0,
                  'totalcourses' => 0,
                  'totalpoints' => 0),
           '\totara_program\progress\progressinfo_aggregate_some');

        // Add some course infos
        // We need some randomness in the data to exercise more code paths
        // but we need to know which scores are used
        $scores = sort_random_order(array(0, 0.15, 0.5, 0.75, 1));
        for ($i = 0; $i < 5; $i++) {
            $progressinfo->add_criteria('course'.$i,
                \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
                1, $scores[$i],
                array('coursepoints' => $i));
        }

        $result = \totara_program\progress\progressinfo_aggregate_some::aggregate($progressinfo);
        $this->assertEquals(1, $result['weight']);
        $this->assertEquals(0.75, $result['score'], '', 0.1);

        $verifycustom = array('requiredcourses' => 3,
                              'requiredpoints' => 0,
                              'totalcourses' => 2.25,
                              'totalpoints' => 0);
        $this->assertEquals($verifycustom, $progressinfo->get_customdata());
   }

    /**
     * Tests prog_courseset_aggregate_some with min score only
     */
    public function test_aggregate_minscore() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data(
            \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
            0, 0,
            array('requiredcourses' => 0,
                  'requiredpoints' => 75,
                  'totalcourses' => 0,
                  'totalpoints' => 0),
            '\totara_program\progress\progressinfo_aggregate_some');

        // Add some course infos
        // We need some randomness in the scores so that they are not yet sorted
        // but we need to know which scores are used
        $data = array(
            array('score' => 0,
                  'points' => 10),
            array('score' => 0.5,
                  'points' => 5),
            array('score' => 0.75,
                  'points' => 20),
            array('score' => 0.15,
                  'points' => 50),
            array('score' => 1,
                  'points' => 40)
            );
        $data = sort_random_order($data);

        for ($i = 0; $i < 5; $i++) {
            $progressinfo->add_criteria('course'.$i,
                \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
                1, $data[$i]['score'],
                array('coursepoints' => $data[$i]['points']));
        }

        $result = \totara_program\progress\progressinfo_aggregate_some::aggregate($progressinfo);
        $this->assertEquals(1, $result['weight']);
        $this->assertEquals(0.66, $result['score'], '', 0.01);

        $verifycustom = array('requiredcourses' => 0,
                              'requiredpoints' => 75,
                              'totalcourses' => 0,
                              'totalpoints' => 50.125);
        $customdata = $progressinfo->get_customdata();
        foreach ($verifycustom as $key => $value) {
            $this->assertEquals($value, $customdata[$key], '', 0.01);
        }
   }

  /**
    * Tests prog_courseset_aggregate_some with min courses completed and min score
    */

    public function test_aggregate_mincourseandscore() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data(
            \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
            0, 0,
            array('requiredcourses' => 3,
                  'requiredpoints' => 75,
                  'totalcourses' => 0,
                  'totalpoints' => 0),
           '\totara_program\progress\progressinfo_aggregate_some');

        // Add some course infos
        // We need some randomness in the scores so that they are not yet sorted
        // but we need to know which scores are used
        $data = array(
            array('score' => 0,
                  'points' => 10),
            array('score' => 0.5,
                  'points' => 5),
            array('score' => 0.75,
                  'points' => 20),
            array('score' => 0.15,
                  'points' => 50),
            array('score' => 1,
                  'points' => 40)
            );
        $data = sort_random_order($data);

        for ($i = 0; $i < 5; $i++) {
            $progressinfo->add_criteria('course'.$i,
                \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
                1, $data[$i]['score'],
                array('coursepoints' => $data[$i]['points']));
        }

        $result = \totara_program\progress\progressinfo_aggregate_some::aggregate($progressinfo);
        $this->assertEquals(1, $result['weight']);
        $this->assertEquals(0.66, $result['score'], '', 0.1);

        $verifycustom = array('requiredcourses' => 3,
                              'requiredpoints' => 75,
                              'totalcourses' => 2.25,
                              'totalpoints' => 50.125);
        $customdata = $progressinfo->get_customdata();
        foreach ($verifycustom as $key => $value) {
            $this->assertEquals($value, $customdata[$key], '', 0.01);
        }
   }
}