<?php
/*
 * This file is part of Totara Learn
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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();
(defined('PHPUNIT_TEST') && PHPUNIT_TEST) || die();

/**
 * A report builder source for testing the report builder base source.
 */
class phpunit_test_report_source extends rb_base_source {

    public function __construct() {
        $this->base = '{base}';
        $this->sourcetitle = 'some title';
        $this->columnoptions[] = new rb_column_option('type', 'value', 'name', 'id');
        parent::__construct();
    }
}