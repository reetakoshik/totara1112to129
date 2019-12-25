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
 * Totara navigation edit page.
 *
 * @package    totara
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */
namespace totara_core\totara\menu;

use \totara_core\totara\menu\menu as menu;

class myreports extends \totara_core\totara\menu\item {

    protected function get_default_title() {
        return get_string('reports', 'totara_core');
    }

    protected function get_default_url() {
        return '/my/reports.php';
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    public function get_default_sortorder() {
        return 60000;
    }

    protected function check_visibility() {
        global $CFG;

        static $cache = null;
        if (isset($cache)) {
            return $cache;
        }

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        $reportbuilder_permittedreports = \reportbuilder::get_user_permitted_reports();
        $hasreports = (is_array($reportbuilder_permittedreports) && (count($reportbuilder_permittedreports) > 0));
        if ($hasreports) {
            $cache = menu::SHOW_ALWAYS;
        } else {
            $cache = menu::HIDE_ALWAYS;
        }
        return $cache;
    }
}
