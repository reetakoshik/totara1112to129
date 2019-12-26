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
 * @package block_last_course_accessed
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2018112200;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2017051509;       // Requires this Moodle version.
$plugin->component = 'block_last_course_accessed'; // To check on upgrade, that module sits in correct place