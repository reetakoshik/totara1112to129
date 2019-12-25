<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package repository_opensesame
 */

require(__DIR__ . '/../../config.php');
require_once("$CFG->dirroot/lib/adminlib.php");

admin_externalpage_setup('opensesameabout');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('aboutopensesame', 'repository_opensesame'));
echo $OUTPUT->box(get_string('aboutopensesamedesc', 'repository_opensesame'));
echo '<div>'.get_string('aboutcontent', 'repository_opensesame').'</div>';
echo $OUTPUT->footer();
