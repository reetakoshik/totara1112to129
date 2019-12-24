<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

use block_totara_featured_links\form\validator\is_valid_certification;
use block_totara_featured_links\form\validator\is_valid_course;
use block_totara_featured_links\form\validator\is_valid_program;
use totara_form\item;
use totara_form\model;
use totara_form\validator;

require_once('test_helper.php');

/**
 * Class block_totara_featured_links_test_validators_testcase
 * Tests the validators for the featured links block forms
 */
class block_totara_featured_links_test_validators_testcase extends test_helper {

    /**
     * Makes sure that the course validator
     * doesnt find an error
     */
    public function test_is_valid_course_valid_course() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();

        $this->run_validator_check(
            new is_valid_course(),
            ['course_name_id' => $course->id],
            false
        );
    }

    /**
     * Makes sure that the course validator
     * finds an error when there is no course id
     */
    public function test_is_valid_course_no_course() {
        $this->resetAfterTest(true);

        $this->run_validator_check(
            new is_valid_course(),
            ['course_name_id' => null],
            true
        );
    }

    /**
     * Makes sure that the course validator
     * finds an error when the course is not visible to the user
     */
    public function test_is_valid_course_hidden_course() {
        $this->resetAfterTest(true);
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $course->visible = 0;
        $DB->update_record('course', $course);

        $this->run_validator_check(
            new is_valid_course(),
            ['course_name_id' => $course->id],
            true
        );
    }

    /**
     * Makes sure that the program validator
     * doesn't find an error on valid program
     */
    public function test_is_valid_program_valid_program() {
        $this->resetAfterTest(true);
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $programgenerator->create_program();

        $this->run_validator_check(
            new is_valid_program(),
            ['program_name_id' => $program->id],
            false
        );
    }

    /**
     * Makes sure that the program validator
     * finds an error when no program id is passed
     */
    public function test_is_valid_program_no_program() {
        $this->resetAfterTest(true);

        $this->run_validator_check(
            new is_valid_program(),
            ['program_name_id' => null],
            true
        );
    }

    /**
     * Makes sure that the program validator
     * finds an error when the user cannot see the program
     */
    public function test_is_valid_program_hidden_program() {
        $this->resetAfterTest(true);
        global $DB;
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $programgenerator->create_program();
        $program->visible = 0;
        $DB->update_record('prog', $program);

        $this->run_validator_check(
            new is_valid_program(),
            ['program_name_id' => $program->id],
            true
        );
    }

    /**
     * Makes sure that the program validator
     * finds an error when the program is actually a certification
     */
    public function test_is_valid_program_certification() {
        $this->resetAfterTest(true);
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certificationid = $programgenerator->create_certification();

        $this->run_validator_check(
            new is_valid_program(),
            ['program_name_id' => $certificationid],
            true
        );
    }

    /**
     * Makes sure that the certification validator
     * does not find an error when a valid certification is passed
     */
    public function test_is_valid_certification_valid_certification() {
        $this->resetAfterTest(true);

        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certificationid = $programgenerator->create_certification();

        $this->run_validator_check(
            new is_valid_certification(),
            ['certification_name_id' => $certificationid],
            false
        );
    }

    /**
     * Makes sure that the certification validator
     * finds an error when no id is passed
     */
    public function test_is_valid_certification_valid_no_certification() {
        $this->resetAfterTest(true);

        $this->run_validator_check(
            new is_valid_certification(),
            ['certification_name_id' => null],
            true
        );
    }

    /**
     * Makes sure that the certification validator
     * finds an error when the certification is hidden from the user
     */
    public function test_is_valid_certification_hidden() {
        $this->resetAfterTest(true);
        global $DB;

        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certificationid = $programgenerator->create_certification();
        $certification = $DB->get_record('prog', ['id' => $certificationid]);
        $certification->visible = 0;
        $DB->update_record('prog', $certification);

        $this->run_validator_check(
            new is_valid_certification(),
            ['certification_name_id' => $certificationid],
            true
        );
    }

    /**
     * Makes sure that the certification validator
     * finds and error when the certification is actually a program
     */
    public function test_is_valid_certification_program() {
        $this->resetAfterTest(true);

        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $programgenerator->create_program();

        $this->run_validator_check(
            new is_valid_certification(),
            ['certification_name_id' => $program->id],
            true
        );
    }

    /**
     * Checks that the $validator calls {@link validator::add_error}
     * was called once() when there should be an error an or never() when there shouldnt be an error
     * given $data
     * @param validator $validator
     * @param array $data
     * @param bool $error
     */
    private function run_validator_check(validator $validator, array $data, bool $error) {
        $mockmodel = $this->getMockBuilder(model::class)
            ->setMethods(['get_raw_post_data'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockmodel->method('get_raw_post_data')
            ->willReturn($data);

        // The element_validator is meant to take a element but get_model is final.
        // So I'm using reflection to set it to a mock of an item instead.
        $mockelement = $this->createMock(item::class);
        $mockelement->method('get_model')
            ->willReturn($mockmodel);

        if ($error) {
            $mockelement->expects($this->once())
                ->method('add_error');
        } else {
            $mockelement->expects($this->never())
                ->method('add_error');
        }

        $this->set_protected_property($validator, 'element', $mockelement);
        $validator->validate();
    }

}