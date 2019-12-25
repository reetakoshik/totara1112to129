<?php
/*
 * This file is part of Totara LMS
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class totara_certification_user_learning_item_testcase extends advanced_testcase {

    private $generator;
    private $program_generator, $completion_generator;
    private $course1, $course2;
    private $certification1;
    private $user1;

    protected function tearDown() {
        $this->generator = null;
        $this->program_generator = null;
        $this->completion_generator = null;
        $this->course1 = $this->course2 = null;
        $this->certification1 = null;
        $this->user1 = null;
        parent::tearDown();
    }

    public function setUp() {
        $this->resetAfterTest(true);
        parent::setUp();

        $this->generator = $this->getDataGenerator();
        $this->program_generator = $this->generator->get_plugin_generator('totara_program');
        $this->completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        // Create some course.
        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();

        // Create a certification.
        $certification1id = $this->program_generator->create_certification(array('fullname' => 'Certification 1'));
        $this->certification1 = new program($certification1id);

        $this->user1 = $this->getDataGenerator()->create_user(array('fullname' => 'user1'));
    }

    function test_is_single_course_true() {
        $this->resetAfterTest(true);

        // Setup certification content.
        $certcontent = new prog_content($this->certification1->id);
        $certcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $certcontent->get_course_sets();

        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $certcontent->add_course(1, $coursedata);

        // Do some more setup.
        $coursesets[0]->nextsetoperator = NEXTSETOPERATOR_OR;

        // Set completion type.
        $coursesets[0]->completiontype = COMPLETIONTYPE_ALL;

        // Set certifpath.
        $coursesets[0]->certifpath = CERTIFPATH_CERT;

        // Save the sets
        $coursesets[0]->save_set();

        // Assign the user to the certification.
        $this->program_generator->assign_program($this->certification1->id, array($this->user1->id));

        // Get the certification and process the coursesets.
        $certification_item = \totara_certification\user_learning\item::one($this->user1->id, $this->certification1->id);

        $this->assertEquals($certification_item->is_single_course()->fullname, $this->course1->fullname);
        $this->assertEquals($certification_item->is_single_course()->id, $this->course1->id);
    }

    function test_is_single_course_false() {
        $this->resetAfterTest(true);

        // Setup certification content.
        $certcontent = new prog_content($this->certification1->id);
        $certcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $certcontent->get_course_sets();

        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $certcontent->add_course(1, $coursedata);
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $certcontent->add_course(1, $coursedata);

        // Do some more setup.
        $coursesets[0]->nextsetoperator = NEXTSETOPERATOR_AND;

        // Set completion type.
        $coursesets[0]->completiontype = COMPLETIONTYPE_ALL;

        // Set certifpath.
        $coursesets[0]->certifpath = CERTIFPATH_CERT;

        // Save the sets
        $coursesets[0]->save_set();

        // Assign the user to the certification.
        $this->program_generator->assign_program($this->certification1->id, array($this->user1->id));

        // Get the certification and process the coursesets.
        $certification_item = \totara_certification\user_learning\item::one($this->user1->id, $this->certification1->id);

        $this->assertFalse($certification_item->is_single_course());
    }
}
