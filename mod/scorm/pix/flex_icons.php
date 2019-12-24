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
 * @package mod_scorm
 */

/* Developer documentation is in /pix/flex_icons.php file. */

$icons = array(
    'mod_scorm|icon' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-archive',
                ),
        ),
    'mod_scorm|suspend' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-moon-o',
                ),
        ),
);

$aliases = array(
    'mod_scorm|minus' => 'minus-square-o',
    'mod_scorm|notattempted' => 'square-o',
    'mod_scorm|passed' => 'check-square-o',
    'mod_scorm|plus' => 'plus-square-o',
    'mod_scorm|wait' => 'loading',
);
