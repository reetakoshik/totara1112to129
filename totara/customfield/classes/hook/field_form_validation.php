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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Manage Totara custom fields.
 */
class field_form_validation extends \totara_core\hook\base {
    /**
     * @var array $data of ("fieldname"=>value) of submitted data
     */
    public $data;

    /**
     * @var array $errors of "element_name"=>"error_description" if there are errors,
     */
    public $errors;

    /**
     * Manage Totara custom fields.
     *
     * @param array $data of ("fieldname"=>value) of submitted data
     * @param array $errors of "element_name"=>"error_description" if there are errors,
     */
    public function __construct(array $data, array &$errors) {
        $this->data = $data;
        $this->errors =& $errors;
    }

}