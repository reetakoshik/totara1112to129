<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_program
 */

namespace totara_program\hook;

defined('MOODLE_INTERNAL') || die();

final class program_edit_form_save_changes extends base {
    /**
     * Form data.
     *
     * @var \stdClass
     */
    private $formdata;

    /**
     * program_edit_form_save_changes constructor.
     *
     * @param \stdClass $formdata
     * @param int       $programid
     */
    public function __construct(\stdClass $formdata, int $programid) {
        parent::__construct($programid);
        $this->formdata = $formdata;
    }

    /**
     * @return \stdClass
     */
    public function get_form_data(): \stdClass {
        return $this->formdata;
    }
}