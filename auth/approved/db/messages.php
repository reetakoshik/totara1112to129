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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_approved
 */

$messageproviders = array (
    'autoapproved_request' => array(
        'capability' => 'auth/approved:approve',
        'defaults' => array( // Do not send any notifications by default.
            'popup' => MESSAGE_PERMITTED,
            'email' => MESSAGE_PERMITTED,
        ),
    ),
    'confirmed_request' => array(
        'capability' => 'auth/approved:approve',
        'defaults' => array(
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
        ),
    ),
    'unconfirmed_request' => array(
        'capability' => 'auth/approved:approve',
        'defaults' => array( // Do not send any notifications by default.
            'popup' => MESSAGE_PERMITTED,
            'email' => MESSAGE_PERMITTED,
        ),
    ),
);
