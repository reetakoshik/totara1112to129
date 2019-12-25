<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_feedback360
 */

/**
 * TL-16443 Make all multichoice questions use int for param1.
 *
 * Whenever someone created a new scale for their question, it would store it as an integer in the param1 text field.
 * However, when using an existing scale, it would record the scale id with quotes around it. This caused a failure
 * in some sql. To make everything consistent and easier to process, we're changing them all to integers in text
 * fields, without quotes.
 */
function totara_feedback360_upgrade_fix_inconsistent_multichoice_param1() {
    global $DB;

    list($sql, $params) = $DB->sql_text_replace('param1', '"', '', SQL_PARAMS_NAMED);

    $sql = "UPDATE {feedback360_quest_field}
               SET {$sql}
             WHERE datatype IN ('multichoicemulti', 'multichoicesingle')
               AND " . $DB->sql_like('param1', ':colon', true, true, true) . "
               AND " . $DB->sql_like('param1', ':bracket', true, true, true) . "
               AND " . $DB->sql_like('param1', ':braces', true, true, true);
    $params['colon'] = '%:%';
    $params['bracket'] = '%[%';
    $params['braces'] = '%{%';

    $DB->execute($sql, $params);
}
