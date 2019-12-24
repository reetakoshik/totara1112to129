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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package availability_audience
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/lib/modinfolib.php");


use availability_audience\section_util;

/**
 * Unit test for checking whether the class
 * section_util is loading cohort correctly
 *
 * Class section_util_test
 */
class section_util_test extends advanced_testcase {
    /**
     * @var int
     */
    private $sectionnumber = 1;

    /**
     * Creating the number of cohorts base
     * on the input parameter $count.
     *
     * @param int $count
     * @return stdClass[]
     */
    private function create_cohorts(int $count=2): array {
        $data = [];
        for ($i = 0; $i < $count ; $i++) {
            $data[] = $this->getDataGenerator()->create_cohort();
        }

        return $data;
    }

    /**
     * Utility method of formatting the section_info
     * availability in json format
     *
     * @param   stdClass[] $cohorts
     * @return string
     */
    private function build_availability_json(array $cohorts): string {
        $data = [
            'op' => "&",
            'c' => [],
            'showc' => []
        ];

        foreach ($cohorts as $cohort) {
            $data['showc'][] = true;
            $data['c'][] = [
                'type' => 'audience',
                'cohort' => $cohort->id
            ];
        }

        return json_encode($data);
    }

    /**
     * Setting the availability of section_info,
     * since the class itself does not allow the
     * attribute to set via public interface
     *
     * @param section_info $sectioninfo
     * @param string        $jsondata
     * @throws ReflectionException
     */
    private function build_section_availability(section_info &$sectioninfo, string $jsondata): void {
        $refClass = new ReflectionClass($sectioninfo);
        $property = $refClass->getProperty("_availability");
        $property->setAccessible(true);
        $property->setValue($sectioninfo, $jsondata);
    }

    /**
     * Steps:
     * - Create course with sections,
     * - Create 2 cohorts and adding it to the course section.
     *
     * Test suite of loading the cohorts of a course section,
     * via the sectioninfo's _availability attribute, in this
     * test suite, the cohorts are injected into the sectioninfo,
     * therefore the array of cohort availabilities should not be empty
     *
     * @return void
     * @throws ReflectionException
     * @throws moodle_exception
     */
    public function test_load_cohort_availabilities(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(null,['createsections' => true]);
        $sectioninfo = get_fast_modinfo($course->id)->get_section_info($this->sectionnumber);

        $cohorts = $this->create_cohorts();
        $json = $this->build_availability_json($cohorts);
        $this->build_section_availability($sectioninfo, $json);

        $util = new section_util($sectioninfo);
        $availabilities = $util->load_cohort_availabilities();
        $this->assertNotEmpty($availabilities);
    }

    /**
     * Create coruse with sections
     *
     * Test suite of checking whether the util class loads cohorts properly, since
     * the sectioninfo does not contiain any information about the cohort with availability,
     * therefore the result of method should be an empty array
     *
     * @throws ReflectionException
     * @throws moodle_exception
     */
    public function test_load_cohort_availabilities_with_empty_result(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(null, ['createsections' => true]);
        $sectioninfo = get_fast_modinfo($course->id)->get_section_info($this->sectionnumber);

        $this->build_section_availability($sectioninfo, json_encode([
            'op' => '&',
            'c' => [
                ['type' => 'language', 'lang' => 'en_us']
            ],
            'showc' => [true]
        ]));

        $util = new section_util($sectioninfo);
        $this->assertEmpty($util->load_cohort_availabilities());
    }
}
