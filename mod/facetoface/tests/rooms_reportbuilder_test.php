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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara
 * @subpackage reportbuilder
 *
 */

defined("MOODLE_INTERNAL") || die();

global $CFG;
require_once($CFG->dirroot . "/totara/reportbuilder/lib.php");

/**
 * Unit test for facetoface_rooms reprot builder
 * and the test is testing the SQL query of custom report plust
 * embedded report
 */
class mod_facetoface_rooms_reportbuilder_testcase extends advanced_testcase {
    /**
     * Saving all the columns for the report builder
     * @param rb_source_facetoface_rooms    $src
     * @param int                           $id     The report builder id
     */
    private function set_up_columns(rb_source_facetoface_rooms $src, int $id): void {
        global $DB;
        $columnoptions = $src->defaultcolumns;
        $so = 1;

        foreach ($columnoptions as $columnoption) {
            $heading = isset($columnoption['heading']) ? $columnoption['heading'] : null;
            $column = $src->new_column_from_option(
                $columnoption['type'],
                $columnoption['value'],
                null,
                null,
                $heading,
                !empty($heading),
                0
            );

            $item = [
                'reportid'      => $id,
                'type'          => $column->type,
                'value'         => $column->value,
                'heading'       => $column->heading,
                'hidden'        => $column->hidden,
                'transform'     => $column->transform,
                'aggregate'     => $column->aggregate,
                'sortorder'     => $so,
                'customheading' => 0,
            ];

            $DB->insert_record("report_builder_columns", (object) $item);
            $so += 1;
        }
    }

    /**
     * Helper method to setup the
     * report builder within the phpunit system
     *
     * @param stdClass          $user                 The user that is creating the report
     * @param bool              $userembedded         Determine whether using the default source report or not
     * @return reportbuilder
     */
    private function set_up_report_builder(stdClass $user, $userembedded = false): reportbuilder {
        global $DB;
        $id = null;

        $config = (new rb_config())->set_reportfor($user->id);

        if ($userembedded) {
            return reportbuilder::create_embedded('facetoface_rooms', $config);
        }

        $rp = [
            'shortname'         => 'f2fr_test',
            'source'            => 'facetoface_rooms',
            'fullname'          => 'This is SPARTAN',
            'hidden'            => 0,
            'embed'             => 0,
            'accessmode'        => 0,
            'contentmode'       => 0,
            'description'       => 'wowow',
            'recordsperpage'    => 40,
            'toolbarsearch'     => 1,
            'globalrestriction' => 1,
            'timemodified'      => time(),
            'defaultsortorder'  => 4
        ];

        $id = $DB->insert_record('report_builder', (object)$rp);

        /** @var rb_source_facetoface_rooms $src */
        $src = reportbuilder::get_source_object($rp['source']);
        $this->set_up_columns($src, $id);

        return reportbuilder::create($id, $config);
    }

    /**
     * Injecting the data within table
     * `facetoface_room` and update the
     * the source parameters.
     *
     * @param array $items
     */
    private function create_face2face_rooms(array &$items): void {
        if (empty($items)) return;
        global $DB;

        foreach ($items as $index => $item) {
            /** @var stdClass $item */
            $item = is_array($item) ? (object)$item : $item;
            $id = $DB->insert_record("facetoface_room", $item, true);
            $item->id = $id;
            $items[$index] = $item;
        }
    }

    /**
     * Providing the data with the ability to tweak data type
     *
     * @param stdClass  $user   User who created the room
     * @param bool      $isobj  As if we want an array of stdClass, then make this true.
     * @return array
     */
    private function dummy_data(stdClass $user, $isobj = false): array {
        $data = [
            [
                'name'              => "test1",
                'capacity'          => 10,
                'allowconflicts'    => 1,
                'description'       => "" ,
                "custom"            => 0,
                'usercreated'       => $user->id,
                'usermodified'      => $user->id,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ],
            [
                'name'              => 'test2',
                'capacity'          => 15,
                'allowconflicts'    => 1,
                'description'       => "",
                'custom'            => 1,
                'usercreated'       => $user->id,
                'usermodified'      => $user->id,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ]
        ];

        if ($isobj) {
            $data[0] = (object) $data[0];
            $data[1] =  (object) $data[1];
        }
        return $data;
    }

    /**
     * Test suite for checking whether
     * the sql is actually working or not
     *
     * Since we are creating 2 records of
     * facetoface_rooms, however with one
     * of the records is set as custom room
     * and it is not in use (SPECTRE Room)
     * Therefore within this test suite,
     * it should not appear in the record resultset
     */
    public function test_query(): void {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $user = $USER;

        $reportbuilder = $this->set_up_report_builder($user, false);
        $data = $this->dummy_data($user);
        $this->create_face2face_rooms($data);

        $this->assertEquals(1, $reportbuilder->get_filtered_count());
    }

    /**
     * The test suite for the case
     * of one room of record is the custom
     * room and it is in use.
     *
     * Therefore the result we are expecting from querying the record
     * should equal to 2
     */
    public function test_query2(): void {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $user = $USER;

        $reportbuilder = $this->set_up_report_builder($user, false);
        $data = $this->dummy_data($user,true);
        $this->create_face2face_rooms($data);

        $ghostroom = $data[1];
        $params = [
            'sessionid'         => 1,
            'sessiontimezone'   => "something",
            'roomid'            => $ghostroom->id,
            'timestart'         => time(),
            'timefinish'        => time() * 36
        ];

        $DB->insert_record("facetoface_sessions_dates", (object) $params);

        $this->assertEquals(2, $reportbuilder->get_filtered_count());
    }

    /**
     * The test suite for the scenario that
     * the viewing of report is embedded report
     *
     * Therefore the base query would have been tweaked
     * a bit. However, we still expected result the same with the
     * test suite test_query, as the embedded report will
     * only look into those global rooms.
     *
     * @see rb_facetoface_rooms_reportbuilder_test::test_query
     */
    public function test_embedded_query(): void {
        global $USER;

        $this->setAdminUser();
        $user = $USER;

        $this->resetAfterTest(true);

        $reportbuilder = $this->set_up_report_builder($user, true);
        $data = $this->dummy_data($user, false);
        $this->create_face2face_rooms($data);

        $this->assertEquals(1, $reportbuilder->get_filtered_count());
    }
}
