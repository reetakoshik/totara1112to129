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

defined('MOODLE_INTERNAL') || die();

class totara_core_menu_testcase extends advanced_testcase {
    public function test_url_replace() {
        global $COURSE;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->setUser($user);
        $COURSE = $course;

        $url = 'http://example.com/##username##/index.php?id=##userid##&course=##courseid###xxx##useremail##';
        $result = \totara_core\totara\menu\menu::replace_url_parameter_placeholders($url);
        $encodedemail = urlencode($user->email);
        $encodedusername = urlencode($user->username);
        $this->assertSame("http://example.com/{$encodedusername}/index.php?id={$user->id}&course={$course->id}#xxx{$encodedemail}", $result);
    }

    public function test_validate() {
        $this->resetAfterTest(true);

        $data = new stdClass();
        $data->title = 'Some title';
        $data->custom = '1';
        $data->url = 'http://example.com/##username##/index.php?id=##userid##&course=##courseid###xxx##useremail##';
        $data->classname = 'someclass';
        $data->targetattr = '_blank';
        $errors = \totara_core\totara\menu\menu::validation($data);
        $this->assertSame(array(), $errors);

        $data = new stdClass();
        $data->title = 'Some title';
        $data->custom = '0';
        $data->classname = 'someclass';
        $data->targetattr = '_blank';
        $errors = \totara_core\totara\menu\menu::validation($data);
        $this->assertSame(array(), $errors);

        $data = new stdClass();
        $data->title = str_pad('sometitle', 1025, 'x');
        $data->custom = '1';
        $data->url = str_pad('/', 256, 'x');
        $data->classname = str_pad('someclass', 256, 'x');
        $data->targetattr = str_pad('_blank', 101, '_');
        $errors = \totara_core\totara\menu\menu::validation($data);
        $this->assertCount(4, $errors);
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('url', $errors);
        $this->assertArrayHasKey('classname', $errors);
        $this->assertArrayHasKey('targetattr', $errors);

        $data = new stdClass();
        $data->title = '';
        $data->custom = '1';
        $data->url = '';
        $data->classname = '';
        $data->targetattr = '';
        $errors = \totara_core\totara\menu\menu::validation($data);
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('url', $errors);

        $data = new stdClass();
        $data->title = 'Some title';
        $data->custom = '1';
        $data->url = 'http:/xxx';
        $errors = \totara_core\totara\menu\menu::validation($data);
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('url', $errors);
    }
}
