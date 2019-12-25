<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

abstract class block_current_learning_testcase_base extends advanced_testcase {

    public function get_learning_data($userid) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/blocks/current_learning/block_current_learning.php');
        $current_learning = new block_current_learning();

        // Force the userid to another user.
        $useridproperty = new ReflectionProperty($current_learning, 'userid');
        $useridproperty->setAccessible(true);
        $useridproperty->setValue($current_learning, $userid);

        $current_learning->page = $PAGE;
        $current_learning->instance = new \stdClass();
        $current_learning->instance->id = 1; // Add instance id so the get_content call doesn't error.
        $current_learning->get_content();

        $contextdata = new ReflectionProperty($current_learning, 'contextdata');
        $contextdata->setAccessible(true);

        return $contextdata->getValue($current_learning);
    }

    public function course_in_learning_data($courseid, $learning_data) {
        foreach ($learning_data['learningitems'] as $item) {
            if ($item->id == $courseid && $item->type == 'course') {
                return true;
            }
        }
        return false;
    }

    public function program_in_learning_data($program, $learning_data) {
        foreach ($learning_data['learningitems'] as $item) {
            if ($item->id == $program->id && $item->fullname == $program->fullname && $item->type == 'program') {
                return true;
            }
        }
        return false;
    }

    public function courseset_program_in_learning_data($program, $coursesetname, $learning_data) {
        foreach ($learning_data['learningitems'] as $programitem) {
            if ($programitem->id == $program->id && $programitem->type == 'program') {
                foreach ($programitem->coursesets as $courseset) {
                    if ($courseset->name == $coursesetname) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function course_program_in_learning_data($program, $course, $learning_data) {
        foreach ($learning_data['learningitems'] as $programitem) {
            if ($programitem->id == $program->id && $programitem->type == 'program') {
                foreach ($programitem->coursesets as $courseset) {
                    foreach ($courseset->courses as $courseitem) {
                        if ($courseitem->id == $course->id && $courseitem->fullname == $course->fullname) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function certification_in_learning_data($certification, $learning_data) {
        foreach ($learning_data['learningitems'] as $item) {
            if ($item->id == $certification->id && $item->fullname == $certification->fullname && $item->type == 'certification') {
                return true;
            }
        }
        return false;
    }

    public function course_certification_in_learning_data($certification, $course, $learning_data) {
        foreach ($learning_data['learningitems'] as $certificationitem) {
            if ($certificationitem->id == $certification->id && $certificationitem->type == 'certification') {
                foreach ($certificationitem->coursesets as $courseset) {
                    foreach ($courseset->courses as $courseitem) {
                        if ($courseitem->id == $course->id && $courseitem->fullname == $course->fullname) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
