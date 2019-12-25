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
 * Tests of our upstream hacks and behaviour expected in Totara.
 */
class totara_core_weblib_testcase extends advanced_testcase {
    public function test_clean_text() {
        // Make sure that data-core-autoinitialise and data-core-autoinitialise-amd are
        // stripped from from HTML markup added by regular users.
        $html = '<div class="someclass" data-core-autoinitialise="true" data-core-autoinitialise-amd="mod_mymod/myelement" data-x-yyy="2">sometext</div>';
        $expected = '<div class="someclass">sometext</div>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));
    }

    public function test_purify_uri() {
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', true));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', true, true));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', false));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', false, true));

        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', true));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', true, true));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', false));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', false, true));

        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php'));
        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php', true));
        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php', true, false));
        $this->assertSame('', purify_uri('www.example.com/test.php', true, true));
        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php', false));
        $this->assertSame('', purify_uri('www.example.com/test.php', false, true));
        $this->assertSame('', purify_uri('www.example.com/test.php', false, true));

        // Blocking wrong schemas.

        $this->assertSame('ftp://www.example.com/test.txt', purify_uri('ftp://www.example.com/test.txt'));
        $this->assertSame('', purify_uri('ftp://www.example.com/test.txt', true));
        $this->assertSame('', purify_uri('ftp://www.example.com/test.txt', true, true));
        $this->assertSame('ftp://www.example.com/test.txt', purify_uri('ftp://www.example.com/test.txt', false));

        $this->assertSame('', purify_uri('test: test'));
        $this->assertSame('', purify_uri('test: test', true));
        $this->assertSame('', purify_uri('test: test', false));

        $this->assertSame('', purify_uri(' test: test'));
        $this->assertSame('', purify_uri(' test: test', true));
        $this->assertSame('', purify_uri(' test: test', false));

        $this->assertSame('', purify_uri(null));
        $this->assertSame('', purify_uri(null, true));
        $this->assertSame('', purify_uri(null, false));

        $this->assertSame('', purify_uri(''));
        $this->assertSame('', purify_uri('', true));
        $this->assertSame('', purify_uri('', false));

        $this->assertSame('', purify_uri('javascript:alert(1)'));
        $this->assertSame('', purify_uri('javascript:alert(1)', true));
        $this->assertSame('', purify_uri('javascript:alert(1)', false));

        $this->assertSame('', purify_uri('<javascript>'));
        $this->assertSame('', purify_uri('<javascript>', true));
        $this->assertSame('', purify_uri('<javascript>', false));

        // Automatic fixing.

        $this->assertSame('test%20test', purify_uri('test test'));
        $this->assertSame('test%20test', purify_uri('test test', true));
        $this->assertSame('test%20test', purify_uri('test test', false));
        $this->assertSame('', purify_uri('test test', true, true));

        $this->assertSame('test%20%3A%20test', purify_uri('test : test'));
        $this->assertSame('test%20%3A%20test', purify_uri('test : test', true));
        $this->assertSame('test%20%3A%20test', purify_uri('test : test', false));
        $this->assertSame('', purify_uri('test : test', true, true));

        $this->assertSame('http://www.example.com/test.php?xx=%271%27&bb=%222%22#abc%20c', purify_uri(" http://www.example.com/test.php?xx='1'&amp;bb=\"2\n\"#abc\t c "));

        $this->assertSame('/www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http:/www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('', purify_uri('http:/www.example.com/test.php?xx=1&bb=2#abc', true, true));
        $this->assertSame('www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http:www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('', purify_uri('http:www.example.com/test.php?xx=1&bb=2#abc', true, true));

        // No user names and passwords in URIs.
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://username:password@www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://username:password@www.example.com/test.php?xx=1&bb=2#abc', true));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://username:password@www.example.com/test.php?xx=1&bb=2#abc', false));

    }
}
