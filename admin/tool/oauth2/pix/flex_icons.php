<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package auth_oauth2
 */

$icons = [
    'tool_oauth2|yes' => [
        'data' => [
            'classes' => 'fa-check ft-state-success',
        ],
    ],
    'tool_oauth2|no' => [
        'data' => [
            'classes' => 'fa-times ft-state-danger',
        ],
    ],
    'tool_oauth2|auth' => [
        'data' => [
            'classes' => 'fa-sign-out',
        ],
    ],
    'tool_oauth2|endpoints' => [
        'data' => [
            'classes' => 'fa-th-list',
        ],
    ],
];
