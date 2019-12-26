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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

class totara_reportbuilder_rb_config_testcase extends advanced_testcase {
    public function test_set_methods() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $rbconfig = new rb_config();

        $this->assertSame(array(), $rbconfig->get_embeddata());
        $data = array('a' => 'b');
        $result = $rbconfig->set_embeddata($data);
        $this->assertSame($data, $rbconfig->get_embeddata());
        $this->assertSame($rbconfig, $result);

        $this->assertSame(0, $rbconfig->get_sid());
        $sid = '1123';
        $result = $rbconfig->set_sid($sid);
        $this->assertSame((int)$sid, $rbconfig->get_sid());
        $this->assertSame($rbconfig, $result);

        $this->assertSame(false, $rbconfig->get_nocache());
        $nocache = true;
        $result = $rbconfig->set_nocache($sid);
        $this->assertSame($nocache, $rbconfig->get_nocache());
        $this->assertSame($rbconfig, $result);

        $this->assertSame((int)$USER->id, $rbconfig->get_reportfor());
        $user = $this->getDataGenerator()->create_user();
        $rbconfig->set_reportfor($user->id);
        $result = $rbconfig->set_reportfor($user->id);
        $this->assertSame((int)$user->id, $rbconfig->get_reportfor());
        $this->assertSame($rbconfig, $result);

        $result = $rbconfig->set_reportfor(null);
        $this->assertSame((int)$USER->id, $rbconfig->get_reportfor());
        $this->assertSame($rbconfig, $result);

        $this->assertSame(null, $rbconfig->get_global_restriction_set());
    }

    public function test_finalise() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $rbconfig = new rb_config();

        $rbconfig->finalise();

        $this->assertSame(array(), $rbconfig->get_embeddata());
        $this->assertSame(0, $rbconfig->get_sid());
        $this->assertSame(false, $rbconfig->get_nocache());
        $this->assertSame((int)$USER->id, $rbconfig->get_reportfor());
        $this->assertSame(null, $rbconfig->get_global_restriction_set());

        try {
            $data = array('a' => 'b');
            $result = $rbconfig->set_embeddata($data);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $sid = '1123';
            $result = $rbconfig->set_sid($sid);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $nocache = true;
            $result = $rbconfig->set_nocache($sid);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            $user = $this->getDataGenerator()->create_user();
            $rbconfig->set_reportfor($user->id);
            $result = $rbconfig->set_reportfor($user->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }
}