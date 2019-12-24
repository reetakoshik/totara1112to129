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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_language
 */

namespace availability_language;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition on user language.
 */
class condition extends \core_availability\condition {

    /** @var string language */
    protected $lang;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {
        // Get the lang.
        if (!empty($structure->lang)) {
            $this->lang = $structure->lang;
        } else {
            throw new \coding_exception('Missing ->lang for language condition');
        }
    }

    /**
     * Save the restriction
     *
     * @return \stdClass Details of the restriction
     */
    public function save() {
        $result = new \stdClass();
        $result->type = 'language';
        $result->lang = $this->lang;

        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param string $lang
     * @return \stdClass Object representing condition
     */
    public static function get_json($lang) {
        $result = new \stdClass();
        $result->type = 'language';
        $result->lang = $lang;

        return $result;
    }

    /**
     *  Determines if this condition allow the activity to be available
     *
     *  @param bool $not
     *  @param core_availability\info $info
     *  @param bool $grabthelot Performance, not here used as there is
     *                          only a single API call.
     *  @param int $userid
     *
     *  @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $allow = $this->is_language_condition_met($userid, $info);
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Get condition description
     *
     * @param bool $full Display full description or shortened version, not used
     * @param bool $not Should the condition be inverted
     * @param core_availability/info $info
     *
     * @return string Text describing the conditions of restriction
     */
    public function get_description($full, $not, \core_availability\info $info) {
        $installedlangs = get_string_manager()->get_list_of_translations();
        if (!isset($installedlangs[$this->lang])) {
            $langstr = '(' . $this->lang . ')';
        } else {
            $langstr = $installedlangs[$this->lang];
        }

        if ($not) {
            return get_string('notassignedtolangx', 'availability_language', $langstr);
        } else {
            return get_string('assignedtolangx', 'availability_language', $langstr);
        }
    }

    /**
     * Return debugging string
     *
     * @return string Debug text
     */
    protected function get_debug_string() {
        return $this->lang ? $this->lang : '';
    }

    /**
     * Returns true if the users language matches the required language, false otherwise.
     *
     * @param int $userid
     * @param \core_availability\info $info
     * @return boolean True if conditions are met
     */
    protected function is_language_condition_met($userid, \core_availability\info $info) {
        global $DB, $USER;

        $course = $info->get_course();

        if (isset($course->lang) && $course->lang == $this->lang) {
            // The course has a forced language that matches this condition.
            $conditionmet = true;
        } else if ($USER->id == $userid && isset($USER->lang)) {
            $conditionmet = $this->lang == current_language();
        } else {
            $conditionmet = $this->lang === $DB->get_field('user', 'lang', ['id' => $userid]);
        }

        return $conditionmet;
    }

}
