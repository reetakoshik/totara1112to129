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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 */

namespace core\workflow\core_course\coursecreate;

defined('MOODLE_INTERNAL') || die();

/**
 * Standard create course workflow implementation.
 */
class standard extends \totara_workflow\workflow\base {

    public function get_name(): string {
        return get_string('createmultiactivitycourse', 'moodle');
    }

    public function get_description(): string {
        return get_string('createmultiactivitycoursedesc', 'moodle');
    }

    public function get_image(): ?\moodle_url {
        if ($this->output) {
            return $this->output->image_url('course_defaultimage');
        }
        return new \moodle_url('/pix/course_defaultimage.svg');
    }

    protected function get_workflow_url(): \moodle_url {
        return new \moodle_url('/course/edit.php');
    }

}
