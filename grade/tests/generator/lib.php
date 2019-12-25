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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_grades
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Grades data generator class
 *
 * Class core_grades_generator
 *
 * @group core_grades
 */
class core_grades_generator extends testing_module_generator {

    /**
     * Merge given set of grade attributes with the default attributes
     *
     * @param array $attributes Array of attributes
     * @return array
     */
    protected function default_grade_attributes($attributes = []): array {
        global $USER;

        return array_merge([
            'userid' => $USER->id,
            'finalgrade' => 10,
            'rawgrademax' => 0,
            'rawgrademin' => 100,
            'timecreated' => time(),
            'timemodified' => time(),
        ], $attributes);
    }

    /**
     * Merge given set of historical grade attributes with the default attributes
     *
     * @param array $attributes Array of attributes
     * @return array
     */
    protected function default_historical_grade_attributes($attributes = []): array {
        global $USER;

        return array_merge([
            'action' => 2,
            'source' => 'system',
            'loggeduser' => $USER->id,
            //'itemid' => Must be supplied,
            'userid' => $USER->id,
            'rawgrade' => null,
            'rawgrademax' => 100,
            'rawgrademin' => 0,
            'rawscaleid' => null,
            'usermodified' => null,
            'finalgrade' => null,
            'hidden' => 0,
            'locked' => 0,
            'locktime' => 0,
            'exported' => 0,
            'overridden' => 0,
            'excluded' => 0,
            'feedback' => null,
            'feedbackformat' => 0,
            'information' => null,
            'informationformat' => 0,
            'timemodified' => null,
        ], $attributes);
    }

    /**
     * Create a new grade based on the given attributes
     *
     * @param array $attributes Array of attributes
     * @return \stdClass
     */
    public function new_grade(array $attributes): \stdClass {
        global $DB;

        // We won't re-fetch it from the database just append the id of freshly inserted record.
        return (object) array_merge($attributes = $this->default_grade_attributes($attributes), [
            'id' => $DB->insert_record('grade_grades', $attributes),
        ]);
    }

    /**
     * A helper to update grade record
     *
     * @param int $id Item id to update
     * @param array $attributes Array of attributes to update ['attribute' => 'value']
     * @param bool $touch Whether to touch the timestamps
     * @return null|stdClass
     */
    public function update_grade($id, array $attributes = [], $touch = true): ?\stdClass {
        global $DB;

        if ($touch && !isset($attributes['timemodified'])) {
            $attributes['timemodified'] = time();
        }

        $attributes['id'] = $id;

        $DB->update_record('grade_grades', (object) $attributes);

        return ($record = $DB->get_record('grade_grades', ['id' => $id], '*', MUST_EXIST)) ? $record : null;
    }

    /**
     * Create a new historical grade based on the given attributes
     *
     * @param array $attributes Array of attributes
     * @return stdClass
     */
    public function new_historical_grade(array $attributes): \stdClass {
        global $DB;

        if (isset($attributes['id']) && !isset($attributes['oldid'])) {
            $attributes['oldid'] = $attributes['id'];
        }

        // We won't re-fetch it from the database just append the id of freshly inserted record.
        return (object) array_merge($attributes = $this->default_historical_grade_attributes($attributes), [
            'id' => $DB->insert_record('grade_grades_history', $attributes),
        ]);
    }

    /**
     * Create a new historical grade based on the actual grade record
     *
     * @param \stdClass|int $grade Grade object or id
     * @param \stdClass|int|null $user User id, object or null to use current user
     * @return stdClass
     */
    public function new_historical_grade_from_grade($grade, $user = null): \stdClass {
        global $DB;

        if (!($grade instanceof \stdClass)) {
            $grade = $DB->get_record('grade_items', ['id' => $grade]);
        }

        if (is_null($user)) {
            global $USER;

            $user = $USER;
        } elseif (!($user instanceof \stdClass)) {
            $user = (object) ['id' => $user];
        }

        $intersection = [
            'itemid',
            'userid',
            'rawgrade',
            'rawgrademax',
            'rawgrademin',
            'rawscaleid',
            'usermodified',
            'finalgrade',
            'hidden',
            'locked',
            'locktime',
            'exported',
            'overridden',
            'excluded',
            'feedback',
            'feedbackformat',
            'information',
            'informationformat',
            'timemodified'
        ];

        $attributes = [
            'oldid' => $grade->id,
            'action' => 2,
            'source' => $grade->itemtype == 'mod' ? "mod/{$grade->itemmodule}" : 'system',
            'loggeduser' => $user->id,
        ];

        foreach ($intersection as $attribute) {
            $attributes[$attribute] = $grade->$attribute;
        }

        return $this->new_historical_grade($attributes);
    }

    /**
     * Insert new grade record for a given item in a semi-proper way, it doesn't replicate the grades api
     * But creates a 'historical' record in 'grade_grades_history' table if there is already a record in the main table.
     *
     * @param int|\stdClass $item Item id or object
     * @param float $final Final grade
     * @param int|\stdClass|null $user User id or object or null to get the current user
     * @return stdClass Grade object
     */
    public function new_grade_for_item($item, $final, $user = null): \stdClass {
        global $DB;

        // Normalizing attributes
        if (!($item instanceof \stdClass)) {
            $item = $DB->get_record('grade_items', ['id' => $item]);
        }

        if (is_null($user)) {
            global $USER;

            $user = $USER;
        } elseif (!($user instanceof \stdClass)) {
            $user = $DB->get_record('user', ['id' => $user]);
        }

        $final = floatval($final);

        if ($grade = $DB->get_record('grade_grades', ['itemid' => $item->id, 'userid' => $user->id])) {
            $this->new_historical_grade((array) $grade);
            return $this->update_grade($grade->id, [
                'rawgrademin' => $item->grademin,
                'rawgrademax' => $item->grademax,
                'finalgrade' => $final
            ]);
        }

        return $this->new_grade([
            'itemid' => $item->id,
            'userid' => $user->id,
            'rawgrademin' => $item->grademin,
            'rawgrademax' => $item->grademax,
            'finalgrade' => $final
        ]);
    }

    /**
     * Get grade item for module (not friendly with modules with multiple grade items)
     *
     * @param \stdClass|int $course Course object or id
     * @param string $module Module name
     * @param \stdClass|int $instance Module instance object or id
     * @return stdClass
     */
    public function get_item_for_module($course, $module, $instance): \stdClass {
        global $DB;

        if ($course instanceof \stdClass) {
            $course = $course->id;
        }

        if ($instance instanceof \stdClass) {
            $instance = $instance->id;
        }

        return $DB->get_record('grade_items', [
            'courseid' => $course,
            'itemmodule' => $module,
            'iteminstance' => $instance,
            'itemtype' => 'mod',
        ], '*', MUST_EXIST);
    }

}