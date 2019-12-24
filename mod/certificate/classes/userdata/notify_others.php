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
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package mod_certificate
 */

namespace mod_certificate\userdata;

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles certificate data that contains a list of email addresses
 * to notify users of a certificate issue using the email address.
 */
class notify_others extends \totara_userdata\userdata\item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdata_notify_users', 'mod_certificate'];
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        // User email's can be empty (now) so check we have a value.
        if (!empty($user->email)) {
            $params = [];
            $params['email'] = '%' . $DB->sql_like_escape($user->email) . '%';

            $join = self::get_activities_join($context, 'certificate', 'c1.id', 'c2');

            $sql = "SELECT c1.id, c1.emailothers
                    FROM {certificate} c1
                    {$join}
                    WHERE " . $DB->sql_like("c1.emailothers", ":email");

            $records = $DB->get_records_sql($sql, $params);

            foreach ($records as $id => $record) {
                $old_emailothers = explode(',', $record->emailothers);
                $new_emailothers = [];

                // Unfortunately, because the field containing comma separated
                // email addresses there could be any number of spaces between
                // the comma and email address so we must trim the field to make
                // sure the email address is clean and not filled out.
                foreach ($old_emailothers as $email) {
                    $email = trim($email);

                    if ($email !== $user->email) {
                        $new_emailothers[] = $email;
                    }
                }

                // If the $user email address has been removed from the list
                // in emailothers then update the certificate record.
                if (count($new_emailothers) < count($old_emailothers)) {
                    $record->emailothers = implode(', ', $new_emailothers);

                    $DB->update_record('certificate', $record);
                }
            }
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * For the certificate notify users field, it can be any email address.
     * We can only handle Totara user email addresses here, which is a copy
     * of the users email address, so there's no value providing an export.
     *
     * @return bool
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        if (!empty($user->email)) {
            $params = [];
            $params['email'] = '%' . $DB->sql_like_escape($user->email) . '%';

            $join = self::get_activities_join($context, 'certificate', 'c1.id', 'c2');

            $sql = "SELECT COUNT(c1.id)
                    FROM {certificate} c1
                    {$join}
                    WHERE " . $DB->sql_like("c1.emailothers", ":email");
            $count = $DB->count_records_sql($sql, $params);

            return $count;
        } else {
            return 0;
        }
    }
}