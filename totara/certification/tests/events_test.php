<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Test events in certifications.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_certifications_events_testcase
 *
 */
class totara_certification_events_testcase extends advanced_testcase {

    /** @var totara_program_generator */
    private $program_generator = null;
    /** @var program */
    private $program = null;
    private $user = null;

    protected function tearDown() {
        $this->program_generator = null;
        $this->program = null;
        $this->user = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->program = $this->program_generator->create_program(array('fullname' => 'program1'));
        $this->user = $this->getDataGenerator()->create_user(array('fullname' => 'user1'));
    }

    public function test_certification_completionstateedited() {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array(
            'oldstate' => CERTIFCOMPLETIONSTATE_ASSIGNED,
            'newstate' => CERTIFCOMPLETIONSTATE_CERTIFIED,
            'changedby' => $USER->id
        );
        $event = \totara_program\event\program_completionstateedited::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other,
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }
}
