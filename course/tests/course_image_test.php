<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_get_course_image_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_course_get_image() {
        global $CFG, $OUTPUT;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        // Return false if there is not image anywhere.
        $url = course_get_image($course);
        $expected = $OUTPUT->image_url('course_defaultimage', 'moodle');
        $this->assertEquals($expected->out(), $url->out());

        $this->setAdminUser();

        $context = context_course::instance($course->id);
        $fs = get_file_storage();

        $rc = [
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'images',
            'filepath' => '/',
            'filename' => 'hello_world.png',
            'mimetype' => 'png',
            'itemid' => 0,
            'license' => 'public'
        ];

        $fs->create_file_from_string($rc, 'Hello World !!!');

        $url = course_get_image($course);
        $expected = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/course/images/{$course->cacherev}/image";
        $this->assertEquals($expected, $url->out());
    }
}