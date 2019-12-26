<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 Totara Learning Solutions Ltd
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
require_once($CFG->dirroot . '/totara/customfield/field/menu/field.class.php');

class totara_customfield_menu_test extends advanced_testcase {
    public function test_save_load_data() {
        $this->resetAfterTest(true);

        $prefix = 'course';
        $tableprefix = 'course';
        /**
         * @var totara_customfield_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        $item_obj = new stdClass();
        $item_obj->id = 1;
        $item_obj->username = 'learner1';

        $namebase = "menu";
        $options = ['A & B', 'C < D', '5\'11"'];
        $i = 0;
        foreach ($options as $saved) {
            $i++;
            $name = $namebase . $i;
            $ids = $generator->create_menu($tableprefix, array($name => $options));

            $field = "customfield_" . $name;
            $item_obj->$field = $saved;
            customfield_save_data($item_obj, $prefix, $tableprefix, true);

            $customfield = new customfield_menu($ids[$name], $item_obj, $prefix, $tableprefix);

            // Confirm that internally it is no filtered.
            $savedata = $customfield->data;
            $this->assertSame($saved, $savedata);

            // Confirm that output is filtered.
            $formatdata = strip_tags(format_text($saved));
            $displaydata = $customfield->display_data();
            $this->assertSame($formatdata, $displaydata);
        }
    }
}
