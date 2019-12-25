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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara
 * @subpackage question
 */

global $CFG;
require_once($CFG->dirroot.'/totara/question/tests/question_testcase.php');
require_once($CFG->dirroot.'/totara/question/field/aggregate.class.php');

class question_aggregate_test extends totara_question_testcase {
    public function test_calculate_aggregate() {
        $storage = new question_storage_mock(1);
        $quest = new question_aggregate($storage);
        $quest->param2 = 1;
        $quest->param3 = 1;

        $noanswer = array();
        $noanswer['data0'] = null;
        $noanswer['data0_default'] = 0;
        $noanswer['data1'] = null;
        $noanswer['data1_default'] = 11;
        $noanswer['data2'] = null;
        $noanswer['data2_default'] = 12;
        $noanswer['data3'] = null;
        $noanswer['data3_default'] = 13;
        $noanswer['data4'] = null;
        $noanswer['data4_default'] = 14;

        $quest->param4 = array('usedefault' => false, 'usezero' => false);
        $out = $quest->calculate_aggregate($noanswer);
        $this->assertEquals('Not yet answered', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => false);
        $out = $quest->calculate_aggregate($noanswer);
        $this->assertEquals('Not yet answered', $out);

        $quest->param4 = array('usedefault' => false, 'usezero' => true);
        $out = $quest->calculate_aggregate($noanswer);
        $this->assertEquals('Not yet answered', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => true);
        $out = $quest->calculate_aggregate($noanswer);
        $this->assertEquals('Not yet answered', $out);

        $oneanswer = array();
        $oneanswer['data0'] = null;
        $oneanswer['data0_default'] = 0;
        $oneanswer['data1'] = 1;
        $oneanswer['data1_default'] = 11;
        $oneanswer['data2'] = 0;
        $oneanswer['data2_default'] = 12;
        $oneanswer['data3'] = null;
        $oneanswer['data3_default'] = 13;
        $oneanswer['data4'] = null;
        $oneanswer['data4_default'] = 14;

        $quest->param4 = array('usedefault' => false, 'usezero' => false);
        $out = $quest->calculate_aggregate($oneanswer);
        $this->assertEquals(' Average score: 1<br /> Median score: 1', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => false);
        $out = $quest->calculate_aggregate($oneanswer);
        $this->assertEquals(' Average score: 9.33<br /> Median score: 13', $out);

        $quest->param4 = array('usedefault' => false, 'usezero' => true);
        $out = $quest->calculate_aggregate($oneanswer);
        $this->assertEquals(' Average score: 0.5<br /> Median score: 0.5', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => true);
        $out = $quest->calculate_aggregate($oneanswer);
        $this->assertEquals(' Average score: 5.6<br /> Median score: 1', $out);

        $zeroanswer = array();
        $zeroanswer['data0'] = 0;
        $zeroanswer['data0_default'] = 0;
        $zeroanswer['data1'] = 1;
        $zeroanswer['data1_default'] = 0;
        $zeroanswer['data2'] = null;
        $zeroanswer['data2_default'] = 2;
        $zeroanswer['data3'] = null;
        $zeroanswer['data3_default'] = 0;
        $zeroanswer['data4'] = null;
        $zeroanswer['data4_default'] = 0;

        $quest->param4 = array('usedefault' => false, 'usezero' => false);
        $out = $quest->calculate_aggregate($zeroanswer);
        $this->assertEquals(' Average score: 1<br /> Median score: 1', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => false);
        $out = $quest->calculate_aggregate($zeroanswer);
        $this->assertEquals(' Average score: 1.5<br /> Median score: 1.5', $out);

        $quest->param4 = array('usedefault' => false, 'usezero' => true);
        $out = $quest->calculate_aggregate($zeroanswer);
        $this->assertEquals(' Average score: 0.5<br /> Median score: 0.5', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => true);
        $out = $quest->calculate_aggregate($zeroanswer);
        $this->assertEquals(' Average score: 0.6<br /> Median score: 0', $out);

        $allanswer = array();
        $allanswer['data1'] = 0;
        $allanswer['data1_default'] = 0;
        $allanswer['data2'] = 1;
        $allanswer['data2_default'] = 11;
        $allanswer['data3'] = 2;
        $allanswer['data3_default'] = 12;
        $allanswer['data4'] = 3;
        $allanswer['data4_default'] = 13;
        $allanswer['data5'] = 4;
        $allanswer['data5_default'] = 14;

        $quest->param4 = array('usedefault' => false, 'usezero' => false);
        $out = $quest->calculate_aggregate($allanswer);
        $this->assertEquals(' Average score: 2.5<br /> Median score: 2.5', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => false);
        $out = $quest->calculate_aggregate($allanswer);
        $this->assertEquals(' Average score: 2.5<br /> Median score: 2.5', $out);

        $quest->param4 = array('usedefault' => false, 'usezero' => true);
        $out = $quest->calculate_aggregate($allanswer);
        $this->assertEquals(' Average score: 2<br /> Median score: 2', $out);

        $quest->param4 = array('usedefault' => true, 'usezero' => true);
        $out = $quest->calculate_aggregate($allanswer);
        $this->assertEquals(' Average score: 2<br /> Median score: 2', $out);
    }
}
