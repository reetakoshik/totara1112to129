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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_stats
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/totara_stats/locallib.php');

/**
 * Test the util class for report graph block.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_stats
 */
class block_totara_stats_testcase extends advanced_testcase {

    public function test_totara_stats_manager_stats_without_staff() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $config = new stdClass;
        $stats = totara_stats_manager_stats($user, $config);
        $this->assertCount(0, $stats);

        // Now actually request some stats.
        $config->statlearnerhours = true;
        $config->statcoursesstarted = true;
        $config->statcoursescompleted = true;
        $config->statcompachieved = true;
        $config->statobjachieved = true;
        $stats = totara_stats_manager_stats($user, $config);

        $this->assertCount(5, $stats);
        foreach ($stats as $statdata) {
            $this->assertNotEmpty($statdata->sql);
            $this->assertTrue(isset($statdata->sqlparams));
            $this->assertNotEmpty($statdata->string);
            $this->assertContains('1 <> 1', $statdata->sql);
        }

    }

}
