<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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

class totara_core_clone_db_testcase extends advanced_testcase {
    public function test_totara_is_clone_db_configured() {
        global $CFG;
        $this->resetAfterTest();

        $CFG->clone_dbname = null;
        $this->assertFalse(totara_is_clone_db_configured());

        $CFG->clone_dbname = 'abc';
        $this->assertTrue(totara_is_clone_db_configured());
    }

    public function test_totara_get_clone_db() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $this->assertObjectNotHasAttribute('clone_dbname', $DB);
        $this->assertNull(totara_get_clone_db(true));

        $CFG->clone_dbname = $CFG->dbname;

        $db = totara_get_clone_db(true);
        $this->assertInstanceOf(get_class($DB), $db);
        $this->assertNotSame($DB, $db);

        $allversion = $DB->get_field('config', 'value', array('name' => 'allversionshash'));
        $this->assertSame($allversion, $db->get_field('config', 'value', array('name' => 'allversionshash')));

        $this->assertSame($db, totara_get_clone_db());
        $this->assertNotSame($db, totara_get_clone_db(true));

        unset($CFG->clone_dbname);
        $this->assertNull(totara_get_clone_db(true));
    }

    protected function tearDown() {
        global $CFG;

        unset($CFG->clone_dbname);
        totara_get_clone_db(true);

        parent::tearDown();
    }
}