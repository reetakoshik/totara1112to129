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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

class users extends formatter {

    /** @var string */
    private $sourcedelimiter;

    /** @var string */
    private $resultdelimiter;

    /**
     * @param string $useridsfield the database field containing the array of user ids (use $DB->group_concat)
     * @param string $sourcedelimiter indicating how the source items are delimited
     * @param string $resultdelimiter indicating how the resulting items should be delimited
     */
    public function __construct(
        string $useridsfield,
        string $sourcedelimiter = ',',
        string $resultdelimiter = ', '
    ) {
        $this->add_required_field('userids', $useridsfield);
        $this->sourcedelimiter = $sourcedelimiter;
        $this->resultdelimiter = $resultdelimiter;
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_TEXT,
        ];
    }

    /**
     * Produce an alphabetically sorted list of users from a list of userids.
     *
     * Expects $data to contain key 'userids' which is a string containing user ids.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {
        global $DB;

        if (!array_key_exists('userids', $data)) {
            throw new \coding_exception("Users data formatter expects 'userids'");
        }

        if (empty($data['userids'])) {
            return '';
        }

        $userids = explode($this->sourcedelimiter, $data['userids']);

        $userrecords = $DB->get_records_list('user', 'id', $userids);

        $users = [];

        foreach ($userrecords as $userrecord) {
            $users[] = fullname($userrecord);
        }

        sort($users);

        return implode($this->resultdelimiter, $users);
    }
}
