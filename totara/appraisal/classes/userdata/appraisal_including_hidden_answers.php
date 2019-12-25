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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

namespace totara_appraisal\userdata;

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This is the export-only user data item for the user's appraisal content including answers which the user cannot normally see.
 */
class appraisal_including_hidden_answers extends appraisal_export {
    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        return static::export_conditional($user, true);
    }
}