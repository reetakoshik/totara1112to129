<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

/*
 * Testing of send notification tasks
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}
global $CFG;

class tool_policy_sendnotification_tool_policy_task_testcase extends advanced_testcase {
    /**
     * Test simple run
     */
    public function test_sendnotification_tool_policy_task() {
        $this->resetAfterTest();
        $cron = new \tool_policy\task\sendnotification_tool_policy_task();
        $cron->testing = true;
        $cron->execute();
        $this->execute_adhoc_tasks();
    }

    
}