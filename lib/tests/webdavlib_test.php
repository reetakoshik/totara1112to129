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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/webdavlib.php');

class core_webdavlib_testcase extends advanced_testcase {

    /**
     * Test the content-type value check method.
     */
    public function test_check_expected_contenttype() {
        $ref_method = new ReflectionMethod('webdav_client', 'check_expected_contenttype');
        $ref_method->setAccessible(true);

        // Just a hollow instance we'll use to test the check_expected_contenttype func.
        $instance = new webdav_client();

        // Accepted types.
        $this->assertTrue($ref_method->invoke($instance, 'application/xml'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml; charset=utf-8'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml; charset=UTF-8'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml; charset=\'utf-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml; charset=\'UTF-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml; charset="utf-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml; charset="UTF-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml;charset=utf-8'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml;charset=UTF-8'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml;charset=\'utf-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml;charset=\'UTF-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml;charset="utf-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'application/xml;charset="UTF-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml; charset=utf-8'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml; charset=UTF-8'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml; charset=\'utf-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml; charset=\'UTF-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml; charset="utf-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml; charset="UTF-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml;charset=utf-8'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml;charset=UTF-8'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml;charset=\'utf-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml;charset=\'UTF-8\''));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml;charset="utf-8"'));
        $this->assertTrue($ref_method->invoke($instance, 'text/xml;charset="UTF-8"'));

        // Lossy handling, just cause we don't want regressions.
        $this->assertTrue($ref_method->invoke($instance, ' application/xml '));
        $this->assertTrue($ref_method->invoke($instance, '  text/xml  '));
        $this->assertTrue($ref_method->invoke($instance, ' application/xml; charset=utf-8 '));
        $this->assertTrue($ref_method->invoke($instance, '  text/xml;charset=\'UTF-8\'  '));

        // Invalid types. These are not supported.
        $this->assertFalse($ref_method->invoke($instance, 'application/xml;'));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml;'));
        $this->assertFalse($ref_method->invoke($instance, 'text/html'));
        $this->assertFalse($ref_method->invoke($instance, 'text/html; charset=utf-8'));
        $this->assertFalse($ref_method->invoke($instance, 'text/html; charset=\'utf-8\''));
        $this->assertFalse($ref_method->invoke($instance, 'text/html; charset="utf-8"'));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml; charset="utf-8\''));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml; charset=\'utf-8"'));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml; charset="utf-8'));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml; charset=\'utf-8'));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml; charset=utf-8\''));
        $this->assertFalse($ref_method->invoke($instance, 'text/xml; charset=utf-8"'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset="utf-8\''));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=\'utf-8"'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset="utf-8'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=\'utf-8'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=utf-8\''));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=utf-8"'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=windows-1252'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=\'windows-1252\''));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset="windows-1252"'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=utf-8;'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset="utf-8";'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=\'utf-8\';'));
        $this->assertFalse($ref_method->invoke($instance, 'application/xml; charset=utf-8;'));
        $this->assertFalse($ref_method->invoke($instance, 'text'));
        $this->assertFalse($ref_method->invoke($instance, 'application'));
        $this->assertFalse($ref_method->invoke($instance, 'xml'));
        $this->assertFalse($ref_method->invoke($instance, 'sam'));
    }

}