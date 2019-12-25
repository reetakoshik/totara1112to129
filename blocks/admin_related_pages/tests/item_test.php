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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_admin_related_pages
 */

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

use block_admin_related_pages\item;

/**
 * Item tests
 *
 * @package block_admin_related_pages
 * @group block_admin_related_pages
 */
class block_admin_related_pages_item_testcase extends \basic_testcase {

    private function expose_property($object, $property) {
        $identifier = new \ReflectionProperty($object, $property);
        if (!$identifier->isPublic()) {
            $identifier->setAccessible(true);
        }
        return $identifier->getValue($object);
    }

    public function test_construct() {
        $item = new item('a', 'pluginname', 'block_admin_related_pages', ['d', 'e']);
        self::assertSame('a', $item->get_key());
        $label = $item->get_label();
        self::assertInstanceOf(\lang_string::class, $label);
        self::assertSame('pluginname', $this->expose_property($label, 'identifier'));
        self::assertSame('block_admin_related_pages', $this->expose_property($label, 'component'));
        self::assertSame(['d', 'e'], $item->get_parents());
    }

    public function test_add_parent_get_parents() {
        $item = new item('a', 'pluginname', 'block_admin_related_pages', []);
        self::assertSame([], $item->get_parents());
        $item->add_parent('d');
        self::assertSame(['d'], $item->get_parents());
        $item->add_parent('e');
        self::assertSame(['d', 'e'], $item->get_parents());
        $item->add_parent('f');
        self::assertSame(['d', 'e', 'f'], $item->get_parents());
        $item->add_parent('d');
        self::assertSame(['d', 'e', 'f'], $item->get_parents());
        $item->add_parent('e');
        self::assertSame(['d', 'e', 'f'], $item->get_parents());
        $item->add_parent('f');
        self::assertSame(['d', 'e', 'f'], $item->get_parents());
        $item->add_parent('');
        self::assertSame(['d', 'e', 'f', ''], $item->get_parents());
        $item->add_parent('7');
        self::assertSame(['d', 'e', 'f', '', '7'], $item->get_parents());
    }

    public function test_add_related_page_get_related_pages() {
        $item = new item('a', 'pluginname', 'block_admin_related_pages', []);
        self::assertSame([], $item->get_related_pages());
        $item->add_related_page('d', 'D');
        self::assertSame(['d' => 'D'], $item->get_related_pages());
        $item->add_related_page('e', 'E');
        self::assertSame(['d' => 'D', 'e' => 'E'], $item->get_related_pages());
        $item->add_related_page('f', 'F');
        self::assertSame(['d' => 'D', 'e' => 'E', 'f' => 'F'], $item->get_related_pages());
        $item->add_related_page('d', 'G');
        self::assertSame(['d' => 'D', 'e' => 'E', 'f' => 'F'], $item->get_related_pages());
        $item->add_related_page('e', 'H');
        self::assertSame(['d' => 'D', 'e' => 'E', 'f' => 'F'], $item->get_related_pages());
        $item->add_related_page('f', 'I');
        self::assertSame(['d' => 'D', 'e' => 'E', 'f' => 'F'], $item->get_related_pages());
        $item->add_related_page('7', 'J');
        self::assertSame(['d' => 'D', 'e' => 'E', 'f' => 'F', '7' => 'J'], $item->get_related_pages());
    }

    public function test_add_related_page_empty_key() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Invalid related page key.');
        $item = new item('a', 'pluginname', 'block_admin_related_pages', []);
        $item->add_related_page('', '8');
    }

    public function test_add_related_page_empty_from() {
        $item = new item('a', 'pluginname', 'block_admin_related_pages', []);
        $item->add_related_page('8', '');
        self::assertSame(['8' => ''], $item->get_related_pages());
    }

    public function test_set_get_url() {
        $item = new item('a', 'pluginname', 'block_admin_related_pages', []);
        self::assertInstanceOf(\moodle_url::class, $item->get_url());
        self::assertSame('/', $item->get_url()->out_as_local_url(false));
        $item->set_url(new \moodle_url('/test', ['a' => 'A']));
        self::assertSame('/test?a=A', $item->get_url()->out_as_local_url(false));
    }

    public function test_prepare_to_cache() {
        $item = new item('a', 'pluginname', 'block_admin_related_pages', []);
        self::assertSame([
            'a',
            'pluginname',
            'block_admin_related_pages',
            '/',
            [],
            []
        ], $item->prepare_to_cache());

        $item->set_url(new \moodle_url('/test', ['a' => 'A']));
        $item->add_parent('one');
        $item->add_parent('two');
        $item->add_related_page('a', 'A');
        $item->add_related_page('b', 'B');
        self::assertSame([
            'a',
            'pluginname',
            'block_admin_related_pages',
            '/test?a=A',
            ['one', 'two'],
            ['a' => 'A', 'b' => 'B']
        ], $item->prepare_to_cache());

    }

    public function test_wake_from_cache() {
        $item = item::wake_from_cache([
            'a',
            'pluginname',
            'block_admin_related_pages',
            '/',
            [],
            []
        ]);
        self::assertInstanceOf(item::class, $item);
        self::assertSame('a', $item->get_key());
        $label = $item->get_label();
        self::assertInstanceOf(\lang_string::class, $label);
        self::assertSame('pluginname', $this->expose_property($label, 'identifier'));
        self::assertSame('block_admin_related_pages', $this->expose_property($label, 'component'));
        self::assertSame([], $item->get_parents());
        self::assertSame([], $item->get_related_pages());
        self::assertSame('/', $item->get_url()->out_as_local_url(false));

        $item = item::wake_from_cache([
            'a',
            'pluginname',
            'block_admin_related_pages',
            '/test?a=A',
            ['one', 'two'],
            ['a' => 'A', 'b' => 'B']
        ]);
        self::assertInstanceOf(item::class, $item);
        self::assertSame('a', $item->get_key());
        $label = $item->get_label();
        self::assertInstanceOf(\lang_string::class, $label);
        self::assertSame('pluginname', $this->expose_property($label, 'identifier'));
        self::assertSame('block_admin_related_pages', $this->expose_property($label, 'component'));
        self::assertSame(['one', 'two'], $item->get_parents());
        self::assertSame(['a' => 'A', 'b' => 'B'], $item->get_related_pages());
        self::assertSame('/test?a=A', $item->get_url()->out_as_local_url(false));
    }

}