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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\user_learning;

use \core_course\user_learning\item as core_course;
use \totara_core\user_learning\designation_subitem;

class course extends core_course {

    use designation_subitem;

    public $duedate;

    /**
     * The number of points this course is worth.
     * @var int
     */
    public $points;

    /**
     * Gets the points this course.
     *
     * @param courseset $set
     * @return int|false
     */
    public function get_points(courseset $set) {
        if ($this->points !== null) {
            return $this->points;
        }

        if (empty($set->coursesumfield)) {
            return false;
        }

        $sumfield = customfield_get_field_instance($this->learningitemrecord, $set->coursesumfield, 'course', 'course');
        if ($sumfield) {
            $this->points += (int)$sumfield->display_data();
        }
        return $this->points;
    }
}
