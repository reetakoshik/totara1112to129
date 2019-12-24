<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nairv@totaralearning.com>
 * @package totara
 * @subpackage totara_appraisal
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Holds constant values used all over the appraisal codebase.
 */
class totara_appraisal_constants {
    // URLs
    const URL_MYAPPRAISAL = '/totara/appraisal/myappraisal.php';

    // Form action values.
    const ACTION_ASSIGN_JOB = 'job';

    // Query parameters.
    const PARAM_JOB_ID = 'job_id';
}