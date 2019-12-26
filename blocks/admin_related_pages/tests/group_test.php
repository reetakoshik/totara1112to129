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
use block_admin_related_pages\group;

/**
 * Group tests
 *
 * @package block_admin_related_pages
 * @group block_admin_related_pages
 */
class block_admin_related_pages_group_testcase extends \basic_testcase {

    public function tearDown() {
        global $ADMIN;
        $ADMIN = null;
        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\admin_root
     */
    private function mock_admin_root() {
        $root = $this->createMock(\admin_root::class);
        return $root;
    }

    /**
     * @param string $key
     * @param string $name
     * @param string $url
     * @return \PHPUnit\Framework\MockObject\MockObject|\admin_externalpage
     */
    private function mock_admin_externalpage(string $key = 'test', string $name = 'Test', string $url = '/test') {
        $page = $this->getMockBuilder(\admin_externalpage::class)
            ->setMethods(['check_access', 'is_hidden'])
            ->setConstructorArgs([$key, $name, $url])
            ->getMock();
        $page->expects($this->any())->method('check_access')->willReturn(true);
        $page->expects($this->any())->method('is_hidden')->willReturn(false);
        return $page;
    }

    private function mock_admin_category($name, $visiblename, $hidden, array $pages = []) {
        $category = $this->getMockBuilder(\admin_category::class)
            ->setMethods(['get_children'])
            ->setConstructorArgs([$name, $visiblename, $hidden])
            ->getMock();
        $category->expects($this->any())->method('get_children')->willReturn($pages);
        return $category;
    }

    public function test_construct() {
        $group = new group();
        self::assertInstanceOf(group::class, $group);
        self::assertCount(0, $group->get_items());

        $group = new group([
            new item('a', 'pluginname', 'block_admin_related_pages'),
            new item('b', 'pluginname', 'block_admin_related_pages'),
        ]);
        self::assertInstanceOf(group::class, $group);
        self::assertCount(2, $group->get_items());
    }

    public function test_add_item() {
        $group = new group();
        self::assertCount(0, $group->get_items());
        $item1 = new item('a', 'pluginname', 'block_admin_related_pages');
        $group->add($item1);
        self::assertCount(1, $group->get_items());
        $item2 = new item('a', 'pluginname', 'block_admin_related_pages', ['d', 'e']);
        $group->add($item2);
        $items = $group->get_items();
        self::assertCount(2, $items);
        self::assertSame($item1, $items[0]);
        self::assertSame($item2, $items[1]);
    }

    public function test_get_keys() {
        $group = new group();
        self::assertCount(0, $group->get_keys());
        $item1 = new item('a', 'pluginname', 'block_admin_related_pages');
        $group->add($item1);
        self::assertCount(1, $group->get_keys());
        $item2 = new item('a', 'pluginname', 'block_admin_related_pages', ['d', 'e']);
        $group->add($item2);
        self::assertCount(2, $group->get_keys());
        self::assertSame(['a', 'a'], $group->get_keys());
    }

    public function test_resolve_relationships() {
        $root = $this->mock_admin_root();
        $page_a = $this->mock_admin_externalpage('a', 'A', '/A');
        $page_b = $this->mock_admin_externalpage('b', 'B', '/B');
        $page_c = $this->mock_admin_externalpage('c', 'C', '/C');
        $category = $this->mock_admin_category('test', 'Test', false, [$page_a, $page_c]);

        $root->expects($this->any())->method('locate')->will(
            $this->returnValueMap(
                [
                    ['a', $page_a],
                    ['b', $page_b],
                    ['c', $page_c],
                    ['test', $category],
                ]
            )
        );

        $group = new group([
            new item('a', 'pluginname', 'block_admin_related_pages'),
            new item('b', 'pluginname', 'block_admin_related_pages'),
            new item('test', 'pluginname', 'block_admin_related_pages'),
            new item('unknown', 'pluginname', 'block_admin_related_pages'),
        ]);
        $group->add_relationship('test', 'c');
        $group->add_relationship('b', 'c');
        $group->add_relationship('unknown', 'c');

        $keys = $group->get_keys();
        self::assertCount(4, $keys);
        self::assertSame(['a', 'b', 'test', 'unknown'], $keys);

        $items = $group->get_items();
        self::assertCount(4, $items);

        $item_1 = reset($items);
        $item_2 = next($items);
        $item_3 = next($items);
        $item_4 = next($items);

        self::assertSame('a', $item_1->get_key());
        self::assertSame('/', $item_1->get_url()->out_as_local_url(false));
        self::assertSame([], $item_1->get_related_pages());
        self::assertSame([], $item_1->get_parents());

        self::assertSame('b', $item_2->get_key());
        self::assertSame('/', $item_2->get_url()->out_as_local_url(false));
        self::assertSame([], $item_2->get_related_pages());
        self::assertSame([], $item_2->get_parents());

        self::assertSame('test', $item_3->get_key());
        self::assertSame('/', $item_3->get_url()->out_as_local_url(false));
        self::assertSame([], $item_3->get_related_pages());
        self::assertSame([], $item_3->get_parents());

        self::assertSame('unknown', $item_4->get_key());
        self::assertSame('/', $item_4->get_url()->out_as_local_url(false));
        self::assertSame([], $item_4->get_related_pages());
        self::assertSame([], $item_4->get_parents());

        $group->resolve_relationships($root);

        $keys = $group->get_keys();
        self::assertCount(7, $keys);
        self::assertSame(['a', 'b', 'test', 'unknown', 'a', 'c', 'b'], $keys);

        $items = $group->get_items();
        self::assertCount(4, $items);

        $item_1 = reset($items);
        $item_2 = next($items);
        $item_3 = next($items);
        $item_4 = next($items);

        self::assertSame('a', $item_1->get_key());
        self::assertSame('/', $item_1->get_url()->out_as_local_url(false));
        self::assertSame(['a' => 'c', 'c' => 'c', 'b' => 'c'], $item_1->get_related_pages());
        self::assertSame(['a', 'b', 'test', 'unknown'], $item_1->get_parents());

        self::assertSame('b', $item_2->get_key());
        self::assertSame('/', $item_2->get_url()->out_as_local_url(false));
        self::assertSame(['a' => 'c', 'c' => 'c', 'b' => 'c'], $item_2->get_related_pages());
        self::assertSame(['a', 'b', 'test', 'unknown'], $item_2->get_parents());

        self::assertSame('test', $item_3->get_key());
        self::assertSame('/', $item_3->get_url()->out_as_local_url(false));
        self::assertSame(['a' => 'c', 'c' => 'c', 'b' => 'c'], $item_3->get_related_pages());
        self::assertSame(['a', 'b', 'test', 'unknown'], $item_3->get_parents());

        self::assertSame('unknown', $item_4->get_key());
        self::assertSame('/', $item_4->get_url()->out_as_local_url(false));
        self::assertSame(['a' => 'c', 'c' => 'c', 'b' => 'c'], $item_4->get_related_pages());
        self::assertSame(['a', 'b', 'test', 'unknown'], $item_4->get_parents());
    }

}