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
class field_form_set_data extends \totara_core\hook\base {
    /**
     * @var array moodleform globals workaround
     */
    public $customdata;

    /**
     * @var \moodleform
     */
    public $mform;

    /**
     * Manage Totara custom fields.
     *
     * @param moodleform $mform, moodle form
     * @param array $customdata, moodle form globals workaround
     */
    public function __construct(\moodleform &$mform, array $customdata) {
        $this->mform =& $mform;
        $this->customdata = $customdata;
    }
}