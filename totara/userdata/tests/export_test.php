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
 * @package totara_userdata
 * @category test
 */

use totara_userdata\userdata\export;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the base item class.
 */
class totara_userdata_export_testcase extends advanced_testcase {

    /**
     * Test that no new properties can be added
     */
    public function test_property_set() {
        $export = new export();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('export instance cannot be modified');

        $export->xx = 1;
    }

    /**
     * Check that adding a file adds it to the files property and returns the correct data
     */
    public function test_add_file() {
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $fs = get_file_storage();

        $filerecord = [
            'component' => 'phpunit',
            'filearea' => 'userdata',
            'contextid' => $syscontext->id,
            'itemid' => 0,
            'filename' => 'mytestfile1.txt',
            'filepath' => '/test1/'
        ];
        $file = $fs->create_file_from_string($filerecord, 'test1');

        $export = new export();
        $result = $export->add_file($file);

        $this->assertEquals(
            [
                'fileid' => $file->get_id(),
                'filename' => $file->get_filename(),
                'contenthash' => $file->get_contenthash()
            ],
            $result
        );

        $this->assertArrayHasKey($file->get_id(), $export->files);
        $this->assertContains($file, $export->files);
    }

}
