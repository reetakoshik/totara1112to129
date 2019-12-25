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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

/**
 * Behat Totara dashboard generator.
 */
use Behat\Gherkin\Node\TableNode;


class behat_totara_dashboards extends behat_base {

    protected static $generator = null;

    protected function get_data_generator() {
        global $CFG;
        if (self::$generator === null) {
            require_once($CFG->libdir.'/testing/generator/lib.php');
            require_once($CFG->dirroot.'/totara/dashboard/tests/generator/lib.php');
            self::$generator = new totara_dashboard_generator(testing_util::get_data_generator());
        }
        return self::$generator;
    }

    /**
     * Create the totara dashboards
     *
     * @Given /^the following totara_dashboards exist:$/
     * @param TableNode $table
     * @throws Exception
     */
    public function the_following_totara_dashboards_exist(TableNode $table) {
        \behat_hooks::set_step_readonly(true); //Backend action.

        $required = array(
            'name'
        );
        $optional = array(
            'locked',
            'published',
            'cohorts',
        );

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Dashboards require the field '.$reqname.' to be set');
            }
        }

        // Copy values, ready to pass on to the generator.
        foreach ($data as $row) {
            $record = array();
            $allfields = array_merge($required, $optional);
            foreach ($row as $fieldname => $value) {
                if (in_array($fieldname, $allfields)) {
                    $record[$fieldname] = $value;
                } else {
                    throw new coding_exception('Unknown field '.$fieldname.' in totara dashboard definition');
                }
            }
            $this->get_data_generator()->create_dashboard($row);
        }
    }
}