<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author  Joby Harding <joby.harding@totaralms.com>
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package mod_book
 */

/* Developer documentation is in /pix/flex_icons.php file. */

/*
 * Unique book icons.
 */
$icons = array(
    'mod_book|icon' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-book',
                ),
        ),
    'mod_book|chapter' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-book-open',
                ),
        ),
    'mod_book|nav_exit' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-up',
                ),
        ),
    'mod_book|nav_next' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-flip-rtl',
                ),
        ),
    'mod_book|nav_prev' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-left ft-flip-rtl',
                ),
        ),
    'mod_book|nav_prev_dis' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-left ft-state-disabled ft-flip-rtl',
                ),
        ),
);

/*
 * These deprecated icons should not be used anymore.
 */
$deprecated = array(
    'mod_book|nav_next_dis' => 'caret-right-disabled',
    'mod_book|nav_sep' => 'spacer',
    'mod_book|add' => 'plus',
);
