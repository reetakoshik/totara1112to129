<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package core_user
 */


namespace core_user;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use stdClass;

/**
 * A class to update the `email_bounce_count` and also `email_send_count` within table user_preference. It will most
 * likely working with the action of editing the user email. Since, user has another option to cancel the request of
 * changing the email.
 * Therefore, the system need to keep track the history of these preferences (temporally). Until the user confirm the
 * request, then these track records will be removed
 *
 * @since Totara 11.8
 *
 * @package core_user
 */
final class email_bounce_counter {
    /** @var stdClass */
    private $user;

    /** @var int|null */
    private $backup_bounce_count = null;

    /** @var int|null */
    private $backup_send_count = null;

    /**
     * Constructor.
     *
     * @param stdClass $user The target $user that is being updated
     */
    public function __construct(stdClass $user) {
        $this->user = $user;
        if (!isset($this->user->id)) {
            throw new coding_exception("Missing \$user->id");
        }
    }

    /**
     * Fetch current count.
     *
     * @param string $preference 'email_bounce_count' or 'email_send_count'
     * @return int
     */
    private function get_count(string $preference) {
        // NOTE: use the user id instead of user object to fetch the latest value.
        return (int)get_user_preferences($preference, 0, $this->user->id);
    }

    /**
     * Call when email bounces back..
     *
     * @return void
     */
    public function increase_bounce_count(): void {
        $count = $this->get_count('email_bounce_count');
        set_user_preference('email_bounce_count', $count + 1, $this->user->id);
    }

    /**
     * Call after every email that was sent.
     *
     * @return void
     */
    public function increase_send_count(): void {
        $count = $this->get_count('email_send_count');
        set_user_preference('email_send_count', $count + 1, $this->user->id);
    }

    /**
     * A method to reset the user's bounce and send count preference.
     *
     * @return void
     */
    public function reset_counts(): void {
        // Bundle reset methods here.
        $this->reset_bounce_count();
        $this->reset_send_count();
    }

    /**
     * Reset email bounce count to 0. By default, it will keep on track the current value of bounce
     * counter.
     *
     * @return void
     */
    public function reset_bounce_count(): void {
        $this->backup_bounce_count = $this->get_count('email_bounce_count');
        set_user_preference('email_bounce_count', 0, $this->user->id);
    }

    /**
     * Reset send email count to 0. By default, it will keep track on the current value of send
     * counter
     *
     * @return void
     */
    public function reset_send_count(): void {
        $this->backup_send_count = $this->get_count('email_send_count');
        set_user_preference('email_send_count', 0, $this->user->id);
    }

    /**
     * A bundle method of restoring the 'email_bounce_count' and 'email_send_count'
     * in case we want to reset the email bounce/send counter of the user.
     *
     * @return void
     */
    public function restore(): void {
        if (isset($this->backup_bounce_count)) {
            set_user_preference('email_bounce_count', $this->backup_bounce_count, $this->user->id);
        }
        if (isset($this->backup_send_count)) {
            set_user_preference('email_send_count', $this->backup_send_count, $this->user->id);
        }
    }

    /**
     * Returning null if the backup value of email_bounce_count and email_send_count is not being set.
     * Or the key is not appearing in the map. Otherwise, an integer of backup counter should be
     * returned here.
     *
     * @param string $key
     * @return int|null
     */
    public function get_backup_count_value(string $key): ?int {
        $map = array(
            'email_bounce_count' => $this->backup_bounce_count,
            'email_send_count' => $this->backup_send_count
        );

        return (isset($map[$key])) ? (int)$map[$key] : null;
    }
}