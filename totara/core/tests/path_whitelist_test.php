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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use totara_core\path_whitelist;

defined('MOODLE_INTERNAL') || die();

class totara_core_path_whitelist_testcase extends advanced_testcase {
    public function test_path_whitelist_add_join() {
        $class = new \ReflectionClass(path_whitelist::class);
        $var = $class->getProperty('whitelist');
        $var->setAccessible(true);

        $whitelist = new path_whitelist('');
        $this->assertTrue($whitelist->is_empty());

        $whitelist = new path_whitelist(array());
        $this->assertTrue($whitelist->is_empty());

        $whitelist->add('foo/bar/qux.php');
        $this->assertFalse($whitelist->is_empty());
        $this->assertCount(1, $var->getValue($whitelist));

        $addpath = implode(DIRECTORY_SEPARATOR, array('foo', 'bar', 'qux.php'));
        $whitelist->add($addpath);
        $this->assertCount(1, $var->getValue($whitelist));

        $whitelist->add('/foo/bar/qux.php');
        $this->assertCount(2, $var->getValue($whitelist));

        $whitelist->add(array(
            'lorem/ipsum',
            'dolor/sit/amet.txt',
            'lorem' . DIRECTORY_SEPARATOR . 'ipsum'
        ));
        $this->assertCount(4, $var->getValue($whitelist));

        $join = $whitelist->join("\n");
        $this->assertEquals(3, substr_count($join, "\n"));
    }

    public function test_path_whitelist_search_remove() {
        $whitelist = new path_whitelist([
            'foo/bar/qux.php',
            'lorem/ipsum/'
        ]);
        $this->assertFalse($whitelist->search('does/not/match.txt'));

        $key = $whitelist->search('foo/bar/qux.php');
        $this->assertNotFalse($key);
        $testpath = implode(DIRECTORY_SEPARATOR, array('foo', 'bar', 'qux.php'));
        $key = $whitelist->search($testpath);
        $this->assertNotFalse($key);
        $whitelist->remove($key);

        $testpath = implode(DIRECTORY_SEPARATOR, array('lorem', 'ipsum'));
        $key = $whitelist->search($testpath);
        $this->assertFalse($key);

        $testpath = implode(DIRECTORY_SEPARATOR, array('lorem', 'ipsum', ''));
        $key = $whitelist->search($testpath);
        $this->assertNotFalse($key);

        $whitelist->remove($key);
        $this->assertTrue($whitelist->is_empty());
    }

    public function test_path_whitelist_normalise_path() {
        global $CFG;
        // NOTE: $CFG->dirroot is already "normalised"

        $class = new \ReflectionClass(path_whitelist::class);
        $method = $class->getMethod('normalise_path');
        $method->setAccessible(true);
        $result = $method->invoke(null, $CFG->dirroot . '/foo/bar/qux.php');
        $expected = $CFG->dirroot . implode(DIRECTORY_SEPARATOR, array('', 'foo', 'bar', 'qux.php'));
        $this->assertEquals($expected, $result);
    }
}
