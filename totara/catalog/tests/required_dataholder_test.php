<?php
/*
 * This file is part of Totara Learn
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_requried_dataholder_testcase extends advanced_testcase {

    public function test_constructor() {
        $ft = \totara_catalog\dataformatter\formatter::TYPE_FTS;

        $formatter = new \totara_catalog\dataformatter\fts('somefield');

        $dh = new \totara_catalog\dataholder(
            'testkey',
            'testname',
            [
                $ft => $formatter,
            ]
        );

        $rd = new \totara_catalog\local\required_dataholder($dh, $ft);

        $this->assertEquals($dh, $rd->dataholder);
        $this->assertEquals($ft, $rd->formattertype);
        $this->assertEquals($formatter, $rd->formatter);
    }
}
