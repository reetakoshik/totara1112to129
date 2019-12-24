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
 * @package totara_core
 */

use \totara_core\jsend;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests jsend protocol abstraction class.
 */
class totara_core_jsend_testcase extends advanced_testcase {
    public function test_request_fake() {
        $url = 'http://www.example.com/auth/connect/sep_services.php';
        $data = array(
            array('status' => 'success', 'data' => array('666')),
            array('status' => 'success', 'data' => array('777')),
        );

        jsend::set_phpunit_testdata($data);
        $this->assertSame($data, jsend::get_phpunit_testdata());

        jsend::request($url, array());
        array_shift($data);
        $this->assertSame($data, jsend::get_phpunit_testdata());

        jsend::request($url, array());
        array_shift($data);
        $this->assertSame($data, jsend::get_phpunit_testdata());

        jsend::request($url, array());
        $this->assertSame(array(), jsend::get_phpunit_testdata());

        jsend::request($url, array());
        $this->assertSame(array(), jsend::get_phpunit_testdata());

        jsend::set_phpunit_testdata(null);
        $this->assertSame(null, jsend::get_phpunit_testdata());

        $data = array('status' => 'success', 'data' => array('aaa' => 'bbb'));
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame($data, $result);

        $data2 = array('status' => 'success', 'data' => (object)array('aaa' => 'bbb'));
        jsend::set_phpunit_testdata(array($data2));
        $result = jsend::request($url, array());
        $this->assertSame($data, $result);

        $data = array('status' => 'success', 'xxx' => 'ddd');
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame(array('status' => 'success', 'data' => null), $result);

        $data = array('status' => 'error', 'message' => 'xxxx');
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame($data, $result);

        $data = array('status' => 'error', 'xmessage' => 'xxxx');
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame(array('status' => 'error', 'message' => 'unknown error'), $result);

        $data = array('status' => 'fail', 'data' => array('x' => 'yyyy'));
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame($data, $result);

        $data = array('status' => 'fail', 'xdata' => 'xx');
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame(array('status' => 'fail', 'data' => null), $result);

        $data = array('status' => 'abc');
        jsend::set_phpunit_testdata(array($data));
        $result = jsend::request($url, array());
        $this->assertSame(array('status' => 'error', 'message' => 'Remote data request failed - invalid response format.'), $result);

        jsend::set_phpunit_testdata(array('xxxx'));
        $result = jsend::request($url, array());
        $this->assertSame(array('status' => 'error', 'message' => 'Remote data request failed - invalid response format.'), $result);

        jsend::set_phpunit_testdata(array(''));
        $result = jsend::request($url, array());
        $this->assertSame(array('status' => 'error', 'message' => 'Remote data request failed - invalid response format.'), $result);
    }

    public function test_request_real() {
        $testurl = $this->getExternalTestFileUrl('/totara/test_jsend.php');

        $expected = array(
            'status' => 'success',
            'data' => array('absc' => 'def'),
        );
        $result = jsend::request($testurl, array('test' => 'test1'));
        $this->assertSame($expected, $result);

        $expected = array(
            'status' => 'success',
            'data' => null,
        );
        $result = jsend::request($testurl, array('test' => 'test2'));
        $this->assertSame($expected, $result);

        $expected = array(
            'status' => 'fail',
            'data' => array('absc' => 'def'),
        );
        $result = jsend::request($testurl, array('test' => 'test3'));
        $this->assertSame($expected, $result);

        $expected = array(
            'status' => 'fail',
            'data' => null,
        );
        $result = jsend::request($testurl, array('test' => 'test4'));
        $this->assertSame($expected, $result);

        $expected = array(
            'status' => 'error',
            'message' => 'some error',
        );
        $result = jsend::request($testurl, array('test' => 'test5'));
        $this->assertSame($expected, $result);

        $expected = array(
            'status' => 'error',
            'message' => 'some other error',
            'data' => array('xx' => 'yy'),
            'code' => 'some code',
        );
        $result = jsend::request($testurl, array('test' => 'test6'));
        $this->assertSame($expected, $result);
    }

    public function test_send_result() {
        ob_start();
        $data = array('status' => 'success', 'data' => array('aa' => '666'));
        jsend::send_result($data);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();

        ob_start();
        $data = array('status' => 'success', 'xdata' => 'zzz');
        jsend::send_result($data);
        $debugging = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertCount(2, $debugging);
        $this->assertSame('invalid JSend result, missing data key in success result', $debugging[0]->message);
        $this->assertSame('invalid JSend result, \'xdata\' is not a valid result index', $debugging[1]->message);
        $data = array('status' => 'success', 'data' => null);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();

        ob_start();
        $data = array('status' => 'error', 'message' => 'some error');
        jsend::send_result($data);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();

        ob_start();
        $data = array('status' => 'error', 'xmessage' => 'some error');
        jsend::send_result($data);
        $debugging = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertCount(2, $debugging);
        $this->assertSame('invalid JSend result, missing message key in error result', $debugging[0]->message);
        $this->assertSame('invalid JSend result, \'xmessage\' is not a valid result index', $debugging[1]->message);
        $data = array('status' => 'error', 'message' => 'unknown error');
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();

        ob_start();
        $data = array('status' => 'fail', 'data' => array('666'));
        jsend::send_result($data);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();

        ob_start();
        $data = array('status' => 'fail', 'xdata' => 'zzz');
        jsend::send_result($data);
        $debugging = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertCount(2, $debugging);
        $this->assertSame('invalid JSend result, missing data key in fail result', $debugging[0]->message);
        $this->assertSame('invalid JSend result, \'xdata\' is not a valid result index', $debugging[1]->message);
        $data = array('status' => 'fail', 'data' => null);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();
    }

    public function test_send_success() {
        ob_start();
        $data = array('status' => 'success', 'data' => array('666'));
        jsend::send_success($data['data']);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();

        ob_start();
        $data = array('status' => 'success', 'data' => null);
        jsend::send_success($data['data']);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();
    }

    public function test_send_error() {
        ob_start();
        $data = array('status' => 'error', 'message' => 'some error');
        jsend::send_error($data['message']);
        $this->assertSame(json_encode($data), ob_get_contents());
        ob_end_clean();
    }

    public function test_clean_data() {
        $data = array(
            'a' => 'grr',
            'b' => 10,
            'c' => 3.14,
            'd' => true,
            'e' => false,
            'f' => array('a', 'b', array('c', 'd', array())),
            true => 'x',
            false => 'y',
            1 => 'z',
        );
        $expected = $data;
        jsend::clean_result($data);
        $this->assertSame($expected, $data);

        $data = array(
            chr(130).'a'."\0" => chr(130).'grr'."\0",
            'b' => 10,
            'c' => 3.14,
            'd' => true,
            'e' => false,
            'f' => array(chr(130).'a'."\0", 'b', (object)array('c', 'd', array())),
            true => 'x',
            false => 'y',
            1 => 'z',
        );
        $expected = array(
            'b' => 10,
            'c' => 3.14,
            'd' => true,
            'e' => false,
            'f' => array('a', 'b', array('c', 'd', array())),
            true => 'x',
            false => 'y',
            1 => 'z',
            'a' => 'grr',
        );
        jsend::clean_result($data);
        $this->assertSame($expected, $data);
    }
}
