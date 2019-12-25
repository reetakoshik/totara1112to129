<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 *
 * @package totara_reportbuilder
 *
 */

defined("MOODLE_INTERNAL") || die();

global $CFG;

require_once($CFG->dirroot . "/totara/reportbuilder/lib.php");

/**
 * Unit test for facetoface_assets report builder testing the SQL query
 * of custom report and embedded report
 *
 * Class reportbuilder_test
 * @method fail(string $message)
 */
class rb_facetoface_asset_reportbuilder_test extends advanced_testcase {
    /**
     * Saving all the columns for the report builder
     * @param rb_source_facetoface_assets    $src
     * @param int                           $id     The report builder id
     */
    private function set_up_columns(rb_source_facetoface_asset $src, int $id): void {
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
     * @param stdClass          $user                 User that is creating the report
     * @param bool              $userembedded         Determine whether using the default source report or not
     * @return reportbuilder
     */
    private function set_up_report_builder(stdClass $user, $userembedded = false): reportbuilder {
        global $DB;
        $id = null;
        if (!$userembedded) {
            $rp = [
                'shortname'         => "f2fa_test",
                'source'            => 'facetoface_asset',
                'fullname'          => 'This is SPARTA',
                'hidden'            => 0,
                'embed'             => 0,
                'accessmode'        => 1,
                'contentmode'       => 0,
                'description'       => 'wowow',
                'recordsperpage'    => 40,
                'toolbarsearch'     => 1,
                'globalrestriction' => 1,
                'timemodified'      => time(),
                'defaultsortorder'  => 4
            ];

            $id = $DB->insert_record("report_builder", (object)$rp);

            /** @var rb_source_facetoface_assets $src */
            $src = reportbuilder::get_source_object($rp['source']);
            $this->set_up_columns($src, $id);
        }

        return new reportbuilder(
            $id,
            'facetoface_assets',
            false,
            null,
            $user->id,
            false,
            [],
            null
        );
    }

    /**
     * Injecting the data within table `facetoface_asset` and update source parameters.
     *
     * @param array $items
     */
    private function create_face2face_assets(array &$items): void {
        if (empty($items)) {
            return;
        }

        global $DB;

        foreach ($items as $index => $item) {
            /** @var stdClass $item */
            $item = is_array($item) ? (object)$item : $item;
            $id = $DB->insert_record("facetoface_asset", $item, true);
            $item->id = $id;
            $items[$index] = $item;
        }
    }

    /**
     * Helper method to invoke the private methods of report builder class
     *
     * @param reportbuilder     $reportbuilder
     * @param int               $max
     * @return counted_recordset
     */
    private function query_records(reportbuilder $reportbuilder, int $max=2): counted_recordset {
        list($sql, $params, $cache) = $reportbuilder->build_query(false, true);

        $refClass = new ReflectionClass($reportbuilder);
        $method = $refClass->getMethod("get_counted_recordset_sql");
        $method->setAccessible(true);

        $results = $method->invokeArgs($reportbuilder, [$sql, $params, 0, $max, true]);
        return $results;
    }

    /**
     * Providing assets data (with the ability to tweak data type).
     *
     * @param stdClass  $user   User who created the asset
     * @param bool      $isobj  As if we want an array of stdClass, then make this true.
     * @return array
     */
    private function dummy_data(stdClass $user, $isobj = false): array {
        $data = [
            [
                'name'              => "test1",
                'allowconflicts'    => 1,
                'description'       => "" ,
                'custom'            => 0,
                'usercreated'       => $user->id,
                'usermodified'      => $user->id,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ],
            [
                'name'              => 'test2',
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
     * Create two assets of which one is a shared asset.
     *
     * Query should return only one result - the shared asset.
     *
     */
    public function test_query(): void {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $user = $USER;

        $reportbuilder = $this->set_up_report_builder($user, false);
        $data = $this->dummy_data($user, true);
        $this->create_face2face_assets($data);

        $records = $this->query_records($reportbuilder);
        $this->assertEquals(1, $records->get_count_without_limits());
    }

    /**
     * Create two assets of which one is a shared asset.
     *
     * Embedded report query should return only one result.
     *
     * @see rb_facetoface_assets_reportbuilder_test::test_query
     */
    public function test_embedded_query(): void {
        global $USER;

        $this->setAdminUser();
        $user = $USER;
        $this->resetAfterTest(true);

        $reportbuilder = $this->set_up_report_builder($user, true);
        $data = $this->dummy_data($user, false);
        $this->create_face2face_assets($data);

        $records = $this->query_records($reportbuilder);
        $this->assertEquals(1, $records->get_count_without_limits());
    }
}
