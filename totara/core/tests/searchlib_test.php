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

/**
 * Tests search stuff.
 */
class totara_core_searchlib_testcase extends advanced_testcase {
    public function test_totara_search_parse_keywords() {
        global $CFG;
        require_once("$CFG->dirroot/totara/core/searchlib.php");

        $this->assertSame(array('xx'), totara_search_parse_keywords('xx'));
        $this->assertSame(array('xx '), totara_search_parse_keywords("'xx '"));
        $this->assertSame(array('xx', 'yy'), totara_search_parse_keywords('xx yy'));
        $this->assertSame(array('xx', 'yy'), totara_search_parse_keywords('xx yy '));
        $this->assertSame(array('xx', 'yy'), totara_search_parse_keywords('   xx  yy '));
        $this->assertSame(array('xx', 'yy zz'), totara_search_parse_keywords('   xx  "yy zz"'));
        $this->assertSame(array('xx', 'yy zz '), totara_search_parse_keywords('   xx  "yy zz "'));
    }

    public function test_totara_search_get_keyword_where_clause() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/core/searchlib.php");

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user(array('username' => 'prvni', 'firstname' => 'John'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'druhy', 'firstname' => 'Prvni'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'prvnii', 'firstname' => 'Peter'));

        $result = totara_search_get_keyword_where_clause(array('druh'), array('username'));
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey($user2->id, $users);

        $result = totara_search_get_keyword_where_clause(array('prvni'), array('username'));
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey($user1->id, $users);
        $this->assertArrayHasKey($user3->id, $users);

        $result = totara_search_get_keyword_where_clause(array('prv', 'vnii'), array('username'));
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey($user3->id, $users);

        $result = totara_search_get_keyword_where_clause(array('rvn'), array('username', 'firstname'));
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(3, $users);
        $this->assertArrayHasKey($user1->id, $users);
        $this->assertArrayHasKey($user2->id, $users);
        $this->assertArrayHasKey($user3->id, $users);

        // Weird data.

        $result = totara_search_get_keyword_where_clause(array(), array('username'));
        $this->assertCount(2, $result);
        $this->assertSame('', $result[0]);
        $this->assertInternalType('array', $result[1]);
    }

    public function test_search_get_keyword_where_clause_options() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/core/searchlib.php");

        $this->resetAfterTest();

        $guest = guest_user();
        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'aabbcc'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'bbccddaa'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'aabb'));

        $result = search_get_keyword_where_clause_options('username', array('ab'), false, 'contains', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey($user1->id, $users);
        $this->assertArrayHasKey($user3->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('ab'), true, 'contains', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(3, $users);
        $this->assertArrayHasKey($user2->id, $users);
        $this->assertArrayHasKey($guest->id, $users);
        $this->assertArrayHasKey($admin->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('ab', 'cd'), false, 'contains', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(3, $users);
        $this->assertArrayHasKey($user1->id, $users);
        $this->assertArrayHasKey($user2->id, $users);
        $this->assertArrayHasKey($user3->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('ab', 'cd'), true, 'contains', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey($guest->id, $users);
        $this->assertArrayHasKey($admin->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('aa'), false, 'startswith', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey($user1->id, $users);
        $this->assertArrayHasKey($user3->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('aa'), true, 'startswith', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(3, $users);
        $this->assertArrayHasKey($user2->id, $users);
        $this->assertArrayHasKey($guest->id, $users);
        $this->assertArrayHasKey($admin->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('aa'), false, 'endswith', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey($user2->id, $users);

        $result = search_get_keyword_where_clause_options('username', array('aa'), true, 'endswith', false);
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('(', $result[0]);
        $this->assertStringEndsWith(')', $result[0]);
        $this->assertInternalType('array', $result[1]);
        $users = $DB->get_records_sql("SELECT * FROM {user} WHERE {$result[0]}", $result[1]);
        $this->assertCount(4, $users);
        $this->assertArrayHasKey($user1->id, $users);
        $this->assertArrayHasKey($user3->id, $users);
        $this->assertArrayHasKey($guest->id, $users);
        $this->assertArrayHasKey($admin->id, $users);

        // Weird data.

        $result = search_get_keyword_where_clause_options('username', array(), true, 'contains', false);
        $this->assertCount(2, $result);
        $this->assertSame('', $result[0]);
        $this->assertInternalType('array', $result[1]);
    }
}
