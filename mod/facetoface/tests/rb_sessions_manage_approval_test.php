<?php
/*
 * This file is part of Totara Learn
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_rb_sessions_manage_approval_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Find an option in an array of rb_column_options.
     *
     * @param array $columnoptions rb_column_options as haystack
     * @param array $option array containing type and value strings as needle
     * @return boolean
     */
    private function find_column_option($columnoptions, $option) {
        if (empty($option)) {
            return false;
        }
        foreach ($columnoptions as $columnoption) {
            if ($columnoption->type = $option['type'] && $columnoption->value = $option['value']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test that the approval link column option exist.
     */
    public function test_approvallink_column_options() {
        $this->resetAfterTest();

        $src = reportbuilder::get_source_object('facetoface_sessions');
        $columnoptions = array();
        if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
            $columnoptions = $src->columnoptions;
        }

        $this->assertEquals(true, $this->find_column_option($columnoptions, ['type' => 'session', 'value' => 'approvallink']));
    }

    /**
     * Test that the default columns are correct.
     */
    public function test_default_columns() {
        $this->resetAfterTest();

        $src = reportbuilder::get_source_object('facetoface_sessions');
        if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
            $defaultcolumns = $src->defaultcolumns;
            $this->assertContains(['type' => 'user', 'value' => 'namelink'], $defaultcolumns);
            $this->assertContains(['type' => 'course', 'value' => 'courselink'], $defaultcolumns);
            $this->assertContains(['type' => 'date', 'value' => 'sessiondate'], $defaultcolumns);
            $this->assertContains(['type' => 'session', 'value' => 'approvallink'], $defaultcolumns);
        }
    }
}