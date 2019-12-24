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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test referrer related code.
 */
class totara_core_referrer_testcase extends advanced_testcase {
    public function test_get_local_referer() {
        global $CFG;
        $this->resetAfterTest();

        $CFG->wwwroot = 'http://www.example.com';

        unset($_SERVER['HTTP_REFERER']);
        $this->assertSame('', get_local_referer());

        $_SERVER['HTTP_REFERER'] = '';
        $this->assertSame('', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/';
        $this->assertSame('http://www.example.com/', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/test.php';
        $this->assertSame('http://www.example.com/test.php', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/test.php?id=1';
        $this->assertSame('http://www.example.com/test.php', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/test.php?id=1';
        $this->assertSame('http://www.example.com/test.php?id=1', get_local_referer(false));

        $_SERVER['HTTP_REFERER'] = 'https://www.example.com/';
        $this->assertSame('', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://example.com/';
        $this->assertSame('', get_local_referer());


        $CFG->wwwroot = 'http://www.example.com/mysite';

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/mysite/';
        $this->assertSame('http://www.example.com/mysite/', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/mysite/test.php';
        $this->assertSame('http://www.example.com/mysite/test.php', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/mysite/test.php?id=1';
        $this->assertSame('http://www.example.com/mysite/test.php', get_local_referer());

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/mysite/test.php?id=1';
        $this->assertSame('http://www.example.com/mysite/test.php?id=1', get_local_referer(false));

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/mysite';
        $this->assertSame('http://www.example.com/mysite', get_local_referer()); // This is technically wrong.

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/';
        $this->assertSame('', get_local_referer());

        // Totara: test default url

        $CFG->wwwroot = 'http://www.example.com';
        $defaulturl = $CFG->wwwroot . '/xx.php?zz=ww';

        unset($_SERVER['HTTP_REFERER']);
        $this->assertSame($defaulturl, get_local_referer(true, $defaulturl));

        unset($_SERVER['HTTP_REFERER']);
        $this->assertSame($defaulturl, get_local_referer(true, new moodle_url($defaulturl)));

        unset($_SERVER['HTTP_REFERER']);
        $this->assertSame($defaulturl, get_local_referer(false, $defaulturl));

        $_SERVER['HTTP_REFERER'] = '';
        $this->assertSame($defaulturl, get_local_referer(true, $defaulturl));

        $_SERVER['HTTP_REFERER'] = '';
        $this->assertSame($defaulturl, get_local_referer(false, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/';
        $this->assertSame('http://www.example.com/', get_local_referer(true, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/test.php';
        $this->assertSame('http://www.example.com/test.php', get_local_referer(true, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/test.php?id=1';
        $this->assertSame('http://www.example.com/test.php', get_local_referer(true, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'http://www.example.com/test.php?id=1';
        $this->assertSame('http://www.example.com/test.php?id=1', get_local_referer(false, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'https://www.example.com/';
        $this->assertSame($defaulturl, get_local_referer(true, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'http://example.com/';
        $this->assertSame($defaulturl, get_local_referer(true, $defaulturl));

        $_SERVER['HTTP_REFERER'] = 'http://example.com/';
        $this->assertSame($defaulturl, get_local_referer(false, $defaulturl));
    }

    public function test_get_referrer_policy() {
        global $CFG;
        $this->resetAfterTest();

        $this->assertEmpty($CFG->securereferrers);

        \core_useragent::instance(true, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.91 Safari/537.36');
        $this->assertFalse(core_useragent::is_ie());
        $this->assertTrue(core_useragent::is_chrome());

        $CFG->securereferrers = 0;
        $_GET = array();
        $this->assertNull(get_referrer_policy());
        $_GET = array('sesskey' => 'xxxx');
        $this->assertSame('no-referrer', get_referrer_policy());

        $CFG->securereferrers = 1;
        $_GET = array();
        $this->assertSame('strict-origin-when-cross-origin', get_referrer_policy());
        $_GET = array('sesskey' => 'xxxx');
        $this->assertSame('no-referrer', get_referrer_policy());

        // IE is not going to get fixed.

        \core_useragent::instance(true, 'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0)');
        $this->assertTrue(core_useragent::is_ie());

        $CFG->securereferrers = 0;
        $_GET = array();
        $this->assertNull(get_referrer_policy());
        $_GET = array('sesskey' => 'xxxx');
        $this->assertSame('never', get_referrer_policy());

        $CFG->securereferrers = 1;
        $_GET = array();
        $this->assertSame('strict-origin-when-cross-origin', get_referrer_policy());
        $_GET = array('sesskey' => 'xxxx');
        $this->assertSame('never', get_referrer_policy());

        \core_useragent::instance(true);
    }
}
