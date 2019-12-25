<?php
/*
 * This file is part of Totara LMS
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
 * Block for displaying the last course accessed by the user.
 *
 * @package block_last_course_accessed
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 */

namespace block_last_course_accessed\hook;

/**
 * Hook to allow extra data to be added to the object being passed to the template
 */
class template_content extends \totara_core\hook\base {
    /*
     * @var $template_object stdClass Contains the data that will be passed into the mustache template.
     */
    public $template_object;

    public function __construct($template_object) {
        $this->template_object = $template_object;
    }
}
