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
 * @author Aleksandr Baishev <aleksandr.baishev@@totaralearning.com>
 * @package mod_chat
 */

namespace mod_chat\userdata;


use context;
use core_date;
use DateTime;
use DateTimeZone;
use moodle_database;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Class chat_userdata_helper
 *
 * @package mod_chat\userdata
 */
abstract class chat_userdata_helper {
    /**
     * @var target_user
     */
    protected $user;

    /**
     * @var context
     */
    protected $context;

    /**
     * @var moodle_database
     */
    protected $db;

    /**
     * User full name
     *
     * @var string
     */
    protected $name;

    /**
     * chat_userdata_helper constructor.
     * @param target_user $user Target user for userdata items
     * @param context $context Context object
     * @param moodle_database|null $db Database object reference
     */
    public function __construct(target_user $user, context $context, moodle_database $db = null) {
        if (is_null($db)) {
            global $DB;

            $db = $DB;
        }

        $this->user = $user;
        $this->context = $context;
        $this->db = $db;
        $this->name = fullname($this->fetch_user($user->id));
    }

    /**
     * Fetch user from the database
     *
     * @param int $id User id
     * @return false|\stdClass
     */
    protected function fetch_user($id) {
        // Not returning guest user here.
        if (($id = intval($id)) < 2) {
            return false;
        }

        return $this->db->get_record('user', ['id' => $id]);
    }

    /**
     * Prepare query
     *
     * @param string $field Activity id field
     * @param string $userfield User id field
     * @return array [SQL where, SQL joins, SQL where params]
     */
    protected function prepare_query(string $field = 'target.id', $userfield = 'target.userid') {
        $where = [];
        $joins = '';

        if (trim($userfield) != '') {
            $userfield = clean_param($userfield, PARAM_TEXT);
            $where[] = "{$userfield} = :user_id";
        }

        $params = ['user_id' => $this->user->id];

        switch ($this->context->contextlevel) {
            case CONTEXT_COURSE:
            case CONTEXT_MODULE:
            case CONTEXT_COURSECAT:
                $joins = item::get_activities_join($this->context, 'chat', $field, 'activity');
                break;

            default:
                // Do nothing. If context is invalid or not supported we fall back to the system context, which is
                // site-wide, so no additional checks needed.
        }

        // AND is hardcoded as it's fine here.
        $where = trim(implode(' AND ', $where));
        $joins = trim($joins);

        return [
            $where,
            $joins,
            $params
        ];

    }

    /**
     * Convert timestamp to a human readable time string in the exported user timezone
     *
     * @param int $timestamp Timestamp
     * @return string
     */
    protected function human_time($timestamp) {
        $date = new DateTime("@$timestamp");
        $date->setTimezone(new DateTimeZone(core_date::normalise_timezone($this->user->timezone)));
        return $date->format('F j, Y, g:i a T');
    }
}